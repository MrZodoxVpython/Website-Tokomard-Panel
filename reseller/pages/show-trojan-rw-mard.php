<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$reseller = $_SESSION['reseller'] ?? $_SESSION['username'] ?? 'unknown';

$remoteIP = '203.194.113.140';
$sshUser = 'root';
$remotePath = "/etc/xray/data-panel/reseller";
$sshPrefix = "ssh -o ConnectTimeout=5 -o StrictHostKeyChecking=no $sshUser@$remoteIP";

// === Proses Aksi ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['username'] ?? $_POST['edit_user'] ?? '');
    $escapedFile = escapeshellarg("$remotePath/akun-$reseller-$username.txt");

    if (isset($_POST['aksi']) && $_POST['aksi'] === 'stop') {
        $cmd = "$sshPrefix \"grep -q 'DISABLED' $escapedFile || echo 'DISABLED' >> $escapedFile\"";
        shell_exec($cmd);
    } elseif (isset($_POST['aksi']) && $_POST['aksi'] === 'delete') {
        $cmd = "$sshPrefix 'rm -f $escapedFile'";
        shell_exec($cmd);
    } elseif (isset($_POST['edit_user'])) {
        $expiredInput = trim($_POST['expired'] ?? '');
        if (preg_match('/^\d+$/', $expiredInput)) {
            // Tambah hari dari tanggal sekarang
            $newExpired = date('Y-m-d', strtotime("+$expiredInput days"));
        } else {
            // Gunakan langsung input jika format sudah yyyy-mm-dd
            $newExpired = preg_replace('/[^0-9\-]/', '', $expiredInput);
        }
        $cmd = "$sshPrefix \"sed -i 's/^Expired On.*/Expired On: $newExpired/' $escapedFile\"";
        shell_exec($cmd);
    }

    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Ambil file akun
$cmdListFiles = "$sshPrefix 'ls $remotePath/akun-$reseller-*.txt 2>/dev/null'";
$fileListRaw = shell_exec($cmdListFiles);
$fileList = array_filter(explode("\n", trim($fileListRaw ?? '')));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Trojan RW-MARD</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen p-6">
<div class="max-w-5xl mx-auto">
    <h1 class="text-2xl font-bold text-center mb-6">Daftar Akun Trojan - <?= htmlspecialchars($reseller) ?></h1>

    <?php if (empty($fileList)): ?>
        <div class="bg-yellow-600/20 text-yellow-300 p-4 rounded text-center">
            ⚠ Belum ada akun untuk reseller <strong><?= htmlspecialchars($reseller) ?></strong>.
        </div>
    <?php else: ?>
        <?php foreach ($fileList as $remoteFile):
            $filename = basename($remoteFile);
            preg_match('/akun-' . preg_quote($reseller, '/') . '-(.+)\.txt/', $filename, $m);
            $username = $m[1] ?? 'unknown';
            $escapedFile = escapeshellarg($remoteFile);
            $sshCatCmd = "$sshPrefix 'cat $escapedFile'";
            $content = trim(shell_exec($sshCatCmd));
        ?>
        <div class="bg-gray-800 rounded p-4 shadow mb-6">
            <div class="flex justify-between items-center flex-wrap">
                <div class="text-blue-400 font-semibold text-lg"><?= htmlspecialchars($username) ?></div>
                <div class="flex flex-wrap gap-2 mt-2 sm:mt-0">

                    <!-- SHOW -->
                    <button onclick="toggleDetail('<?= $username ?>')" id="btn-<?= $username ?>" class="btn-show bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded">Show</button>

                    <!-- STOP -->
                    <form method="POST" class="inline">
                        <input type="hidden" name="aksi" value="stop">
                        <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
                        <button class="bg-yellow-600 hover:bg-yellow-700 px-3 py-1 rounded">Stop</button>
                    </form>

                    <!-- DELETE -->
                    <form method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus akun <?= $username ?>?')">
                        <input type="hidden" name="aksi" value="delete">
                        <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
                        <button class="bg-red-600 hover:bg-red-700 px-3 py-1 rounded">Delete</button>
                    </form>

                    <!-- EDIT -->
                    <button onclick="document.getElementById('form-<?= $username ?>').classList.toggle('hidden')" class="bg-green-600 px-3 py-1 rounded hover:bg-green-700">Edit</button>
                </div>
            </div>

            <!-- DETAIL -->
            <div id="detail-<?= $username ?>" class="detail-box mt-3 bg-gray-700 rounded hidden">
                <div class="overflow-x-auto">
                    <pre class="text-green-300 font-mono text-sm whitespace-pre p-3 min-w-full"><?= htmlspecialchars($content ?: "❌ Gagal membaca isi file.") ?></pre>
                </div>
            </div>

            <!-- FORM EDIT -->
            <form method="POST" id="form-<?= $username ?>" class="mt-3 hidden bg-gray-700 p-4 rounded">
                <input type="hidden" name="edit_user" value="<?= htmlspecialchars($username) ?>">
                <label class="block mb-1">Masukkan tanggal expired baru (yyyy-mm-dd) atau jumlah hari (misal: 5):</label>
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

