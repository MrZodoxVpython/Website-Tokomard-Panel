<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$reseller = $_SESSION['reseller'] ?? $_SESSION['username'] ?? 'unknown';

$configPath = '/etc/xray/config.json';
$logDir = "/etc/xray/data-panel/reseller";
$akunFiles = glob("$logDir/akun-$reseller-*.txt");

// Handle DELETE
if (isset($_GET['hapus'])) {
    $userToDelete = $_GET['hapus'];
    $lines = file($configPath);
    $newLines = [];

    for ($i = 0; $i < count($lines); $i++) {
        if (preg_match('/^\s*(###|#&|#!|#\$)\s+' . preg_quote($userToDelete) . '\s+\d{4}-\d{2}-\d{2}/', $lines[$i])) {
            $i++; // skip JSON line
            continue;
        }
        if (preg_match('/^##LOCK##/', trim($lines[$i]))) {
            continue; // skip LOCK lines if any
        }
        $newLines[] = $lines[$i];
    }

    file_put_contents($configPath, implode('', $newLines));
    shell_exec('sudo /usr/local/bin/restart-xray.sh');

    foreach (glob("$logDir/akun-$reseller-$userToDelete.txt") as $file) {
        unlink($file);
    }

    header("Location: show-trojan.php");
    exit;
}

// Handle POST (Edit & Start/Stop)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Edit Expired
    if (isset($_POST['edit_user'])) {
        $userEdit = $_POST['edit_user'];
        $expiredInput = trim($_POST['expired']);
        $expiredBaru = null;

        if (preg_match('/^\d+$/', $expiredInput)) {
            $expiredBaru = date('Y-m-d', strtotime("+$expiredInput days"));
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiredInput)) {
            $expiredBaru = $expiredInput;
        }

        if ($expiredBaru) {
            $lines = file($configPath);
            $currentTag = '';

            foreach ($lines as $i => $line) {
                if (preg_match('/^\s*#(trojan)(grpc|ws)?$/i', trim($line), $m)) {
                    $currentTag = '#' . strtolower($m[1] . ($m[2] ?? ''));
                }

                if (in_array($currentTag, ['#trojanws', '#trojangrpc'])) {
                    if (preg_match('/^\s*(###|#!|#&|#\$)\s+' . preg_quote($userEdit, '/') . '\s+\d{4}-\d{2}-\d{2}/', $line, $matches)) {
                        $prefix = $matches[1];
                        $lines[$i] = "$prefix $userEdit $expiredBaru\n";
                    }
                }
            }

            file_put_contents($configPath, implode('', $lines));

            foreach (glob("$logDir/akun-$reseller-$userEdit.txt") as $file) {
                $content = file_get_contents($file);
                $content = preg_replace('/(Expired On\s*:\s*)(\d{4}-\d{2}-\d{2})/', '${1}' . $expiredBaru, $content);
                file_put_contents($file, $content);
            }

            shell_exec('sudo /usr/local/bin/restart-xray.sh');
        }

        header("Location: show-trojan.php");
        exit;
    }

