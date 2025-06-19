<?php
session_start();

// Cek role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

// Path ke file log aktivitas
$log_file = __DIR__ . '/log_aktivitas_admin.txt';
$logs = [];

if (file_exists($log_file)) {
    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $logs = array_reverse($lines); // Tampilkan yang terbaru dulu
}
?>

<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Aktivitas Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen p-6">

    <div class="max-w-4xl mx-auto bg-gray-800 p-6 rounded-lg shadow-lg">
        <h1 class="text-2xl font-semibold mb-4">Log Aktivitas Admin</h1>

        <?php if (empty($logs)): ?>
            <p class="text-gray-400">Belum ada aktivitas yang tercatat.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left table-auto border-collapse">
                    <thead>
                        <tr class="bg-gray-700 text-gray-100">
                            <th class="p-3 border-b border-gray-600">Waktu</th>
                            <th class="p-3 border-b border-gray-600">Username</th>
                            <th class="p-3 border-b border-gray-600">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): 
                            $parts = explode('|', $log);
                            if (count($parts) === 3): ?>
                                <tr class="hover:bg-gray-700">
                                    <td class="p-3 border-b border-gray-700"><?= htmlspecialchars(trim($parts[0])) ?></td>
                                    <td class="p-3 border-b border-gray-700"><?= htmlspecialchars(trim($parts[1])) ?></td>
                                    <td class="p-3 border-b border-gray-700"><?= htmlspecialchars(trim($parts[2])) ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>

