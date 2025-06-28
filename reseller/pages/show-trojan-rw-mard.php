<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$reseller = $_SESSION['reseller'] ?? $_SESSION['username'] ?? 'unknown';

$remoteIP = '203.194.113.140'; // IP VPS rw-mard
$sshUser = 'root';
$remotePath = "/etc/xray/data-panel/reseller";
$sshPrefix = "ssh -o ConnectTimeout=5 -o StrictHostKeyChecking=no $sshUser@$remoteIP";

// Ambil daftar file .txt akun milik reseller
$cmdListFiles = "$sshPrefix 'ls $remotePath/akun-$reseller-*.txt 2>/dev/null'";
$fileListRaw = shell_exec($cmdListFiles);
$fileList = array_filter(explode("\n", trim($fileListRaw)));

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Akun Trojan - RW-MARD</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white p-6">
<div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold mb-4">Daftar Akun Trojan (RW-MARD) - <?= htmlspecialchars($reseller) ?></h1>

    <?php if (empty($fileList)) : ?>
        <div class="bg-yellow-500/10 border border-yellow-400 text-yellow-300 p-4 rounded">⚠️ Belum ada akun yang dibuat atau koneksi SSH gagal.</div>
    <?php endif; ?>

    <?php foreach ($fileList as $remoteFile):
        $filename = basename($remoteFile);
        preg_match('/akun-' . preg_quote($reseller, '/') . '-(.+)\.txt/', $filename, $m);
        $username = $m[1] ?? 'unknown';

        // Ambil isi file via SSH
        $escapedFile = escapeshellarg($remoteFile);
        $sshCatCmd = "$sshPrefix 'cat $escapedFile'";
        $content = shell_exec($sshCatCmd);
    ?>
        <div class="bg-gray-800 p-4 rounded mb-4 shadow">
            <div class="flex justify-between items-center">
                <div class="text-lg font-semibold"><?= htmlspecialchars($username) ?></div>
                <div>
                    <button id="btn-<?= $username ?>" onclick="toggleDetail('<?= $username ?>')" class="btn-show bg-blue-600 px-3 py-1 rounded hover:bg-blue-700">Show</button>
                </div>
            </div>

            <div id="detail-<?= $username ?>" class="detail-box mt-3 bg-gray-700 rounded hidden">
                <div class="overflow-x-auto">
                    <pre class="text-green-300 font-mono text-sm whitespace-pre p-3 min-w-full"><?= htmlspecialchars($content ?: "❌ Gagal membaca isi file atau file kosong.") ?></pre>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
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

