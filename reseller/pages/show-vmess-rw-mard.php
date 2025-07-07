<?php
session_start();
if (isset($_SESSION['expired_success'])) {
    echo "<div 
            style='background-color:#111827;
                   color:#16a34a;
                   padding:5px;
                   border-radius:5px;
                   margin-top:0px;
                   margin-bottom:10px;
                   margin-left:auto;
                   margin-right:auto;
                   width:fit-content;'>
        " . $_SESSION['expired_success'] . "
    </div>";
    unset($_SESSION['expired_success']);
}

//ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    $user = $_POST['toggle_user'];
    $action = $_POST['action'];

    // Ambil config.json dari remote
    $tmpFile = "/tmp/config-remote-$user.json";
    shell_exec("$sshPrefix 'cat $configPath' > $tmpFile");
    $lines = file($tmpFile);
    $currentTag = '';
    $updated = false;

    for ($i = 0; $i < count($lines); $i++) {
        $line = trim($lines[$i]);

        if (preg_match('/^#vmess(grpc)?$/i', $line)) {
            $currentTag = strtolower($line);
        }

        if (in_array($currentTag, ['#vmess', '#vmessgrpc'])) {
            if (preg_match('/^\s*###\s+' . preg_quote($user, '/') . '\s+\d{4}-\d{2}-\d{2}/', $line)) {
                $lockLineIndex = $i + 1;
                $jsonLineIndex = $i + 1;

                if (isset($lines[$lockLineIndex]) && strpos(trim($lines[$lockLineIndex]), '##LOCK##') === 0) {
                    $jsonLineIndex++; // JSON pindah ke bawah LOCK
                }

                $jsonLine = trim($lines[$jsonLineIndex] ?? '');

                if ($action === 'stop') {
                    if (preg_match('/"id"\s*:\s*"([^\"]+)"/', $jsonLine, $m)) {
                        $originalId = $m[1];
                        if ($originalId !== 'locked') {
                            array_splice($lines, $jsonLineIndex, 0, ["##LOCK##$originalId\n"]);
                            $lines[$jsonLineIndex + 1] = preg_replace('/"id"\s*:\s*"[^\"]+"/', '"id": "locked"', $jsonLine) . "\n";
                            $updated = true;
                        }
                    }
                }

                if ($action === 'start') {
                    if (preg_match('/"id"\s*:\s*"locked"/', $jsonLine)) {
                        $lockLine = trim($lines[$jsonLineIndex - 1] ?? '');
                        if (preg_match('/^##LOCK##(.+)/', $lockLine, $m)) {
                            $realId = trim($m[1]);
                            if (strpos($jsonLine, '"email": "' . $user . '"') !== false) {
                                $lines[$jsonLineIndex] = preg_replace('/"id"\s*:\s*"locked"/', '"id": "' . $realId . '"', $jsonLine) . "\n";
                                array_splice($lines, $jsonLineIndex - 1, 1); // hapus ##LOCK##
                                $updated = true;
                            }
                        }
                    }
                }
            }
        }
    }

    if ($updated) {
        // Simpan file sementara
        file_put_contents($tmpFile, implode('', $lines));

        // Kirim kembali ke VPS remote
        shell_exec("scp -o StrictHostKeyChecking=no $tmpFile $sshUser@$remoteIP:$configPath");

        // Restart Xray
        shell_exec("$sshPrefix 'systemctl restart xray'");
    }

    header("Location: show-vmess-rw-mard.php");
    exit;
}

