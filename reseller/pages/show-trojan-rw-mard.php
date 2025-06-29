<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$reseller = $_SESSION['reseller'] ?? $_SESSION['username'] ?? 'unknown';

$remoteIP = '203.194.113.140'; // IP VPS remote (rw-mard)
$sshUser = 'root';
$remotePath = "/etc/xray/data-panel/reseller";
$sshPrefix = "ssh -o ConnectTimeout=5 -o StrictHostKeyChecking=no $sshUser@$remoteIP";

$cmdListFiles = "$sshPrefix 'ls $remotePath/akun-$reseller-*.txt 2>/dev/null'";
$fileListRaw = shell_exec($cmdListFiles);
$akunFiles = array_filter(explode("\n", trim($fileListRaw)));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Akun Trojan RW-MARD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .detail-box pre {
            z-index: 0;
            position: relative;
        }
        button, form {
            z-index: 10;
            position: relative;
        }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen p-6">
<div class="max-w-4xl mx-auto">
    <h1 class="text-center text-2xl font-bold mb-4">Daftar Akun Trojan (RW-MARD) - <?= htmlspecialchars($reseller) ?></h1>

    <?php if (empty($akunFiles)) : ?>
        <div class="text-center bg-yellow-500/10 border border-yellow-400 text-yellow-300 p-4 rounded">
            ⚠ Belum ada daftar akun untuk reseller <strong><?= htmlspecialchars($reseller) ?></strong>,
            silakan buat akun terlebih dahulu.
        </div>
    <?php else: ?>
        <?php foreach ($akunFiles as $remoteFile):
            $filename = basename($remoteFile);
            preg_match('/akun-' . preg_quote($reseller, '/') . '-(.+)\.txt/', $filename, $m);
            $username = $m[1] ?? 'unknown';
            $escapedFile = escapeshellarg($remoteFile);
            $sshCatCmd = "$sshPrefix 'cat $escapedFile'";
            $content = trim(shell_exec($sshCatCmd));
        ?>
        <div class="bg-gray-800 p-4 rounded mb-4 shadow relative z-0">
            <div class="flex justify-between items-center z-10 relative">
                <div class="text-lg font-semibold text-blue-300"><?= htmlspecialchars($username) ?></div>
                <div class="space-x-2">
                    <button id="btn-<?= $username ?>" onclick="toggleDetail('<?= $username ?>')" class="btn-show bg-blue-600 px-3 py-1 rounded hover:bg-blue-700 z-10">Show</button>

                    <form action="aksi-trojan.php" method="POST" class="inline z-10">
                        <input type="hidden" name="aksi" value="stop">
                        <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
                        <input type="hidden" name="reseller" value="<?= htmlspecialchars($reseller) ?>">
                        <input type="hidden" name="vps" value="rw-mard">
                        <button type="submit" class="bg-yellow-600 px-3 py-1 rounded hover:bg-yellow-700 z-10">Stop</button>
                    </form>

                    <form action="aksi-trojan.php" method="POST" class="inline z-10" onsubmit="return confirm('Yakin ingin menghapus akun ini?')">
                        <input type="hidden" name="aksi" value="delete">
                        <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
                        <input type="hidden" name="reseller" value="<?= htmlspecialchars($reseller) ?>">
                        <input type="hidden" name="vps" value="rw-mard">
                        <button type="submit" class="bg-red-600 px-3 py-1 rounded hover:bg-red-700 z-10">Delete</button>
                    </form>

                    <form action="edit-akun.php" method="GET" class="inline z-10">
                        <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
                        <input type="hidden" name="reseller" value="<?= htmlspecialchars($reseller) ?>">
                        <input type="hidden" name="vps" value="rw-mard">
                        <button type="submit" class="bg-green-600 px-3 py-1 rounded hover:bg-green-700 z-10">Edit</button>
                    </form>
                </div>
            </div>
            <div id="detail-<?= $username ?>" class="detail-box mt-3 bg-gray-700 rounded hidden overflow-hidden">
                <div class="overflow-x-auto">
                    <pre class="text-green-300 font-mono text-sm whitespace-pre p-3 min-w-full"><?= htmlspecialchars($content ?: "❌ Gagal membaca isi file atau file kosong.") ?></pre>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function toggleDetail(id) {
    const box = document.getElementById('detail-' + id);
    const btn = document.getElementById('btn-' + id);
    const allBoxes = document.querySelectorAll('.detail-box');
    const allBtns = document.querySelectorAll('.btn-show');

    allBoxes.forEach(b => b.classList.add('hidden'));
    allBtns.forEach(b => b.innerText = 'Show');

    if (box.classList.contains('hidden')) {
        box.classList.remove('hidden');
        btn.innerText = 'Hide';
    } else {
        box.classList.add('hidden');
        btn.innerText = 'Show';
    }
}
</script>
</body>
</html>