// START / STOP
if (isset($_POST['toggle_user']) && isset($_POST['action'])) {
    $user = $_POST['toggle_user'];
    $action = $_POST['action']; // start or stop
    $lines = file($configPath);
    $currentTag = '';
    $updated = false;

    for ($i = 0; $i < count($lines); $i++) {
        $line = trim($lines[$i]);

        // Deteksi blok protokol
        if (preg_match('/^#trojan(ws|grpc)?$/i', $line)) {
            $currentTag = strtolower($line);
        }

        // Proses hanya jika sedang dalam blok trojan
        if (in_array($currentTag, ['#trojanws', '#trojangrpc'])) {
            if (preg_match('/^\s*(###|#!|#&|#\$)\s+' . preg_quote($user, '/') . '\s+\d{4}-\d{2}-\d{2}/', $line)) {
                $jsonIndex = $i + 1;
                if (!isset($lines[$jsonIndex])) continue;

                $jsonLine = trim($lines[$jsonIndex]);

                // STOP: lock akun
                if ($action === 'stop') {
                    if (preg_match('/"password"\s*:\s*"([^"]+)"/', $jsonLine, $m)) {
                        $originalPassword = $m[1];
                        if ($originalPassword !== 'locked') {
                            $lines[$jsonIndex] = preg_replace('/"password"\s*:\s*"[^"]+"/', '"password": "locked"', $jsonLine) . "\n";
                            array_splice($lines, $jsonIndex, 0, ["##LOCK##$originalPassword\n"]);
                            $updated = true;
                        }
                    }
                }

                // START: unlock akun
                if ($action === 'start') {
                    if (preg_match('/"password"\s*:\s*"locked"/', $jsonLine)) {
                        // Cari baris LOCK di atasnya, max 10 baris ke atas
                        for ($k = $jsonIndex - 1; $k >= max(0, $jsonIndex - 10); $k--) {
                            if (preg_match('/^##LOCK##(.+)/', trim($lines[$k]), $match)) {
                                $realPassword = trim($match[1]);

                                // Kembalikan password asli
                                $lines[$jsonIndex] = preg_replace('/"password"\s*:\s*"locked"/', '"password": "' . $realPassword . '"', $lines[$jsonIndex]);

                                // Hapus baris ##LOCK##
                                array_splice($lines, $k, 1);
                                $updated = true;
                                break;
                            }
                        }
                    }
                }
            }
        }
    }

    if ($updated) {
        file_put_contents($configPath, implode('', $lines));
        shell_exec('sudo /usr/local/bin/restart-xray.sh');
    }

    header("Location: show-trojan.php");
    exit;
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Trojan SGDO-2DEV</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white p-6">
<div class="max-w-4xl mx-auto">
    <h1 class="text-center text-2xl font-bold mb-4">Daftar Akun Trojan (SGDO-2DEV) - <?= htmlspecialchars($reseller) ?></h1>
    <?php if (empty($fileList)) : ?>
        <div class="text-center bg-yellow-500/10 border border-yellow-400 text-yellow-300 p-4 rounded">
            ⚠ Belum ada daftar akun untuk reseller <strong><?= htmlspecialchars($reseller) ?></strong>
            silahkan buat akan terlebih dahulu.
        </div>
    <?php else: ?>
    <?php foreach ($akunFiles as $file):
        $filename = basename($file);
        preg_match('/akun-' . preg_quote($reseller, '/') . '-(.+)\.txt/', $filename, $m);
        $username = $m[1] ?? 'unknown';
        $content = file_get_contents($file);
        $isDisabled = false;

        $configLines = file($configPath);
        for ($i = 0; $i < count($configLines); $i++) {
            if (preg_match('/^\s*(###|#!|#&|#\$)\s+' . preg_quote($username) . '\s+\d{4}-\d{2}-\d{2}/', $configLines[$i])) {
                for ($j = $i + 1; $j <= $i + 3 && $j < count($configLines); $j++) {
                    $line = trim($configLines[$j]);
                    if (strpos($line, '"password": "locked"') !== false) {
                        $isDisabled = true;
                        break 2;
                    }
                }
            }
        }
    ?>
        <div class="bg-gray-800 p-4 rounded mb-4 shadow">
            <div class="flex justify-between items-center">
                <div class="text-lg font-semibold"><?= htmlspecialchars($username) ?></div>
                <div class="space-x-2">
                    <button id="btn-<?= $username ?>" onclick="toggleDetail('<?= $username ?>')" class="btn-show bg-blue-600 px-3 py-1 rounded hover:bg-blue-700">Show</button>

                    <!-- Tombol Start/Stop -->
                    <form method="POST" class="inline">
                        <input type="hidden" name="toggle_user" value="<?= htmlspecialchars($username) ?>">
                        <input type="hidden" name="action" value="<?= $isDisabled ? 'start' : 'stop' ?>">
                        <button type="submit" class="<?= $isDisabled ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-500 hover:bg-gray-600' ?> px-3 py-1 rounded">
                            <?= $isDisabled ? 'Start' : 'Stop' ?>
                        </button>
                    </form>

                    <a href="?hapus=<?= urlencode($username) ?>" onclick="return confirm('Yakin ingin menghapus akun <?= $username ?>?')" class="bg-red-600 px-3 py-1 rounded hover:bg-red-700">Delete</a>
                    <button onclick="document.getElementById('form-<?= $username ?>').classList.toggle('hidden')" class="bg-yellow-500 px-3 py-1 rounded hover:bg-yellow-600">Edit</button>
                </div>
            </div>

            <div id="detail-<?= $username ?>" class="detail-box mt-3 bg-gray-700 rounded hidden">
                <div class="overflow-x-auto">
                    <pre class="text-green-300 font-mono text-sm whitespace-pre p-3 min-w-full"><?= htmlspecialchars($content) ?></pre>
                </div>
            </div>

            <form method="POST" id="form-<?= $username ?>" class="mt-3 hidden bg-gray-700 p-4 rounded">
                <input type="hidden" name="edit_user" value="<?= htmlspecialchars($username) ?>">
                <label class="block mb-1">Perbarui Expired (tgl atau jumlah hari)</label>
                <input type="text" name="expired" required class="w-full p-2 rounded bg-gray-600 mb-2 text-white">
                <button type="submit" class="bg-green-600 px-4 py-2 rounded hover:bg-green-700">Simpan</button>
            </form>
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

