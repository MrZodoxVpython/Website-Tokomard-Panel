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
                    preg_match("/akun-{$reseller}-(.+?)\\.txt$/", $filename, $match);
                    $username = $match[1] ?? 'unknown';
                    $fileContent = htmlspecialchars(file_get_contents($file));
                    $encodedUser = urlencode($username);
                ?>
                <li class="border border-gray-700 rounded p-4 bg-gray-800 shadow">
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="font-semibold">Akun:</span> <?= htmlspecialchars($username) ?>
                        </div>
                        <div class="flex space-x-2">
                            <button 
                                onclick="toggleContent('detail-<?= $index ?>', this)" 
                                class="bg-indigo-600 hover:bg-indigo-700 px-4 py-1 rounded text-sm toggle-button"
                            >
                                Show
                            </button>
                            <a href="edit-akun.php?user=<?= $encodedUser ?>&proto=trojan" class="bg-yellow-600 hover:bg-yellow-700 px-3 py-1 rounded text-sm">Edit</a>
                            <a href="hapus-akun.php?user=<?= $encodedUser ?>" onclick="return confirm('Yakin ingin menghapus akun ini?')" class="bg-red-600 hover:bg-red-700 px-3 py-1 rounded text-sm">Delete</a>
                        </div>
                    </div>
                    <pre id="detail-<?= $index ?>" class="mt-4 hidden bg-gray-900 p-3 rounded text-green-400 overflow-x-auto"><?= $fileContent ?></pre>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <script>
        let lastOpened = null;

        function toggleContent(id, button) {
            const current = document.getElementById(id);

            if (lastOpened && lastOpened !== current) {
                lastOpened.classList.add("hidden");
                const previousButton = document.querySelector(`button[data-target="${lastOpened.id}"]`);
                if (previousButton) previousButton.innerText = "Show";
            }

            const isHidden = current.classList.contains("hidden");
            current.classList.toggle("hidden");

            button.innerText = isHidden ? "Hide" : "Show";
            button.setAttribute("data-target", id);

            lastOpened = isHidden ? current : null;
        }
    </script>
</body>
</html>

