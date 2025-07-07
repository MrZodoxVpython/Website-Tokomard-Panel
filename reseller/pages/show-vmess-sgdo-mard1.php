<?php
session_start();
if (isset($_SESSION['expired_success'])) {
    echo "<div style='background-color:#111827;color:#16a34a;padding:5px;border-radius:5px;margin:10px auto;width:fit-content;'>"
        . $_SESSION['expired_success'] . "</div>";
    unset($_SESSION['expired_success']);
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

$reseller = $_SESSION['reseller'] ?? $_SESSION['username'] ?? '';
if (empty($reseller)) die("❌ Reseller tidak ditemukan dalam session.");

$remoteIP = '152.42.182.187';
$sshUser = 'root';
$sshPrefix = "ssh -o ConnectTimeout=5 -o StrictHostKeyChecking=no $sshUser@$remoteIP";
$configPath = '/etc/xray/config.json';
$remotePath = "/etc/xray/data-panel/reseller";

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['hapus'])) {
    if (isset($_GET['hapus'])) {
        $u = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['hapus']);
        shell_exec("$sshPrefix \"sed -i '/#vmess\\|#vmessgrpc/{N;/### $u /{N;/\\n##LOCK##/N;d}}' $configPath\"");
        shell_exec("$sshPrefix \"rm -f $remotePath/akun-$reseller-$u.txt\"");
        shell_exec("$sshPrefix 'systemctl restart xray'");
    }

    if (isset($_POST['toggle_user'], $_POST['action'])) {
        $user = $_POST['toggle_user'];
        $action = $_POST['action'];
        $tmpFile = "/tmp/config-remote-$user.json";
        shell_exec("$sshPrefix 'cat $configPath' > $tmpFile");

        $lines = file($tmpFile);
        $updated = false;
        for ($i = 0; $i < count($lines); $i++) {
            $line = trim($lines[$i]);

            if ((strpos($line, '#vmess') === 0 || strpos($line, '#vmessgrpc') === 0)
                && isset($lines[$i + 1]) && preg_match('/^### ' . preg_quote($user, '/') . ' \d{4}-\d{2}-\d{2}$/', trim($lines[$i + 1]))) {
                $lockLineIndex = $i + 2;
                $jsonLineIndex = $i + 2;
                if (isset($lines[$lockLineIndex]) && strpos(trim($lines[$lockLineIndex]), '##LOCK##') === 0) {
                    $jsonLineIndex++;
                }
                $jsonLine = trim($lines[$jsonLineIndex] ?? '');

                if ($action === 'stop' && preg_match('/"id"\s*:\s*"([^\"]+)"/', $jsonLine, $m)) {
                    $originalPassword = $m[1];
                    if ($originalPassword !== 'locked') {
                        array_splice($lines, $jsonLineIndex, 0, ["##LOCK##$originalPassword\n"]);
                        $lines[$jsonLineIndex + 1] = preg_replace('/"id"\s*:\s*"[^\"]+"/', '"id": "locked"', $jsonLine) . "\n";
                        $updated = true;
                    }
                }

                if ($action === 'start' && preg_match('/"id"\s*:\s*"locked"/', $jsonLine)) {
                    $lockLine = trim($lines[$jsonLineIndex - 1] ?? '');
                    if (preg_match('/^##LOCK##(.+)/', $lockLine, $m)) {
                        $realPassword = trim($m[1]);
                        $lines[$jsonLineIndex] = preg_replace('/"id"\s*:\s*"locked"/', '"id": "' . $realPassword . '"', $jsonLine) . "\n";
                        array_splice($lines, $jsonLineIndex - 1, 1);
                        $updated = true;
                    }
                }
            }
        }

        if ($updated) {
            file_put_contents($tmpFile, implode('', $lines));
            shell_exec("scp -o StrictHostKeyChecking=no $tmpFile $sshUser@$remoteIP:$configPath");
            shell_exec("$sshPrefix 'systemctl restart xray'");
        }

        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }

    if (isset($_POST['edit_user'])) {
        $user = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['edit_user']);
        $expiredInput = trim($_POST['expired']);
        $escapedUser = preg_quote($user, '/');
        $fileAkun = "$remotePath/akun-$reseller-$user.txt";
        $prevDate = trim(shell_exec("$sshPrefix \"grep 'Expired On' $fileAkun | awk -F ':' '{print \\$2}' | xargs\"")) ?: date('Y-m-d');

        if (preg_match('/^\d+$/', $expiredInput)) {
            $expired = date('Y-m-d', strtotime("+$expiredInput days", strtotime($prevDate)));
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiredInput)) {
            $expired = $expiredInput;
        } else {
            die("❌ Format tanggal salah.");
        }

        shell_exec("$sshPrefix \"sed -i 's|^Expired On[[:space:]]*:[[:space:]]*.*|Expired On     : $expired|' $fileAkun\"");
        shell_exec("$sshPrefix \"sed -i 's|^### $escapedUser .*|### $user $expired|' $configPath\"");
        shell_exec("$sshPrefix 'systemctl restart xray'");
        $_SESSION['expired_success'] = "✅ Akun <b>$user</b> diperpanjang sampai <b>$expired</b>";
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>VMess SGDO-MARD1</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen p-6">
<div class="max-w-5xl mx-auto">
    <h1 class="text-center text-2xl font-bold mb-6">Akun VMess – <?=htmlspecialchars($reseller)?></h1>
