<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Autentikasi user dulu
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Konfigurasi default
$host = $_POST['host'] ?? $_GET['host'] ?? '';
$user = $_POST['user'] ?? $_GET['user'] ?? 'root';
$port = $_POST['port'] ?? $_GET['port'] ?? 22;
$password = $_POST['password'] ?? '';
$authSuccess = $_SESSION['auth_success'] ?? false;
$error = '';

// Autentikasi SSH hanya sekali
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if (!function_exists('ssh2_connect')) {
        $error = "Ekstensi ssh2 tidak tersedia.";
    } else {
        $connection = @ssh2_connect($host, $port);
        if (!$connection) {
            $error = "Gagal konek ke $host.";
        } elseif (!@ssh2_auth_password($connection, $user, $password)) {
            $error = "Autentikasi gagal. Password salah.";
        } else {
            $_SESSION['auth_success'] = true;
            $authSuccess = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🖥 VPS Shell Access</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen flex flex-col items-center justify-center p-6">
    <h1 class="text-2xl font-bold mb-6">🖥 VPS Shell Access - <?= htmlspecialchars($host ?: 'Localhost') ?></h1>

    <?php if ($error): ?>
        <div class="bg-red-600 p-4 rounded w-full max-w-md"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($authSuccess): ?>
        <form method="post" class="bg-gray-800 p-6 rounded shadow w-full max-w-3xl">
            <input type="hidden" name="host" value="<?= htmlspecialchars($host) ?>">
            <input type="hidden" name="user" value="<?= htmlspecialchars($user) ?>">
            <input type="hidden" name="port" value="<?= htmlspecialchars($port) ?>">

            <label class="block mb-2">Masukkan Perintah:</label>
            <input type="text" name="command" autofocus required class="w-full p-2 mb-4 rounded bg-gray-700 text-white" placeholder="contoh: ls -lah /var/www">

            <button type="submit" class="bg-blue-600 px-4 py-2 rounded hover:bg-blue-700 w-full">Jalankan</button>
        </form>

        <?php if (!empty($_POST['command'])):
            $command = $_POST['command'];
            $output = shell_exec($command);
        ?>
            <div class="bg-black text-green-400 font-mono p-6 mt-6 w-full max-w-3xl rounded shadow overflow-x-auto">
                <pre><?= htmlspecialchars($output ?: '[Perintah tidak menghasilkan output]') ?></pre>
            </div>
        <?php endif; ?>

        <form method="post" action="logout.php" class="mt-4">
            <button class="text-sm text-gray-400 hover:text-red-400 underline">🔒 Keluar</button>
        </form>
    <?php else: ?>
        <form method="post" class="bg-gray-800 p-6 rounded shadow w-full max-w-md">
            <input type="hidden" name="host" value="<?= htmlspecialchars($host) ?>">
            <input type="hidden" name="user" value="<?= htmlspecialchars($user) ?>">
            <input type="hidden" name="port" value="<?= htmlspecialchars($port) ?>">

            <label class="block mb-2">Password untuk <?= htmlspecialchars($user) ?>@<?= htmlspecialchars($host) ?>:</label>
            <input type="password" name="password" required class="w-full p-2 mb-4 rounded bg-gray-700 text-white">

            <button type="submit" class="bg-blue-600 px-4 py-2 rounded hover:bg-blue-700 w-full">Login & Verifikasi</button>
        </form>
    <?php endif; ?>
</body>
</html>

