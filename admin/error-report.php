<?php
session_start();
// Cek role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

// Hanya admin yang boleh mengakses
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Path file log error
$log_path = __DIR__ . '/logs/error.log';  // Sesuaikan dengan lokasi log kamu
$logs = [];

if (file_exists($log_path)) {
    $logs = file($log_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $logs = array_reverse($logs); // Tampilkan error terbaru di atas
}
?>

<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Laporan Error</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen p-6">

    <div class="max-w-5xl mx-auto bg-gray-800 rounded-lg p-6 shadow-lg">
        <h1 class="text-2xl font-semibold mb-4">Laporan Error Panel</h1>

        <?php if (empty($logs)): ?>
            <p class="text-gray-400">Tidak ada error tercatat.</p>
        <?php else: ?>
            <div class="overflow-y-auto max-h-[70vh] bg-black text-green-400 p-4 rounded-lg text-sm font-mono whitespace-pre-wrap border border-gray-700">
                <?php foreach ($logs as $line): ?>
                    <?= htmlspecialchars($line) . "\n" ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>

