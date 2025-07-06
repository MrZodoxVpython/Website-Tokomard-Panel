<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$reseller = $_SESSION['reseller'] ?? $_SESSION['username'] ?? 'unknown';

$remoteIP = '152.42.182.187';
$sshUser = 'root';
$remotePath = "/etc/xray/data-panel/reseller";
$sshPrefix = "ssh -o ConnectTimeout=5 -o StrictHostKeyChecking=no $sshUser@$remoteIP";
$scpPrefix = "scp -o ConnectTimeout=5 -o StrictHostKeyChecking=no";

$configPath = '/etc/xray/config.json';

// Ambil semua file akun remote reseller
$cmdListFiles = "$sshPrefix 'ls $remotePath/akun-$reseller-*.txt 2>/dev/null'";
$fileListRaw = shell_exec($cmdListFiles);
$fileList = array_filter(explode("\n", trim($fileListRaw ?? '')));

// Handle POST Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $userEdit = $_POST['edit_user'];
    $expiredInput = trim($_POST['expired']);
    $expiredBaru = null;

    // Ambil tanggal expired saat ini dari remote file
    $remoteAkunPath = "$remotePath/akun-$reseller-$userEdit.txt";
    $sshGetExpired = "$sshPrefix \"grep 'Expired On' $remoteAkunPath | awk -F ':' '{print \$2}' | xargs\"";
    $expiredSaatIni = trim(shell_exec($sshGetExpired)); // format: YYYY-MM-DD

    if (!$expiredSaatIni || !preg_match('/\d{4}-\d{2}-\d{2}/', $expiredSaatIni)) {
        $expiredSaatIni = date('Y-m-d'); // fallback jika gagal
    }

    // Hitung tanggal baru
    if (preg_match('/^\d+$/', $expiredInput)) {
        $expiredBaru = date('Y-m-d', strtotime("$expiredSaatIni +$expiredInput days"));
    } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiredInput)) {
        $expiredBaru = $expiredInput;
    }

    if ($expiredBaru) {
        // 🔁 Ubah tanggal expired di config.json lokal
        $lines = file($configPath);
        $currentTag = '';
        foreach ($lines as $i => $line) {
            if (preg_match('/^\s*#(trojan)(grpc|ws)?$/i', trim($line), $m)) {
                $currentTag = '#' . strtolower($m[1] . ($m[2] ?? ''));
            }
            if (in_array($currentTag, ['#trojanws', '#trojangrpc'])) {
               if (preg_match('/^\s*(###|#!|#&|#\$)\s+' . preg_quote($userEdit, '/') . '\s+\d{4}-\d{2}-\d{2}/', $line, $matches)) {
                   $prefix = $matches[1];
                   $lines[$i] = "$prefix $userEdit $expiredBaru\n";
               }
            }
        }
        file_put_contents($configPath, implode('', $lines));

        // 🔁 Ubah Expired On di file akun remote
        $cmdEditRemoteFile = "$sshPrefix \"sed -i 's/\\(Expired On\\s*:\\s*\\).*/\\1$expiredBaru/' '$remoteAkunPath'\"";
        shell_exec($cmdEditRemoteFile);

        // 🔁 Restart xray
        shell_exec('sudo /etc/xray/restart-xray.sh');

        header("Location: show-trojan-sgdo-mard1.php");
        exit;
    }
}

// Handle DELETE akun
if (isset($_GET['hapus'])) {
    $hapusUser = basename($_GET['hapus']);
    $remoteFileToDelete = "$remotePath/akun-$reseller-$hapusUser.txt";

    // 🔁 Hapus file remote
    shell_exec("$sshPrefix 'rm -f $remoteFileToDelete'");

    // 🔁 Hapus baris dari config.json
    $lines = file($configPath);
    $currentTag = '';
    $newLines = [];
    foreach ($lines as $line) {
        if (preg_match('/^\s*#(trojan)(grpc|ws)?$/i', trim($line), $m)) {
            $currentTag = '#' . strtolower($m[1] . ($m[2] ?? ''));
        }

        if (
            in_array($currentTag, ['#trojanws', '#trojangrpc']) &&
            preg_match('/^\s*(###|#!|#&|#\$)\s+' . preg_quote($hapusUser, '/') . '\s+\d{4}-\d{2}-\d{2}/', $line)
        ) {
            continue; // skip baris ini
        }

        $newLines[] = $line;
    }
    file_put_contents($configPath, implode('', $newLines));

    // 🔁 Restart xray
    shell_exec('sudo /etc/xray/restart-xray.sh');

    header("Location: show-trojan-sgdo-mard1.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Trojan - SGDO-MARD1</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen p-6">
<div class="max-w-5xl mx-auto">
    <h1 class="text-2xl font-bold text-center mb-6">Daftar Akun Trojan (SGDO-MARD1) - <?= htmlspecialchars($reseller) ?></h1>

    <?php if (empty($fileList)) : ?>
        <div class="text-center bg-yellow-500/10 border border-yellow-400 text-yellow-300 p-4 rounded">
            ⚠ Belum ada daftar akun untuk reseller <strong><?= htmlspecialchars($reseller) ?></strong> silakan buat akun terlebih dahulu. 
        </div>
    <?php else: ?>
        <?php foreach ($fileList as $remoteFile):
            $filename = basename($remoteFile);
            preg_match('/akun-' . preg_quote($reseller, '/') . '-(.+)\.txt/', $filename, $m);
            $username = $m[1] ?? 'unknown';
            $escapedFile = escapeshellarg($remoteFile);
            $sshCatCmd = "$sshPrefix 'cat $escapedFile'";
            $content = trim(shell_exec($sshCatCmd));
            $isDisabled = strpos($content, "#& $username") !== false;
        ?>
        <div class="bg-gray-800 rounded shadow mb-6 p-4">
            <div class="flex justify-between items-center flex-wrap gap-2">
                <div class="text-blue-400 font-semibold text-lg"><?= htmlspecialchars($username) ?></div>
                <div class="flex gap-2 flex-wrap">
                    <button onclick="toggleDetail('<?= $username ?>')" id="btn-<?= $username ?>" class="btn-show bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded text-white">Show</button>

                    <form method="POST" class="inline">
                        <input type="hidden" name="toggle_user" value="<?= htmlspecialchars($username) ?>">
                        <input type="hidden" name="action" value="<?= $isDisabled ? 'start' : 'stop' ?>">
                        <button type="submit" class="<?= $isDisabled ? 'bg-green-600 hover:bg-green-700' : 'bg-yellow-600 hover:bg-yellow-700' ?> px-3 py-1 rounded">
                            <?= $isDisabled ? 'Start' : 'Stop' ?>
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
                <label class="block mb-1">Perbarui Expired (tgl atau jumlah hari)</label>
                <input type="text" name="expired" required class="w-full p-2 rounded bg-gray-600 mb-2 text-white">
                <button type="submit" class="bg-green-600 px-4 py-2 rounded hover:bg-green-700">Simpan</button>
            </form>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function toggleDetail(id) {
    const targetBox = document.getElementById('detail-' + id);
    const targetBtn = document.getElementById('btn-' + id);
    const allBoxes = document.querySelectorAll('.detail-box');
    const allButtons = document.querySelectorAll('.btn-show');

    if (!targetBox.classList.contains('hidden')) {
        targetBox.classList.add('hidden');
        targetBtn.innerText = 'Show';
        return;
    }

    allBoxes.forEach(box => box.classList.add('hidden'));
    allButtons.forEach(btn => btn.innerText = 'Show');

    targetBox.classList.remove('hidden');
    targetBtn.innerText = 'Hide';
}
</script>
</body>
</html>

