<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

$reseller = $_SESSION['reseller'] ?? $_SESSION['username'] ?? '';
if (empty($reseller)) {
    die("❌ Reseller tidak ditemukan dalam session.");
}

$remoteIP = '203.194.113.140';
$sshUser = 'root';
$sshPrefix = "ssh -o ConnectTimeout=5 -o StrictHostKeyChecking=no $sshUser@$remoteIP";
$configPath = '/etc/xray/config.json';
$remotePath = "/etc/xray/data-panel/reseller";

// === Aksi Delete / Start/Stop / Edit ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['hapus'])) {
    $cmds = [];

    // DELETE (GET)
    if (isset($_GET['hapus'])) {
        $u = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['hapus']);
        $cmds[] = "$sshPrefix \"sed -i '/\\\s$u /d' $configPath\"";
        $cmds[] = "$sshPrefix \"rm -f $remotePath/akun-$reseller-$u.txt\"";
        $cmds[] = "$sshPrefix 'systemctl restart xray'";
	
	foreach ($cmds as $cmd) {
            shell_exec($cmd);
        }

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
        }

    // POST
    $user = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['username'] ?? $_POST['edit_user'] ?? '');
    $pathUser = "$remotePath/akun-$reseller-$user.txt";
    $escapedUser = preg_quote($user, '/');

    // START/STOP
    if (isset($_POST['toggle_user']) && isset($_POST['action'])) {
        $action = $_POST['action'];
        // Ambil config dan jalankan sed via SSH
        if ($action === 'stop') {
            $cmds[] = "$sshPrefix \"sed -i '/^\\s*(###|#!|#&|#\\\$) $escapedUser / {N; s/\"password\": \\\"[^\"]*\\\"/\\\"locked\\\"/}' $configPath\"";
        } else {
            $cmds[] = "$sshPrefix \"sed -i '/##LOCK##/b; /##LOCK##/!{ s/\\\"password\\\": \\\"locked\\\"/\\\"password\\\": \\\"\\\"/ }' $configPath\""; // simplistic unlock
        }
        $cmds[] = "$sshPrefix 'systemctl restart xray'";
    }

    // EDIT expired
// EDIT expired
if (isset($_POST['edit_user'])) {
    $expiredInput = trim($_POST['expired']);
    if (preg_match('/^\d+$/', $expiredInput)) {
    // Ambil tanggal expired sebelumnya dari file akun
    $rawDetail = shell_exec("$sshPrefix \"grep '^Expired On:' $pathUser | cut -d':' -f2- | xargs\"");
    $prevDate = trim($rawDetail);

    // Jika tidak ditemukan, fallback ke hari ini
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $prevDate)) {
        $prevDate = date('Y-m-d');
    }

    // Tambahkan hari ke tanggal sebelumnya
    $expired = date('Y-m-d', strtotime("+$expiredInput days", strtotime($prevDate)));
    } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiredInput)) {
        $expired = $expiredInput;
    } else {
        die("❌ Format tanggal salah.");
    }

    $escapedUser = preg_quote($user, '/');
    
    // Ganti baris komentar akun: #! username YYYY-MM-DD
    $cmds[] = "$sshPrefix \"sed -i 's|^.*#! *$escapedUser *[0-9]\\{4\\}-[0-9]\\{2\\}-[0-9]\\{2\\}|#! $user $expired|' $configPath\"";

    //$cmds[] = "$sshPrefix \"sed -i 's|^#! $escapedUser .*|#! $user $expired|g' $configPath\"";

    // Update di file akun reseller
    $cmds[] = "$sshPrefix \"sed -i 's/^Expired On:.*/Expired On: $expired/' $pathUser\"";

    // Restart xray
    $cmds[] = "$sshPrefix 'systemctl restart xray'";
}

    // Kirim semua perintah
    foreach ($cmds as $c) {
        shell_exec($c);
    }

    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// === Ambil Daftar Akun via SSH ===