<?php
$listCmd = "$sshPrefix \"ls $remotePath/akun-$reseller-*.txt 2>/dev/null\"";
$fileList = explode("\n", trim(shell_exec($listCmd) ?? ''));
if (empty($fileList[0])) {
    echo "<div class='text-center bg-yellow-500/10 border border-yellow-400 text-yellow-300 p-4 rounded'>
        ⚠ Belum ada akun VMess aktif untuk reseller <strong>" . htmlspecialchars($reseller) . "</strong></div>";
} else {
    foreach ($fileList as $file) {
        $u = basename($file);
        if (preg_match("/akun-$reseller-(.+)\.txt/", $u, $m)) {
            $username = $m[1];
            
	    // Deteksi apakah akun ini VMess dari file .txt
	    $checkVMessTxt = "$sshPrefix \"grep -q 'VMESS ACCOUNT' $file\" && echo 'yes'";
	    $output = ($tmp = shell_exec($checkVMessTxt)) !== null ? trim($tmp) : '';
	    //$output = trim(shell_exec($checkVMessTxt));
	    if ($output !== 'yes') continue;
 
	    $rawContent = shell_exec("$sshPrefix \"cat $file\"");
	    $content = '';
	    if ($rawContent !== null && is_string($rawContent)) {
    	        $content = trim($rawContent);
	    }
            $check = shell_exec("$sshPrefix \"grep -A 3 '#vmess\\|#vmessgrpc' $configPath | grep -A 3 '### $username' | grep 'locked'\"");
            $isLocked = trim($check ?? '') !== '';
?>
<div class="bg-gray-800 rounded p-4 shadow mb-4">
    <div class="flex justify-between items-center flex-wrap">
        <div class="text-lg font-semibold"><?=htmlspecialchars($username)?></div>
        <div class="space-x-2 mt-2 sm:mt-0">
            <button id="btn-<?=$username?>" onclick="toggleDetail('<?=$username?>')" class="btn-show bg-blue-600 px-3 py-1 rounded hover:bg-blue-700">Show</button>
            <form method="POST" class="inline">
                <input type="hidden" name="toggle_user" value="<?=$username?>">
                <input type="hidden" name="action" value="<?=$isLocked ? 'start' : 'stop'?>">
                <button type="submit" class="<?= $isLocked ? 'bg-green-600':'bg-yellow-600' ?> px-3 py-1 rounded hover:bg-opacity-90"><?= $isLocked ? 'Start':'Stop' ?></button>
            </form>
            <a href="?hapus=<?=$username?>" onclick="return confirm('Yakin hapus akun <?=$username?>?')" class="bg-red-600 px-3 py-1 rounded hover:bg-red-700">Delete</a>
            <button onclick="document.getElementById('form-<?=$username?>').classList.toggle('hidden')" class="bg-green-600 px-3 py-1 rounded hover:bg-green-700">Edit</button>
        </div>
    </div>
    <div id="detail-<?=$username?>" class="detail-box mt-3 bg-gray-700 rounded hidden">
        <pre class="p-3 text-green-300 font-mono text-sm whitespace-pre-wrap"><?=htmlspecialchars($content)?></pre>
    </div>
    <form method="POST" id="form-<?=$username?>" class="mt-3 hidden bg-gray-700 p-4 rounded">
        <input type="hidden" name="edit_user" value="<?=$username?>">
        <label class="block mb-2">Tanggal expired baru (yyyy-mm-dd atau jumlah hari):</label>
        <input type="text" name="expired" required class="w-full p-2 rounded bg-gray-600 text-white mb-3">
        <button type="submit" class="bg-green-600 px-4 py-2 rounded hover:bg-green-700">Simpan</button>
    </form>
</div>
<?php
        }
    }
}
?>
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

</script>
</body>
</html>