// EDIT EXPIRED
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {

    try {
        $user = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['edit_user']);
        $expiredInput = trim($_POST['expired']);
        $escapedUser = preg_quote($user, '/');
        $cmds = [];

        $fileAkun = "$remotePath/akun-$reseller-$user.txt";

        // ✅ Ambil tanggal expired terakhir
        $getDateCmd = "$sshPrefix \"grep 'Expired On' $fileAkun | awk -F ':' '{print \\$2}' | xargs\"";
        $prevDate = trim(shell_exec($getDateCmd));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $prevDate)) {
            $prevDate = date('Y-m-d');
        }

        // 🔁 Hitung tanggal baru
        if (preg_match('/^\d+$/', $expiredInput)) {
            $expired = date('Y-m-d', strtotime("+$expiredInput days", strtotime($prevDate)));
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiredInput)) {
            $expired = $expiredInput;
        } else {
            throw new Exception("❌ Format tanggal salah. Gunakan YYYY-MM-DD atau jumlah hari.");
        }

        //echo "<pre>";
        //echo "User        : $user\n";
        //echo "Prev Date   : $prevDate\n";
        //echo "New Expired : $expired\n";
        //echo "File Akun   : $fileAkun\n\n";

        // 🛠   Update file dan config
        $cmds[] = "$sshPrefix \"sed -i 's|^Expired On[[:space:]]*:[[:space:]]*.*|Expired On     : $expired|' $fileAkun\"";
        $cmds[] = "$sshPrefix \"sed -i 's|^### $escapedUser .*|### $user $expired|' $configPath\"";
        $cmds[] = "$sshPrefix 'systemctl restart xray'";

        echo "CMDs:\n";
        foreach ($cmds as $c) {
            echo "👉 $c\n";
            $out = shell_exec($c);
            echo "Output: $out\n\n";
        }

        //echo "✅ Perpanjang Selesai!\n";
        //echo "\n⏳ Mengarahkan ulang ke halaman utama dalam 3 detik...\n";
        //echo "</pre>";

        // flush output
        //ob_flush();
        //flush();
        //sleep(3); // tunggu 3 detik biar user lihat debug
                //sleep(3); // tunggu 3 detik biar user lihat debug

        //$_SESSION['expired_success'] = true;
 //       header("Location: ".$_SERVER['PHP_SELF']);
        //exit;
        $_SESSION['expired_success'] = "✅ Akun <b>$user</b> berhasil diperpanjang sampai <b>$expired</b>.";
        header("Location: ".$_SERVER['PHP_SELF']);
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
//ob_end_flush();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>VMess RW-MARD</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen p-6">
<div class="max-w-5xl mx-auto">
    <h1 class="text-center text-2xl font-bold mb-6">Daftar VMess (RW-MARD) – <?=htmlspecialchars($reseller)?></h1>

<?php
$listCmd = "$sshPrefix \"ls $remotePath/akun-$reseller-*.txt 2>/dev/null\"";
$fileListRaw = shell_exec($listCmd);
$files = array_filter(explode("\n", trim($fileListRaw ?? '')));

$found = false;
foreach ($files as $remoteFile):
    $fn = basename($remoteFile);
    preg_match("/akun-" . preg_quote($reseller, "/") . "-(.+)\.txt/", $fn, $m);
    $u = $m[1] ?? 'unknown';

    $content = trim(shell_exec("$sshPrefix \"cat " . escapeshellarg($remoteFile) . "\""));

    // Filter hanya file yang mengandung VMess
    if (
        stripos($content, 'vmess') === false &&
        !preg_match('/uuid\s*:\s*[0-9a-fA-F\-]{36}/', $content)
    ) {
        continue; // skip file Trojan/yang tidak cocok
    }

    $found = true;

    // tampilkan akun VMess valid
    //echo "<div class='p-4 border border-green-500 text-green-300 rounded mb-2'>
     //   ✅ Akun VMess: <strong>$u</strong><br>
      //  <pre class='text-sm whitespace-pre-wrap'>" . htmlspecialchars($content) . "</pre>
    //</div>";
endforeach;

if (!$found): ?>
    <div class="text-center bg-yellow-500/10 border border-yellow-400 text-yellow-300 p-4 rounded mt-6">
        ⚠ Tidak ada akun <strong>VMess</strong> yang ditemukan untuk reseller <strong><?= htmlspecialchars($reseller) ?></strong> silahkan buat akun terlebih dahulu.
    </div>

<!-- filter tag VMess only -->
<?php else:
    foreach ($files as $remoteFile):
        $fn = basename($remoteFile);
        preg_match("/akun-".preg_quote($reseller,"/")."-(.+)\.txt/", $fn, $m);
        $u = $m[1] ?? 'unknown';

        // Ambil isi file akun (.txt)
        $content = trim(shell_exec("$sshPrefix \"cat ".escapeshellarg($remoteFile)."\""));

        // ✅ Skip jika file tidak mengandung VMess
        if (
            stripos($content, 'vmess') === false &&
            !preg_match('/uuid\s*:\s*[0-9a-fA-F\-]{36}/', $content)
        ) {
            continue;
        }

        // Cek apakah akun sedang "locked"
        $checkCmd = "$sshPrefix \"grep -A 2 '### $u' $configPath | grep 'locked'\"";
        $result = shell_exec($checkCmd);
        $isDisabled = trim($result ?? '') !== '';
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
            <div id="detail-<?= $u?>" class="detail-box mt-3 bg-gray-700 rounded hidden">
                <div class="overflow-x-auto">
                    <pre class="text-green-300 font-mono text-sm whitespace-pre p-3 min-w-full"><?= htmlspecialchars($content) ?></pre>
                </div>
            </div>

        <div id="detail-<?=$u?>" class="detail-box mt-3 bg-gray-700 rounded hidden">
            <pre class="p-3 text-green-300 font-mono text-sm whitespace-pre-wrap break-all overflow-x-auto"><?=htmlspecialchars($content)?></pre>
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

