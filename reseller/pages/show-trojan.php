<?php
session_start();

$reseller = $_SESSION['reseller'] ?? $_SESSION['username'] ?? 'unknown';

$logDir = "/etc/xray/data-panel/reseller";
$pattern = "$logDir/akun-$reseller-*.txt";
$files = glob($pattern);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Akun Trojan</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen p-6">
    <div class="max-w-3xl mx-auto">
        <h1 class="text-3xl font-bold mb-6 text-center">Daftar Akun Trojan - <?= htmlspecialchars($reseller) ?></h1>

        <?php if (empty($files)): ?>
            <div class="bg-yellow-800 text-yellow-200 p-4 rounded shadow">Belum ada akun yang dibuat.</div>
        <?php else: ?>
            <ul class="space-y-4">
                <?php foreach ($files as $index => $file): 
                    $filename = basename($file);
                    preg_match("/akun-{$reseller}-(.+?)\.txt$/", $filename, $match);
                    $username = $match[1] ?? 'unknown';
                    $fileContent = htmlspecialchars(file_get_contents($file));
                ?>
                <li class="border border-gray-700 rounded p-4 bg-gray-800 shadow">
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="font-semibold">Akun:</span> <?= htmlspecialchars($username) ?>
                        </div>
                        <button onclick="toggleContent('detail-<?= $index ?>')" class="bg-indigo-600 hover:bg-indigo-700 px-4 py-1 rounded text-sm">
                            Show
                        </button>
                    </div>
                    <pre id="detail-<?= $index ?>" class="mt-4 hidden bg-gray-900 p-3 rounded text-green-400 overflow-x-auto"><?= $fileContent ?></pre>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <script>
        function toggleContent(id) {
            const el = document.getElementById(id);
            el.classList.toggle("hidden");
        }
    </script>
</body>
</html>

