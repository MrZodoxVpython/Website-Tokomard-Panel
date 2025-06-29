<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$reseller = $_SESSION['reseller'] ?? $_SESSION['username'] ?? 'unknown';

$remoteIP = '203.194.113.140'; // IP VPS remote
$sshUser = 'root';
$remotePath = "/etc/xray/data-panel/reseller";
$sshPrefix = "ssh -o ConnectTimeout=5 -o StrictHostKeyChecking=no $sshUser@$remoteIP";

$cmdListFiles = "$sshPrefix 'ls $remotePath/akun-$reseller-*.txt 2>/dev/null'";
$fileListRaw = shell_exec($cmdListFiles);
$fileList = array_filter(explode("\n", trim($fileListRaw)));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Trojan - RW-MARD</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen p-6">
<div class="max-w-5xl mx-auto">
    <h1 class="text-2xl font-bold text-center mb-6">Daftar Akun Trojan (RW-MARD) - <?= htmlspecialchars($reseller) ?></h1>

    <?php if (empty($fileList)) : ?>
        <div class="text-center bg-yellow-500/10 border border-yellow-400 text-yellow-300 p-4 rounded">
            ⚠ Belum ada daftar akun untuk reseller <strong><?= htmlspecialchars($reseller) ?></strong>,
            silakan buat akun terlebih dahulu.
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
        <div class="bg-gray-800 rounded shadow mb-6 p-4">
            <div class="flex justify-between items-center flex-wrap">
                <div class="text-blue-400 font-semibold text-lg"><?= htmlspecialchars($username) ?></div>
                <div class="flex gap-2 mt-2 sm:mt-0">
                    <button onclick="toggleDetail('<?= $username ?>')" id="btn-<?= $username ?>" class="toggle-btn bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded text-white">Show</button>

                    <form method="POST" action="aksi-trojan.php" class="inline">
                        <input type="hidden" name="aksi" value="stop">
                        <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
                        <input type="hidden" name="reseller" value="<?= htmlspecialchars($reseller) ?>">
                        <input type="hidden" name="vps" value="rw-mard">
                        <button class="bg-yellow-600 hover:bg-yellow-700 px-3 py-1 rounded">Stop</button>
                    </form>

                    <form method="POST" action="aksi-trojan.php" class="inline" onsubmit="return confirm('Yakin ingin menghapus akun ini?')">
                        <input type="hidden" name="aksi" value="delete">
                        <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
                        <input type="hidden" name="reseller" value="<?= htmlspecialchars($reseller) ?>">
                        <input type="hidden" name="vps" value="rw-mard">
                        <button class="bg-red-600 hover:bg-red-700 px-3 py-1 rounded">Delete</button>
                    </form>

                    <form method="GET" action="edit-akun.php" class="inline">
                        <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
                        <input type="hidden" name="reseller" value="<?= htmlspecialchars($reseller) ?>">
                        <input type="hidden" name="vps" value="rw-mard">
                        <button class="bg-green-600 hover:bg-green-700 px-3 py-1 rounded">Edit</button>
                    </form>
                </div>
            </div>
            <div id="detail-<?= $username ?>" class="hidden mt-4">
                <div class="overflow-x-auto bg-gray-700 rounded p-3">
                    <pre class="text-sm text-green-300 whitespace-pre-wrap"><?= htmlspecialchars($content ?: "❌ Gagal membaca isi file atau file kosong.") ?></pre>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function toggleDetail(id) {
    const detailBox = document.getElementById('detail-' + id);
    const btn = document.getElementById('btn-' + id);
    const isHidden = detailBox.classList.contains('hidden');
    
    // Sembunyikan semua detail & reset semua tombol
    document.querySelectorAll('[id^="detail-"]').forEach(e => e.classList.add('hidden'));
    document.querySelectorAll('.toggle-btn').forEach(b => b.textContent = 'Show');

    if (isHidden) {
        detailBox.classList.remove('hidden');
        btn.textContent = 'Hide';
    }
}
</script>
</body>
</html>

