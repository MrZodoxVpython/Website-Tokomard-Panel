<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$host = $_POST['host'] ?? '';
$user = $_POST['user'] ?? 'root';
$port = $_POST['port'] ?? 22;

// Form perintah dan hasil eksekusi
$output = '';
$command = $_POST['command'] ?? '';

if ($command !== '') {
    $output = shell_exec($command . ' 2>&1');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shell Terminal - <?= htmlspecialchars($host) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen p-6">
    <h1 class="text-2xl font-bold mb-6">🖥 Akses Shell: <?= htmlspecialchars($host) ?></h1>

    <form method="post" class="bg-gray-800 p-6 rounded shadow w-full max-w-3xl mb-4">
        <input type="hidden" name="host" value="<?= htmlspecialchars($host) ?>">
        <input type="hidden" name="user" value="<?= htmlspecialchars($user) ?>">
        <input type="hidden" name="port" value="<?= htmlspecialchars($port) ?>">

        <label class="block mb-2">Masukkan Perintah:</label>
        <input type="text" name="command" autofocus required placeholder="Contoh: ls -lah /etc"
               class="w-full p-2 mb-4 rounded bg-gray-700 text-white">

        <button type="submit" class="bg-blue-600 px-4 py-2 rounded hover:bg-blue-700 w-full">Jalankan</button>
    </form>

    <?php if ($command !== ''): ?>
        <div class="bg-black text-green-400 font-mono p-6 rounded shadow w-full max-w-3xl overflow-auto">
            <p class="mb-2 text-sm text-yellow-400">Perintah: <code><?= htmlspecialchars($command) ?></code></p>
            <pre><?= htmlspecialchars($output ?: '[Perintah tidak menghasilkan output]') ?></pre>
        </div>
    <?php endif; ?>
</body>
</html>

