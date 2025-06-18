<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Ambil parameter koneksi
$host = $_POST['host'] ?? $_GET['host'] ?? '';
$user = $_POST['user'] ?? $_GET['user'] ?? 'root';
$port = $_POST['port'] ?? $_GET['port'] ?? 22;
$password = $_POST['password'] ?? '';
$authSuccess = false;
$error = '';
$ttydPort = 7681;

// Cek password via SSH
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $password) {
    if (!function_exists('ssh2_connect')) {
        $error = "Ekstensi ssh2 tidak tersedia di PHP.";
    } else {
        $connection = @ssh2_connect($host, $port);
        if (!$connection) {
            $error = "Gagal konek ke $host.";
        } elseif (!@ssh2_auth_password($connection, $user, $password)) {
            $error = "Autentikasi gagal. Password salah.";
        } else {
            $authSuccess = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Akses Terminal VPS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen flex flex-col items-center justify-center p-6">

    <h1 class="text-2xl font-bold mb-6">Akses Terminal: <?= htmlspecialchars($host) ?></h1>

    <?php if ($error): ?>
        <div class="bg-red-600 text-white p-4 rounded mb-4 w-full max-w-md"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($authSuccess): ?>
        <div class="bg-green-700 text-white p-4 rounded mb-6 w-full max-w-md">
            ✅ Autentikasi berhasil sebagai <strong><?= htmlspecialchars($user) ?></strong> di <strong><?= htmlspecialchars($host) ?></strong>.
        </div>
        <a href="http://<?= $host ?>:<?= $ttydPort ?>" target="_blank"
           class="bg-green-600 px-6 py-3 rounded-lg hover:bg-green-700 transition font-semibold">
           Buka Terminal Interaktif
        </a>
        <p class="text-sm text-gray-400 mt-4">Pastikan <code>ttyd</code> aktif di VPS pada port <?= $ttydPort ?>.</p>
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

