<?php
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// Logging awal
file_put_contents("debug.log", "==== " . date("Y-m-d H:i:s") . " ====\n", FILE_APPEND);
file_put_contents("debug.log", "METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);
file_put_contents("debug.log", "POST:\n" . print_r($_POST, true), FILE_APPEND);
file_put_contents("debug.log", "GET:\n" . print_r($_GET, true), FILE_APPEND);

$reseller = $_SESSION['reseller'] ?? $_SESSION['username'] ?? '';
if (empty($reseller)) {
    die("❌ Reseller tidak ditemukan dalam session.");
}

$remoteIP = '203.194.113.140';
$sshUser = 'root';
$sshPrefix = "ssh -o ConnectTimeout=5 -o StrictHostKeyChecking=no $sshUser@$remoteIP";
$configPath = '/etc/xray/config.json';
$remotePath = "/etc/xray/data-panel/reseller";

// Aksi POST atau GET (hapus)
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['hapus'])) {
    $cmds = [];

    // DELETE
    if (isset($_GET['hapus'])) {
        $u = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['hapus']);
        $cmds[] = "$sshPrefix \"sed -i '/\\s$u /{N;/\\n##LOCK##/N;d}' $configPath\"";
        $cmds[] = "$sshPrefix \"rm -f $remotePath/akun-$reseller-$u.txt\"";
        $cmds[] = "$sshPrefix 'systemctl restart xray'";
    }

    // START/STOP
    if (isset($_POST['toggle_user']) && isset($_POST['action'])) {
        $user = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['toggle_user']);
        $escapedUser = preg_quote($user, '/');

        if ($_POST['action'] === 'stop') {
            $cmds[] = "$sshPrefix \"sed -i '/^\\s*(###|#!|#&|#\\$) $escapedUser / {N; s/\\\"password\\\": \\\"[^\"]*\\\"/\\\"locked\\\"/}' $configPath\"";
        } else {
            $cmds[] = "$sshPrefix \"sed -i '/##LOCK##/b; /##LOCK##/!{ s/\\\"password\\\": \\\"locked\\\"/\\\"password\\\": \\\"\\\"/ }' $configPath\"";
        }

        $cmds[] = "$sshPrefix 'systemctl restart xray'";
    }
if (isset($_POST['edit_user'])) {
    try {
        $user = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['edit_user']);
        $expiredInput = trim($_POST['expired']);
        $escapedUser = preg_quote($user, '/');
        $cmds = [];

        $fileAkun = "$remotePath/akun-$reseller-$user.txt";

        // 🔥 STEP 1: Ambil tanggal terakhir dari config.json
        $prevDateCmd = "$sshPrefix \"grep -E '^#! $escapedUser ' $configPath | awk '{print \\$3}'\"";
        $prevDate = trim(shell_exec($prevDateCmd));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $prevDate)) {
            $prevDate = date('Y-m-d');
        }

        // 🔢 STEP 2: Hitung expired baru
        if (preg_match('/^\d+$/', $expiredInput)) {
            $expired = date('Y-m-d', strtotime("+$expiredInput days", strtotime($prevDate)));
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiredInput)) {
            $expired = $expiredInput;
        } else {
            throw new Exception("❌ Format tanggal salah. Gunakan YYYY-MM-DD atau jumlah hari.");
        }

        echo "<pre>";
        echo "User        : $user\n";
        echo "Prev Date   : $prevDate\n";
        echo "New Expired : $expired\n";
        echo "File Akun   : $fileAkun\n\n";

        // 🛠️ STEP 3: Update file .txt dan config.json
        $cmds[] = "$sshPrefix \"sed -i 's|^Expired On[[:space:]]*:[[:space:]]*.*|Expired On     : $expired|' $fileAkun\"";
        $cmds[] = "$sshPrefix \"sed -i 's|^#! $escapedUser .*|#! $user $expired|' $configPath\"";
        $cmds[] = "$sshPrefix 'systemctl restart xray'";

        echo "CMDs:\n";
        foreach ($cmds as $c) {
            echo "👉 $c\n";
            $out = shell_exec($c);
            echo "Output: $out\n\n";
        }

        echo "✅ Selesai!";
        exit;

    } catch (Exception $e) {
        echo "<pre style='color:red;'>".$e->getMessage()."</pre>";
        exit;
    }
}

    // Jalankan semua command
    foreach ($cmds as $c) {
        $out = shell_exec($c);
        file_put_contents("debug.log", "RUNNING: $c\nOUTPUT:\n$out\n", FILE_APPEND);
    }

    // Hilangkan redirect agar output bisa dilihat
}
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

