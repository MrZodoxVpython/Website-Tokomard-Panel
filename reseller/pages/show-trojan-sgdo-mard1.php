<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$reseller = $_SESSION['reseller'] ?? $_SESSION['username'] ?? 'unknown';

$remoteIP = '152.42.182.187';
$sshUser = 'root';
$remotePath = "/etc/xray/data-panel/reseller";
$sshPrefix = "ssh -o ConnectTimeout=5 -o StrictHostKeyChecking=no $sshUser@$remoteIP";

$cmdListFiles = "$sshPrefix 'ls $remotePath/akun-$reseller-*.txt 2>/dev/null'";
$fileListRaw = shell_exec($cmdListFiles);
$fileList = array_filter(explode("\n", trim($fileListRaw)));

$configPath = '/etc/xray/config.json';
$logDir = "/etc/xray/data-panel/reseller";

// Handle POST Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $userEdit = $_POST['edit_user'];
    $expiredInput = trim($_POST['expired']);
    $expiredBaru = null;

    if (preg_match('/^\\d+$/', $expiredInput)) {
        $expiredBaru = date('Y-m-d', strtotime("+$expiredInput days"));
    } elseif (preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $expiredInput)) {
        $expiredBaru = $expiredInput;
    }

    if ($expiredBaru) {
        $lines = file($configPath);
        $currentTag = '';
        foreach ($lines as $i => $line) {
            if (preg_match('/^\\s*#(trojan)(grpc|ws)?$/i', trim($line), $m)) {
                $currentTag = '#' . strtolower($m[1] . ($m[2] ?? ''));
            }
            if (in_array($currentTag, ['#trojanws', '#trojangrpc'])) {
                if (preg_match('/^\\s*(###|#!|#&|#\\$)\\s+' . preg_quote($userEdit, '/') . '\\s+\\d{4}-\\d{2}-\\d{2}/', $line, $matches)) {
                    $prefix = $matches[1];
                    $lines[$i] = "$prefix $userEdit $expiredBaru\n";
                }
            }
        }
        file_put_contents($configPath, implode('', $lines));

        foreach (glob("$logDir/akun-$reseller-$userEdit.txt") as $file) {
            $content = file_get_contents($file);
            $content = preg_replace('/(Expired On\\s*:\\s*)(\\d{4}-\\d{2}-\\d{2})/', '${1}' . $expiredBaru, $content);
            file_put_contents($file, $content);
        }

        shell_exec('sudo /usr/local/bin/restart-xray.sh');
    }

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
            ⚠ Belum ada daftar akun untuk reseller <strong><?= htmlspecialchars($reseller) ?></strong>
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
            <div class="flex justify-between items-center flex-wrap gap-2">
                <div class="text-blue-400 font-semibold text-lg"><?= htmlspecialchars($username) ?></div>
                <div class="flex gap-2 flex-wrap">
                    <button onclick="toggleDetail('<?= $username ?>')" id="btn-<?= $username ?>" class="bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded text-white">Show</button>

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

                    <button onclick="toggleEdit('<?= $username ?>')" class="bg-green-700 hover:bg-green-800 px-3 py-1 rounded text-white text-sm">Edit</button>
                </div>
            </div>

            <!-- Detail -->
            <div id="detail-<?= $username ?>" class="hidden mt-4 detail-box">
                <div class="overflow-x-auto bg-gray-700 rounded p-3">
                    <pre class="text-sm text-green-300 whitespace-pre-wrap"><?= htmlspecialchars($content ?: "❌ Gagal membaca isi file.") ?></pre>
                </div>
            </div>
        <!-- Form Edit Expired -->
<div id="edit-<?= $username ?>" class="hidden mt-2">
    <form method="POST" class="flex items-center gap-2 flex-wrap" onsubmit="return confirm('Yakin ubah masa aktif akun ini?')">
        <input type="hidden" name="edit_user" value="<?= htmlspecialchars($username) ?>">
        <input type="text" name="expired" placeholder="tgl / jumlah hari" class="px-2 py-1 text-sm rounded text-black bg-white w-40" required>
        <button class="bg-green-600 hover:bg-green-700 px-3 py-1 rounded text-white text-sm">Simpan</button>
    </form>
</div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
    function toggleDetail(id) {
        const detail = document.getElementById('detail-' + id);
        const btn = document.getElementById('btn-' + id);
        if (detail.classList.contains('hidden')) {
            detail.classList.remove('hidden');
            btn.textContent = 'Hide';
        } else {
            detail.classList.add('hidden');
            btn.textContent = 'Show';
        }
    }

    function toggleEdit(id) {
        const editBox = document.getElementById('edit-' + id);
        editBox.classList.toggle('hidden');
    }
</script>

</body>
</html>

