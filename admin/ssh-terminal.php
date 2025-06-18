<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Ambil data koneksi dari POST atau GET
$host = $_POST['host'] ?? $_GET['host'] ?? null;
$user = $_POST['user'] ?? $_GET['user'] ?? 'root';
$port = $_POST['port'] ?? $_GET['port'] ?? 22;

if (!$host) {
    echo "Host tidak ditemukan.";
    exit;
}

// Misal kamu punya ttyd aktif di setiap VPS di port 7681
// Pastikan di setiap VPS sudah ada ttyd aktif seperti ini:
// ttyd -p 7681 -c benjamin:password -P /bin/login

$ttydPort = 7681; // atau ganti sesuai setup kamu

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Terminal Akses VPS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-black text-white flex flex-col items-center justify-center min-h-screen p-6">
    <h1 class="text-2xl font-bold mb-4">Akses Terminal: <?= htmlspecialchars($host) ?></h1>
    <p class="mb-6">Klik tombol di bawah untuk membuka akses shell ke VPS (<?= htmlspecialchars($user) ?>@<?= htmlspecialchars($host) ?>)</p>

    <a href="http://<?= $host ?>:<?= $ttydPort ?>" target="_blank"
       class="bg-green-600 px-6 py-3 rounded-lg hover:bg-green-700 transition font-semibold">
        Buka Terminal
    </a>

    <p class="text-sm mt-4 text-gray-400">Pastikan ttyd aktif di VPS ini di port <?= $ttydPort ?></p>
</body>
</html>