$listCmd = "$sshPrefix \"ls $remotePath/akun-$reseller-*.txt 2>/dev/null\"";
$fileListRaw = shell_exec($listCmd);
$files = array_filter(explode("\n", trim($fileListRaw ?? '')));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Trojan RW‑MARD</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen p-6">
<div class="max-w-5xl mx-auto">
    <h1 class="text-center text-2xl font-bold mb-6">Daftar Trojan (RW‑MARD) – <?=htmlspecialchars($reseller)?></h1>

    <?php if (empty($files)): ?>
        <div class="bg-yellow-600/20 text-yellow-300 p-4 rounded text-center">
            ⚠ Belum ada daftar akun untuk reseller <strong><?=htmlspecialchars($reseller)?></strong> silahkan buat akun terlebih dahulu.
        </div>
    <?php else: ?>
        <?php foreach ($files as $remoteFile):
            $fn = basename($remoteFile);
            preg_match("/akun-".preg_quote($reseller,"/")."-(.+)\.txt/", $fn, $m);
            $u = $m[1] ?? 'unknown';
            $content = trim(shell_exec("$sshPrefix \"cat ".escapeshellarg($remoteFile)."\""));
            // Check disabled via config checking? Simplified: jika ada kata "locked" di detail
            $isDisabled = strpos($content, 'DISABLED') !== false;
        ?>
        <div class="bg-gray-800 rounded p-4 shadow mb-4">
            <div class="flex justify-between items-center flex-wrap">
                <div class="text-lg font-semibold"><?=htmlspecialchars($u)?></div>
                <div class="space-x-2 mt-2 sm:mt-0">
                    <!-- Show -->
                    <button id="btn-<?=$u?>" onclick="toggleDetail('<?=$u?>')" class="btn-show bg-blue-600 px-3 py-1 rounded hover:bg-blue-700">Show</button>

                    <!-- Start/Stop -->
                    <form method="POST" class="inline">
                        <input type="hidden" name="toggle_user" value="<?=$u?>">
                        <input type="hidden" name="action" value="<?=$isDisabled?'start':'stop'?>">
                        <button type="submit" class="<?= $isDisabled?'bg-green-600':'bg-yellow-600' ?> px-3 py-1 rounded hover:bg-opacity-90"><?= $isDisabled?'Start':'Stop'?></button>
                    </form>

                    <!-- Delete -->
                    <a href="?hapus=<?=$u?>" onclick="return confirm('Yakin ingin menghapus akun <?=$u?>?')" class="bg-red-600 px-3 py-1 rounded hover:bg-red-700">Delete</a>

                    <!-- Edit -->
                    <button onclick="document.getElementById('form-<?=$u?>').classList.toggle('hidden')" class="bg-green-600 px-3 py-1 rounded hover:bg-green-700">Edit</button>
                </div>
            </div>

            <div id="detail-<?=$u?>" class="detail-box mt-3 bg-gray-700 rounded hidden">
                <pre class="p-3 text-green-300 font-mono text-sm whitespace-pre-wrap"><?=htmlspecialchars($content)?></pre>
            </div>

            <form method="POST" id="form-<?=$u?>" class="mt-3 hidden bg-gray-700 p-4 rounded">
                <input type="hidden" name="edit_user" value="<?=$u?>">
                <label class="block mb-2">Masukkan tanggal expired baru (yyyy-mm-dd) atau jumlah hari:</label>
                <input type="text" name="expired" required class="w-full p-2 rounded bg-gray-600 text-white mb-3">
                <button type="submit" class="bg-green-600 px-4 py-2 rounded hover:bg-green-700">Simpan</button>
            </form>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function toggleDetail(id) {
    const d = document.getElementById('detail-'+id);
    const b = document.getElementById('btn-'+id);
    const all = document.querySelectorAll('.detail-box');
    const allBtn = document.querySelectorAll('.btn-show');
    all.forEach(x=>x.classList.add('hidden'));
    allBtn.forEach(x=>x.innerText='Show');
    if (d.classList.contains('hidden')) {
        d.classList.remove('hidden');
        b.innerText='Hide';
    } else {
        d.classList.add('hidden');
        b.innerText='Show';
    }
}
</script>
</body>
</html>

