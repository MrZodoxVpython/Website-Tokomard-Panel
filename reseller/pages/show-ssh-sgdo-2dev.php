<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
$reseller = $_SESSION['reseller'] ?? $_SESSION['username'] ?? 'unknown';
$logDir = "/etc/xray/data-panel/reseller";
$akunFiles = glob("$logDir/akun-$reseller-*.txt");

// Hapus akun
if (isset($_GET['hapus'])) {
    $user = $_GET['hapus'];
    shell_exec("sudo userdel -f $user 2>/dev/null");
    @unlink("$logDir/akun-$reseller-$user.txt");
    header("Location: show-ssh-sgdo-2dev.php");
    exit;
}

// Edit expired
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit_user']) && isset($_POST['expired'])) {
        $user = $_POST['edit_user'];
        $expiredInput = trim($_POST['expired']);

        if (preg_match('/^\d+$/', $expiredInput)) {
            $expireStr = trim(shell_exec("chage -l $user | grep 'Account expires' | cut -d: -f2"));
            $expireStr = trim($expireStr);
            $current = $expireStr === "never" ? date('Y-m-d') : date('Y-m-d', strtotime($expireStr));
            $newDate = date('Y-m-d', strtotime("+$expiredInput days", strtotime($current)));
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiredInput)) {
            $newDate = $expiredInput;
        }

        if (!empty($newDate)) {
            shell_exec("sudo chage -E $newDate $user");
            $file = "$logDir/akun-$reseller-$user.txt";
            if (file_exists($file)) {
                $isi = file_get_contents($file);
                $isi = preg_replace('/(Expired On\s*:\s*)(\d{4}-\d{2}-\d{2}|never)/', '${1}' . $newDate, $isi);
                file_put_contents($file, $isi);
            }
        }

        header("Location: show-ssh-sgdo-2dev.php");
        exit;
    }

    // Start/Stop user
    if (isset($_POST['toggle_user']) && isset($_POST['action'])) {
        $user = $_POST['toggle_user'];
        $action = $_POST['action'];
        if ($action === 'stop') {
            shell_exec("sudo usermod -L $user");
        } elseif ($action === 'start') {
            shell_exec("sudo usermod -U $user");
        }
        header("Location: show-ssh-sgdo-2dev.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>SSH Account Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white p-6">
<div class="max-w-4xl mx-auto">
    <h1 class="text-xl font-bold mb-4 text-center">Daftar Akun SSH (SGDO-2DEV) - <?= htmlspecialchars($reseller) ?></h1>
    <?php if (empty($akunFiles)) : ?>
        <div class="bg-yellow-500/10 text-yellow-300 p-4 rounded text-center">⚠ Belum ada akun SSH untuk reseller ini.</div>
    <?php else: ?>
    <?php foreach ($akunFiles as $file):
    $filename = basename($file);
    preg_match('/akun-' . preg_quote($reseller, '/') . '-(.+)\.txt/', $filename, $m);
    $username = $m[1] ?? 'unknown';

    // Baca dan bersihkan konten (hapus karakter non-ASCII)
    $rawContent = file_get_contents($file);
    // Hapus karakter whitespace non-standar (non-breaking space, en space, dll)
$cleanContent = preg_replace('/[\x{00A0}\x{2000}-\x{200B}\x{FEFF}]/u', ' ', $rawContent);

// Normalisasi semua spasi ke biasa
$cleanContent = preg_replace('/\s+/', ' ', $cleanContent);

//    $cleanContent = preg_replace('/[^\x20-\x7E\s]/', '', $rawContent); // ASCII printable only
    if (stripos($cleanContent, 'SSH ACCOUNT') === false) {
        echo "<!-- SKIP: $filename -->";
        continue;
    }


    $content = $rawContent;

    // Status akun aktif/kunci
    $rawStatus = shell_exec("passwd -S $username 2>/dev/null");
    $status = $rawStatus !== null ? trim($rawStatus) : '';
    $isLocked = strpos($status, ' L ') !== false;
?>
    <div class="bg-gray-800 p-4 rounded mb-4 shadow">
        <div class="flex justify-between items-center">
            <div class="text-lg font-semibold"><?= htmlspecialchars($username) ?></div>
            <div class="space-x-2">
                <button id="btn-<?= $username ?>" onclick="toggleDetail('<?= $username ?>')" class="btn-show bg-blue-600 px-3 py-1 rounded hover:bg-blue-700">Show</button>

                <form method="POST" class="inline">
                    <input type="hidden" name="toggle_user" value="<?= htmlspecialchars($username) ?>">
                    <input type="hidden" name="action" value="<?= $isLocked ? 'start' : 'stop' ?>">
                    <button type="submit" class="<?= $isLocked ? 'bg-green-600 hover:bg-green-700' : 'bg-yellow-600 hover:bg-yellow-700' ?> px-3 py-1 rounded">
                        <?= $isLocked ? 'Start' : 'Stop' ?>
                    </button>
                </form>

                <a href="?hapus=<?= urlencode($username) ?>" onclick="return confirm('Yakin ingin menghapus akun <?= $username ?>?')" class="bg-red-600 px-3 py-1 rounded hover:bg-red-700">Delete</a>
                <button onclick="document.getElementById('form-<?= $username ?>').classList.toggle('hidden')" class="bg-green-600 px-3 py-1 rounded hover:bg-green-700">Edit</button>
            </div>
        </div>

        <div id="detail-<?= $username ?>" class="detail-box mt-3 bg-gray-700 rounded hidden">
            <div class="overflow-x-auto">
                <pre class="text-green-300 font-mono text-sm whitespace-pre p-3 min-w-full"><?= htmlspecialchars($content) ?></pre>
            </div>
        </div>

        <form method="POST" id="form-<?= $username ?>" class="mt-3 hidden bg-gray-700 p-4 rounded">
            <input type="hidden" name="edit_user" value="<?= htmlspecialchars($username) ?>">
            <label class="block mb-1">Masukkan tanggal expired baru (atau jumlah hari untuk perpanjang)</label>
            <input type="text" name="expired" required class="w-full p-2 rounded bg-gray-600 mb-2 text-white">
            <button type="submit" class="bg-green-600 px-4 py-2 rounded hover:bg-green-700">Simpan</button>
        </form>
    </div>
<?php endforeach; ?>
    <?php endif; ?>
</div>
<script>
function toggleDetail(id) {
    const box = document.getElementById('detail-' + id);
    const btn = document.getElementById('btn-' + id);
    document.querySelectorAll('.detail-box').forEach(b => b.classList.add('hidden'));
    document.querySelectorAll('.btn-show').forEach(b => b.innerText = 'Show');
    if (box.classList.contains('hidden')) {
        box.classList.remove('hidden');
        btn.innerText = 'Hide';
    } else {
        btn.innerText = 'Show';
    }
}
</script>
</body>
</html>

