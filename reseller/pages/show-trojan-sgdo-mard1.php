<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$reseller = $_SESSION['reseller'] ?? $_SESSION['username'] ?? 'unknown';

$remoteIP = '152.42.182.187'; // IP SGDO-MARD
$sshUser = 'root';
$remotePath = "/etc/xray/data-panel/reseller";
$sshListCmd = "ls $remotePath/akun-$reseller-*.txt 2>/dev/null";
$fileList = explode("\n", trim(shell_exec("ssh -o StrictHostKeyChecking=no $sshUser@$remoteIP \"$sshListCmd\"")));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Akun Trojan - SGDO-MARD</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white p-6">
<div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold mb-4">Daftar Akun Trojan (SGDO-MARD) - <?= htmlspecialchars($reseller) ?></h1>

    <?php if (empty($fileList) || $fileList[0] === '') : ?>
        <div class="text-yellow-400">Belum ada akun.</div>
    <?php endif; ?>

    <?php foreach ($fileList as $remoteFile):
        $filename = basename($remoteFile);
        preg_match('/akun-' . preg_quote($reseller, '/') . '-(.+)\.txt/', $filename, $m);
        $username = $m[1] ?? 'unknown';
        $sshCatCmd = "cat $remoteFile";
        $content = shell_exec("ssh -o StrictHostKeyChecking=no $sshUser@$remoteIP \"$sshCatCmd\"");
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
                    <pre class="text-green-300 font-mono text-sm whitespace-pre p-3 min-w-full"><?= htmlspecialchars($content ?: "Gagal membaca isi file.") ?></pre>
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

