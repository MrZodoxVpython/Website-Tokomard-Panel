<?php
session_start();
require '../../koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit;
}

$reseller = $_SESSION['username'];
$result = mysqli_query($conn, "SELECT saldo FROM users WHERE username = '$reseller'");
$row = mysqli_fetch_assoc($result);
$saldo = $row['saldo'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Saldo Reseller</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-100 via-white to-purple-100 min-h-screen flex items-center justify-center font-sans">
    <div class="w-full max-w-xl bg-white rounded-3xl shadow-xl p-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800">👛 Saldo Rekening</h1>
            <span class="px-4 py-1 text-sm bg-blue-100 text-blue-700 rounded-full shadow">
                <?= htmlspecialchars($reseller) ?>
            </span>
        </div>

        <div class="bg-gradient-to-r from-green-400 to-emerald-500 text-white text-center p-6 rounded-xl shadow-lg mb-8">
            <p class="text-sm uppercase font-semibold tracking-wide">Saldo Anda</p>
            <h2 class="text-3xl font-extrabold mt-1">Rp <?= number_format($saldo, 0, ',', '.') ?></h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <a href="tambah.php" class="bg-indigo-500 hover:bg-indigo-600 text-white py-3 rounded-xl shadow text-center font-semibold transition duration-200">
                ➕ Tambah Saldo
            </a>
            <a href="kurangi.php" class="bg-red-500 hover:bg-red-600 text-white py-3 rounded-xl shadow text-center font-semibold transition duration-200">
                ➖ Kurangi Saldo
            </a>
        </div>

        <div class="mt-6 text-center">
            <a href="histori.php" class="text-sm text-gray-600 hover:text-indigo-600 transition duration-150 underline">
                📜 Lihat Riwayat Saldo
            </a>
        </div>
    </div>
</body>
</html>

