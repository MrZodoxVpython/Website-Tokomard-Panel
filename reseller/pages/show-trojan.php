<?php
session_start();
$reseller = $_SESSION['reseller'] ?? $_SESSION['username'] ?? 'unknown';

$configPath = '/etc/xray/config.json';
$logDir = "/etc/xray/data-panel/reseller";
$akunFiles = glob("$logDir/akun-$reseller-*.txt");

// Proses DELETE
if (isset($_GET['hapus'])) {
    $userToDelete = $_GET['hapus'];
    $lines = file($configPath);
    $newLines = [];
    for ($i = 0; $i < count($lines); $i++) {
        if (preg_match('/^\s*(###|#&|#!|#\$)\s+' . preg_quote($userToDelete) . '\s+\d{4}-\d{2}-\d{2}/', $lines[$i])) {
            $i++; // skip next line JSON
            continue;
        }
        $newLines[] = $lines[$i];
    }
    file_put_contents($configPath, implode('', $newLines));
    shell_exec('sudo /usr/local/bin/restart-xray.sh');
    // Hapus file txt yang sesuai username pembeli
    $pattern = "$logDir/akun-$reseller-$userToDelete.txt";
    foreach (glob($pattern) as $file) {
         unlink($file);
    }
    header("Location: show-trojan.php");
    exit;
}

// Proses EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $userEdit = $_POST['edit_user'];
    $expiredInput = trim($_POST['expired']);

    if (preg_match('/^\d+$/', $expiredInput)) {
        $expiredBaru = date('Y-m-d', strtotime("+$expiredInput days"));
    } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiredInput)) {
        $expiredBaru = $expiredInput;
    } else {
        $expiredBaru = null;
    }

    if ($expiredBaru) {
        // Update config.json
        $lines = file($configPath);
        $updated = false;
        $currentTag = '';
        foreach ($lines as $i => $line) {
            if (preg_match('/^\s*#(trojan)(grpc|ws)?$/i', trim($line), $m)) {
                $currentTag = '#' . strtolower($m[1] . ($m[2] ?? ''));
            }

            if (in_array($currentTag, ['#trojanws', '#trojangrpc'])) {
                if (preg_match('/^\s*(###|#!|#&|#\$)\s+' . preg_quote($userEdit, '/') . '\s+\d{4}-\d{2}-\d{2}/', $line, $matches)) {
                    $prefix = $matches[1];
                    $lines[$i] = "$prefix $userEdit $expiredBaru\n";
                    $updated = true;
                }
            }
        }

        if ($updated) {
            file_put_contents($configPath, implode('', $lines));
        }

        // Update file .txt
        $pattern = "$logDir/akun-$reseller-$userEdit.txt";
        foreach (glob($pattern) as $file) {
            $content = file_get_contents($file);
            $content = preg_replace('/(Expired On\s*:\s*)(\d{4}-\d{2}-\d{2})/', '${1}' . $expiredBaru, $content);
            file_put_contents($file, $content);
        }

        shell_exec('sudo /usr/local/bin/restart-xray.sh');
    }

    header("Location: show-trojan.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Akun Trojan</title>
    <script>
        function toggleDetail(id) {
            document.querySelectorAll(".detail-box").forEach(el => el.style.display = 'none');
            document.querySelectorAll(".btn-show").forEach(btn => btn.innerText = 'Show');

            const box = document.getElementById("detail-" + id);
            const btn = document.getElementById("btn-" + id);
            if (box.style.display === 'none') {
                box.style.display = 'block';
                btn.innerText = 'Hide';
            } else {
                box.style.display = 'none';
                btn.innerText = 'Show';
            }
        }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white p-6">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold mb-4">Daftar Akun Trojan - <?= htmlspecialchars($reseller) ?></h1>

        <?php if (empty($akunFiles)) : ?>
            <div class="text-yellow-400">Belum ada akun.</div>
        <?php endif; ?>

        <?php foreach ($akunFiles as $file):
            $filename = basename($file);
            preg_match('/akun-' . preg_quote($reseller, '/') . '-(.+)\.txt/', $filename, $m);
            $username = $m[1] ?? 'unknown';
            $content = file_get_contents($file);
        ?>
            <div class="bg-gray-800 p-4 rounded mb-4 shadow">
                <div class="flex justify-between items-center">
                    <div class="text-lg font-semibold"><?= htmlspecialchars($username) ?></div>
                    <div class="space-x-2">
                        <button id="btn-<?= $username ?>" onclick="toggleDetail('<?= $username ?>')" class="btn-show bg-blue-600 px-3 py-1 rounded hover:bg-blue-700">Show</button>
                        <a href="?hapus=<?= urlencode($username) ?>" onclick="return confirm('Yakin ingin menghapus akun <?= $username ?>?')" class="bg-red-600 px-3 py-1 rounded hover:bg-red-700">Delete</a>
                        <button onclick="document.getElementById('form-<?= $username ?>').classList.toggle('hidden')" class="bg-yellow-500 px-3 py-1 rounded hover:bg-yellow-600">Edit</button>
                    </div>
                </div>

                <!-- Box detail akun -->
                <div id="detail-<?= $username ?>" class="detail-box mt-3 bg-gray-700 rounded hidden">
                    <div class="overflow-x-auto">
                        <pre class="text-green-300 font-mono text-sm whitespace-pre p-3 min-w-full">
<?= htmlspecialchars($content) ?>
                        </pre>
                    </div>
                </div>

                <!-- Form edit -->
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

            // Jika box yang diklik sedang terbuka, maka tutup saja (toggle normal)
            if (!targetBox.classList.contains('hidden')) {
                targetBox.classList.add('hidden');
                targetBtn.innerText = 'Show';
                return;
            }

            // Tutup semua lainnya
            allBoxes.forEach(box => box.classList.add('hidden'));
            allButtons.forEach(btn => btn.innerText = 'Show');

            // Buka yang diklik
            targetBox.classList.remove('hidden');
            targetBtn.innerText = 'Hide';
        }
    </script>
</body>
</html>

