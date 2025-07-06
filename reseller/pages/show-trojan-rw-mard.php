<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
$reseller = $_SESSION['reseller'] ?? $_SESSION['username'] ?? 'unknown';

$remote_ip = '203.194.113.140';
$remote_user = 'root';
$configPath = '/etc/xray/config.json';
$dataDir = "/etc/xray/data-panel/reseller";
$localAkunFiles = glob("$dataDir/akun-$reseller-*.txt");

// Jalankan perintah di server remote via SSH
function remote_exec($command) {
    global $remote_user, $remote_ip;
    return shell_exec("ssh -o StrictHostKeyChecking=no $remote_user@$remote_ip \"$command\"");
}

// Ambil isi file config.json remote
function get_remote_config() {
    global $configPath;
    return explode("\n", remote_exec("cat $configPath"));
}

// Simpan file config.json remote
function put_remote_config($lines) {
    global $configPath;
    $tmp = tempnam(sys_get_temp_dir(), 'xraycfg');
    file_put_contents($tmp, implode("\n", $lines));
    shell_exec("scp -q -o StrictHostKeyChecking=no $tmp root@203.194.113.140:$configPath");
    remote_exec("systemctl restart xray");
    unlink($tmp);
}

// DELETE akun
if (isset($_GET['hapus'])) {
    $username = $_GET['hapus'];
    $lines = get_remote_config();
    $newLines = [];

    for ($i = 0; $i < count($lines); $i++) {
        if (preg_match('/^\s*(###|#!|#&|#\$)\s+' . preg_quote($username) . '\s+\d{4}-\d{2}-\d{2}/', $lines[$i])) {
            $i++; continue;
        }
        if (preg_match('/^##LOCK##/', trim($lines[$i]))) {
            continue;
        }
        $newLines[] = $lines[$i];
    }

    put_remote_config($newLines);
    remote_exec("rm -f /etc/xray/data-panel/reseller/akun-$reseller-$username.txt");

    header("Location: show-trojan-rw-mard.php");
    exit;
}

// Edit akun (perpanjang)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit_user'])) {
        $user = $_POST['edit_user'];
        $expiredInput = trim($_POST['expired']);
        $lines = get_remote_config();

        $expiredLama = null;
        foreach ($lines as $line) {
            if (preg_match('/^\s*(###|#!|#&|#\$)\s+' . preg_quote($user, '/') . '\s+(\d{4}-\d{2}-\d{2})/', $line, $m)) {
                $expiredLama = $m[2];
                break;
            }
        }

        if ($expiredInput && preg_match('/^\d+$/', $expiredInput)) {
            $expiredBaru = $expiredLama
                ? date('Y-m-d', strtotime("+$expiredInput days", strtotime($expiredLama)))
                : date('Y-m-d', strtotime("+$expiredInput days"));
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiredInput)) {
            $expiredBaru = $expiredInput;
        }

        if (!empty($expiredBaru)) {
            $currentTag = '';
            foreach ($lines as $i => $line) {
                if (preg_match('/^\s*#(trojan)(grpc|ws)?$/i', trim($line), $m)) {
                    $currentTag = '#' . strtolower($m[1] . ($m[2] ?? ''));
                }

                if (in_array($currentTag, ['#trojanws', '#trojangrpc'])) {
                    if (preg_match('/^\s*(###|#!|#&|#\$)\s+' . preg_quote($user, '/') . '\s+\d{4}-\d{2}-\d{2}/', $line, $m)) {
                        $prefix = $m[1];
                        $lines[$i] = "$prefix $user $expiredBaru";
                    }
                }
            }

            put_remote_config($lines);
            remote_exec("sed -i 's/\\(Expired On\\s*:\\s*\\)[0-9-]\\+/\\1$expiredBaru/' /etc/xray/data-panel/reseller/akun-$reseller-$user.txt");
        }

        header("Location: show-trojan-rw-mard.php");
        exit;
    }

    // START / STOP
    if (isset($_POST['toggle_user'], $_POST['action'])) {
        $user = $_POST['toggle_user'];
        $action = $_POST['action'];
        $lines = get_remote_config();
        $currentTag = '';
        $updated = false;

        for ($i = 0; $i < count($lines); $i++) {
            $line = trim($lines[$i]);

            if (preg_match('/^#trojan(ws|grpc)?$/i', $line)) {
                $currentTag = strtolower($line);
            }

            if (in_array($currentTag, ['#trojanws', '#trojangrpc'])) {
                if (preg_match('/^\s*(###|#!|#&|#\$)\s+' . preg_quote($user, '/') . '\s+\d{4}-\d{2}-\d{2}/', $line)) {
                    $lockLineIndex = $i + 1;
                    $jsonLineIndex = $i + 1;

                    if (isset($lines[$lockLineIndex]) && strpos(trim($lines[$lockLineIndex]), '##LOCK##') === 0) {
                        $jsonLineIndex++;
                    }

                    $jsonLine = trim($lines[$jsonLineIndex] ?? '');

                    if ($action === 'stop' && preg_match('/"password"\s*:\s*"([^"]+)"/', $jsonLine, $m)) {
                        $originalPassword = $m[1];
                        if ($originalPassword !== 'locked') {
                            array_splice($lines, $jsonLineIndex, 0, ["##LOCK##$originalPassword"]);
                            $lines[$jsonLineIndex + 1] = preg_replace('/"password"\s*:\s*"[^"]+"/', '"password": "locked"', $jsonLine);
                            $updated = true;
                        }
                    }

                    if ($action === 'start' && preg_match('/"password"\s*:\s*"locked"/', $jsonLine)) {
                        $lockLine = trim($lines[$jsonLineIndex - 1] ?? '');
                        if (preg_match('/^##LOCK##(.+)/', $lockLine, $m)) {
                            $realPassword = trim($m[1]);
                            $lines[$jsonLineIndex] = preg_replace('/"password"\s*:\s*"locked"/', '"password": "' . $realPassword . '"', $jsonLine);
                            array_splice($lines, $jsonLineIndex - 1, 1);
                            $updated = true;
                        }
                    }
                }
            }
        }

        if ($updated) {
            put_remote_config($lines);
        }

        header("Location: show-trojan-rw-mard.php");
        exit;
    }
}
?>

