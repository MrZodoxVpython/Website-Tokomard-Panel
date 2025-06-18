<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = $_POST['host'] ?? $_GET['host'] ?? '';
$user = $_POST['user'] ?? $_GET['user'] ?? 'root';
$port = $_POST['port'] ?? $_GET['port'] ?? 22;
$password = $_POST['password'] ?? null;
$output = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $password) {
    if (!function_exists('ssh2_connect')) {
        $error = "Ekstensi ssh2 belum terpasang di PHP server.";
    } else {
        $connection = ssh2_connect($host, $port);
        if (!$connection) {
            $error = "Gagal terkoneksi ke server $host.";
        } else {
            if (!ssh2_auth_password($connection, $user, $password)) {
                $error = "Autentikasi gagal. Username/password salah.";
            } else {
                $stream = ssh2_exec($connection, 'uptime && whoami && hostname');
                stream_set_blocking($stream, true);
                $output = stream_get_contents($stream);
                fclose($stream);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Akses Terminal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen flex flex-col items-center justify-center p-6">
    <h1 class="text-2xl font-bold mb-4">Akses Shell VPS</h1>

    <?php if ($error): ?>
        <div class="bg-red-600 text-white p-4 rounded mb-4 w-full max-w-md"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($output): ?>
        <div class="bg-green-800 text-white p-4 rounded mb-4 w-full max-w-md whitespace-pre"><?php echo htmlspecialchars($output); ?></div>
    <?php endif; ?>

    <form method="post" class="bg-gray-800 p-6 rounded shadow w-full max-w-md">
        <input type="hidden" name="host" value="<?= htmlspecialchars($host) ?>">
        <input type="hidden" name="user" value="<?= htmlspecialchars($user) ?>">
        <input type="hidden" name="port" value="<?= htmlspecialchars($port) ?>">

        <label class="block mb-2">Password untuk <?= htmlspecialchars($user) ?>@<?= htmlspecialchars($host) ?>:</label>
        <input type="password" name="password" required class="w-full p-2 mb-4 rounded bg-gray-700 text-white">

        <button type="submit" class="bg-blue-600 px-4 py-2 rounded hover:bg-blue-700 w-full">Login dan Jalankan Tes</button>
    </form>
</body>
</html>