<?php
$listCmd = "$sshPrefix \"ls $remotePath/akun-$reseller-*.txt 2>/dev/null\"";
$fileListRaw = shell_exec($listCmd);
$files = array_filter(explode("\n", trim($fileListRaw ?? '')));
if (empty($files)): ?>
    <div class="text-center bg-yellow-500/10 border border-yellow-400 text-yellow-300 p-4 rounded">
        ⚠ Belum ada daftar akun untuk reseller <strong><?= htmlspecialchars($reseller) ?></strong>
    </div>
<?php else:
    foreach ($files as $remoteFile):
        $fn = basename($remoteFile);
        preg_match("/akun-".preg_quote($reseller,"/")."-(.+)\.txt/", $fn, $m);
        $u = $m[1] ?? 'unknown';
        $content = trim(shell_exec("$sshPrefix \"cat ".escapeshellarg($remoteFile)."\""));
        $isDisabled = strpos($content, 'DISABLED') !== false;
?>
    <div class="bg-gray-800 rounded p-4 shadow mb-4">
        <div class="flex justify-between items-center flex-wrap">
            <div class="text-lg font-semibold"><?=htmlspecialchars($u)?></div>
            <div class="space-x-2 mt-2 sm:mt-0">
                <button id="btn-<?=$u?>" onclick="toggleDetail('<?=$u?>')" class="btn-show bg-blue-600 px-3 py-1 rounded hover:bg-blue-700">Show</button>
                <form method="POST" class="inline">
                    <input type="hidden" name="toggle_user" value="<?=$u?>">
                    <input type="hidden" name="action" value="<?=$isDisabled?'start':'stop'?>">
                    <button type="submit" class="<?= $isDisabled?'bg-green-600':'bg-yellow-600' ?> px-3 py-1 rounded hover:bg-opacity-90"><?= $isDisabled?'Start':'Stop'?></button>
                </form>
                <a href="?hapus=<?=$u?>" onclick="return confirm('Yakin ingin menghapus akun <?=$u?>?')" class="bg-red-600 px-3 py-1 rounded hover:bg-red-700">Delete</a>
                <button onclick="document.getElementById('form-<?=$u?>').classList.toggle('hidden')" class="bg-green-600 px-3 py-1 rounded hover:bg-green-700">Edit</button>
            </div>
        </div>
        <div id="detail-<?=$u?>" class="detail-box mt-3 bg-gray-700 rounded hidden">
            <pre class="p-3 text-green-300 font-mono text-sm whitespace-pre-wrap"><?=htmlspecialchars($content)?></pre>
        </div>
        <form method="POST" id="form-<?=$u?>" class="mt-3 hidden bg-gray-700 p-4 rounded">
            <input type="hidden" name="edit_user" value="<?=$u?>">
            <label class="block mb-2">Masukkan tanggal expired baru (atau jumlah hari untuk perpanjang)</label>
            <input type="text" name="expired" required class="w-full p-2 rounded bg-gray-600 text-white mb-3">
            <button type="submit" class="bg-green-600 px-4 py-2 rounded hover:bg-green-700">Simpan</button>
        </form>
    </div>
<?php endforeach; endif; ?>
</div>

<!-- DEBUGGING OUTPUT -->
<pre class="bg-black text-green-400 p-4 text-sm overflow-x-auto">
<?php
echo "\n=== DEBUG LIVE ===\n";
echo "SESSION:\n";
print_r($_SESSION);
echo "POST:\n";
print_r($_POST);
echo "CMDs:\n";
print_r($cmds ?? []);
?>
</pre>

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