<!-- TAMPILAN -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Trojan - RW-MARD</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white p-6">
<div class="max-w-4xl mx-auto">
    <h1 class="text-2xl text-center font-bold mb-6">Daftar Akun Trojan (RW-MARD) - <?= htmlspecialchars($reseller) ?></h1>

    <?php if (empty($localAkunFiles)) : ?>
        <div class="bg-yellow-500/10 text-yellow-300 p-4 rounded text-center border border-yellow-400">
            ⚠ Belum ada akun terdaftar untuk reseller <strong><?= htmlspecialchars($reseller) ?></strong>
        </div>
    <?php else: ?>
        <?php foreach ($localAkunFiles as $file):
            $filename = basename($file);
            preg_match('/akun-' . preg_quote($reseller, '/') . '-(.+)\.txt/', $filename, $m);
            $username = $m[1] ?? 'unknown';
            $content = file_get_contents($file);
            $isDisabled = false;

            $configLines = get_remote_config();
            for ($i = 0; $i < count($configLines); $i++) {
                if (preg_match('/^\s*(###|#!|#&|#\$)\s+' . preg_quote($username) . '\s+\d{4}-\d{2}-\d{2}/', $configLines[$i])) {
                    for ($j = $i + 1; $j <= $i + 3 && $j < count($configLines); $j++) {
                        if (strpos($configLines[$j], '"password": "locked"') !== false) {
                            $isDisabled = true;
                            break 2;
                        }
                    }
                }
            }
        ?>
        <div class="bg-gray-800 p-4 mb-4 rounded shadow">
            <div class="flex justify-between items-center">
                <div class="text-lg font-semibold"><?= htmlspecialchars($username) ?></div>
                <div class="space-x-2">
                    <form method="POST" class="inline">
                        <input type="hidden" name="toggle_user" value="<?= htmlspecialchars($username) ?>">
                        <input type="hidden" name="action" value="<?= $isDisabled ? 'start' : 'stop' ?>">
                        <button class="<?= $isDisabled ? 'bg-green-600 hover:bg-green-700' : 'bg-yellow-600 hover:bg-yellow-700' ?> px-3 py-1 rounded">
                            <?= $isDisabled ? 'Start' : 'Stop' ?>
                        </button>
                    </form>

                    <a href="?hapus=<?= urlencode($username) ?>" onclick="return confirm('Yakin ingin hapus akun <?= $username ?>?')" class="bg-red-600 px-3 py-1 rounded hover:bg-red-700">Delete</a>

                    <button onclick="document.getElementById('form-<?= $username ?>').classList.toggle('hidden')" class="bg-blue-600 px-3 py-1 rounded hover:bg-blue-700">Edit</button>
                </div>
            </div>

            <div class="mt-2 bg-gray-700 rounded hidden detail-box" id="detail-<?= $username ?>">
                <pre class="text-green-300 font-mono text-sm p-2 overflow-x-auto"><?= htmlspecialchars($content) ?></pre>
            </div>

            <form method="POST" id="form-<?= $username ?>" class="hidden bg-gray-700 p-3 mt-2 rounded">
                <input type="hidden" name="edit_user" value="<?= htmlspecialchars($username) ?>">
                <label class="block mb-1">Tanggal expired baru / jumlah hari:</label>
                <input type="text" name="expired" required class="w-full p-2 mb-2 rounded bg-gray-600 text-white">
                <button type="submit" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded">Simpan</button>
            </form>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.detail-box').forEach(el => {
    const btn = document.createElement('button');
    btn.innerText = 'Show Detail';
    btn.className = 'mt-2 bg-blue-500 px-2 py-1 rounded text-sm';
    el.parentNode.insertBefore(btn, el);
    btn.onclick = () => {
        el.classList.toggle('hidden');
        btn.innerText = el.classList.contains('hidden') ? 'Show Detail' : 'Hide Detail';
    }
});
</script>
</body>
</html>

