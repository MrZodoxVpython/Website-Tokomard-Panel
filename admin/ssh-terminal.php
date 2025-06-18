<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ambil data koneksi
$host = $_POST['host'] ?? $_GET['host'] ?? '';
$user = $_POST['user'] ?? $_GET['user'] ?? 'root';
$port = 7681; // port ttyd default
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Akses Terminal Interaktif</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-black text-white flex flex-col items-center justify-center min-h-screen p-6">
    <h1 class="text-2xl font-bold mb-4">Akses Terminal: <?= htmlspecialchars($host) ?></h1>

    <p class="mb-6 text-center text-gray-300">
        Klik tombol di bawah untuk membuka terminal penuh via browser untuk akun: <br>
        <code class="text-green-400"><?= htmlspecialchars($user) ?>@<?= htmlspecialchars($host) ?></code><br>
        Port: <code class="text-yellow-400"><?= $port ?></code>
    </p>

    <a href="http://<?= $host ?>:<?= $port ?>" target="_blank"
       class="bg-green-600 px-6 py-3 rounded-lg hover:bg-green-700 transition font-semibold">
        Buka Terminal Interaktif
    </a>

    <p class="text-sm mt-6 text-gray-500">
        Pastikan VPS memiliki <code>ttyd</code> aktif seperti: <br>
        <code>ttyd -p <?= $port ?> -c <?= $user ?>:password /bin/login</code>
    </p>
</body>
</html>

