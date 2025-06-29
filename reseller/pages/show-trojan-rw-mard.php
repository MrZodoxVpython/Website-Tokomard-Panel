<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$reseller = $_SESSION['reseller'] ?? $_SESSION['username'] ?? 'unknown';

$remoteIP = '203.194.113.140';
$sshUser = 'root';
$sshPrefix = "ssh -o ConnectTimeout=5 -o StrictHostKeyChecking=no $sshUser@$remoteIP";

$configPath = '/etc/xray/config.json';
$dataPath = '/etc/xray/data-panel/reseller';

// Handle Delete
if (isset($_GET['hapus'])) {
    $userDel = $_GET['hapus'];
    $delCmd = <<<CMD
$sshPrefix 'sed -i "/^#.* $userDel /,+1d" $configPath && rm -f $dataPath/akun-$reseller-$userDel.txt && /usr/local/bin/restart-xray.sh'
CMD;
    shell_exec($delCmd);
    header("Location: show-trojan-rw-mard.php");
    exit;
}

// Handle Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit_user'])) {
        $userEdit = $_POST['edit_user'];
        $input = trim($_POST['expired']);

        $expiredBaru = '';
        if (preg_match('/^\d+$/', $input)) {
            $expiredBaru = date('Y-m-d', strtotime("+$input days"));
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $input)) {
            $expiredBaru = $input;
        }

        if ($expiredBaru) {
            $cmdEdit = <<<CMD
$sshPrefix "sed -i 's/^\(#.* $userEdit \)[0-9\-]\+/\1$expiredBaru/' $configPath && sed -i 's/\\(Expired On *: *\\).*/\\1$expiredBaru/' $dataPath/akun-$reseller-$userEdit.txt && /usr/local/bin/restart-xray.sh"
CMD;
            shell_exec($cmdEdit);
        }
        header("Location: show-trojan-rw-mard.php");
        exit;
    }

    // Handle Start/Stop
    if (isset($_POST['toggle_user']) && isset($_POST['action'])) {
        $user = $_POST['toggle_user'];
        $action = $_POST['action'];

        if ($action === 'stop') {
            $cmd = <<<CMD
$sshPrefix "sed -i '/^#.* $user /{n;s/\"password\": *\"[^\"]*\"/\"password\": \"locked\"/}' $configPath && sed -i '/^#.* $user /a ##LOCK##$(grep -A1 \"#.* $user \" $configPath | tail -n1 | grep -oP '(?<=\"password\": \")[^\"]+')' $configPath && /usr/local/bin/restart-xray.sh"
CMD;
        } else { // start
            $cmd = <<<CMD
$sshPrefix "sed -i '/^##LOCK##/ { h; d }; /^#.* $user /{n;s/\"password\": \"locked\"/\"password\": \"$(grep -B1 \"\\\"password\\\": \\\"locked\\\"\" $configPath | grep '##LOCK##' | cut -d'#' -f3)'/}' $configPath && sed -i '/^##LOCK##/d' $configPath && /usr/local/bin/restart-xray.sh"
CMD;
        }

        shell_exec($cmd);
        header("Location: show-trojan-rw-mard.php");
        exit;
    }
}

// Load akun
$cmdListFiles = "$sshPrefix 'ls $dataPath/akun-$reseller-*.txt 2>/dev/null'";
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
    <h1 class="text-center text-2xl font-bold mb-4">Daftar Akun Trojan (RW-MARD) - <?= htmlspecialchars($reseller) ?></h1>
	    <?php if (empty($fileList)) : ?>
        <div class="text-center bg-yellow-500/10 border border-yellow-400 text-yellow-300 p-4 rounded">
            ⚠ Belum ada daftar akun untuk reseller <strong><?= htmlspecialchars($reseller) ?></strong>
            silahkan buat akun terlebih dahulu.
        </div>
    <?php else: ?>
        <?php foreach ($fileList as $remoteFile):
            $filename = basename($remoteFile);
            preg_match('/akun-' . preg_quote($reseller, '/') . '-(.+)\.txt/', $filename, $m);

        // Ambil isi file
        $escapedFile = escapeshellarg($remoteFile);
        $sshCatCmd = "$sshPrefix 'cat $escapedFile'";
        $content = trim(shell_exec($sshCatCmd));

        // Cek apakah akun locked
        $checkLockCmd = "$sshPrefix \"awk '/^#.* $username /{getline; print}' $configPath\"";
        $jsonLine = trim(shell_exec($checkLockCmd));
        $isLocked = strpos($jsonLine, '"password": "locked"') !== false;
    ?>
    <div class="bg-gray-800 p-4 rounded mb-4 shadow">
        <div class="flex justify-between items-center">
            <div class="text-lg font-semibold"><?= htmlspecialchars($username) ?></div>
            <div class="space-x-2">
                <button id="btn-<?= $username ?>" onclick="toggleDetail('<?= $username ?>')" class="btn-show bg-blue-600 px-3 py-1 rounded hover:bg-blue-700">Show</button>

                <!-- Start / Stop -->
                <form method="POST" class="inline">
                    <input type="hidden" name="toggle_user" value="<?= htmlspecialchars($username) ?>">
                    <input type="hidden" name="action" value="<?= $isLocked ? 'start' : 'stop' ?>">
                    <button type="submit" class="<?= $isLocked ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-500 hover:bg-gray-600' ?> px-3 py-1 rounded">
                        <?= $isLocked ? 'Start' : 'Stop' ?>
                    </button>
                </form>

                <a href="?hapus=<?= urlencode($username) ?>" onclick="return confirm('Hapus akun <?= $username ?>?')" class="bg-red-600 px-3 py-1 rounded hover:bg-red-700">Delete</a>
                <button onclick="document.getElementById('form-<?= $username ?>').classList.toggle('hidden')" class="bg-yellow-500 px-3 py-1 rounded hover:bg-yellow-600">Edit</button>
            </div>
        </div>

        <div id="detail-<?= $username ?>" class="detail-box mt-3 bg-gray-700 rounded hidden">
            <div class="overflow-x-auto">
                <pre class="text-green-300 font-mono text-sm whitespace-pre p-3 min-w-full"><?= htmlspecialchars($content ?: '❌ Tidak bisa membaca file akun.') ?></pre>
            </div>
        </div>

        <form method="POST" id="form-<?= $username ?>" class="mt-3 hidden bg-gray-700 p-4 rounded">
            <input type="hidden" name="edit_user" value="<?= htmlspecialchars($username) ?>">
            <label class="block mb-1">Update Expired (tgl atau hari)</label>
            <input type="text" name="expired" required class="w-full p-2 rounded bg-gray-600 mb-2 text-white">
            <button type="submit" class="bg-green-600 px-4 py-2 rounded hover:bg-green-700">Simpan</button>
        </form>
    </div>
    <?php endforeach; ?>
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

