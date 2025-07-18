<?php
session_start();
require '../../koneksi.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

$reseller = $_SESSION['username'];
$result = mysqli_query($conn, "SELECT saldo FROM users WHERE username = '$reseller'");
$row = mysqli_fetch_assoc($result);
$saldo = $row['saldo'];
?>
<!DOCTYPE html>
<html lang="en" class="transition duration-300">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Saldo Reseller</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        darkMode: 'class'
      }
    </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-100 min-h-screen flex items-center justify-center transition duration-300">
    <div class="w-full max-w-xl bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">👛 Saldo Rekening</h1>
            <span class="px-4 py-1 text-sm bg-blue-100 dark:bg-blue-900 dark:text-blue-200 text-blue-700 rounded-full shadow">
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

        <div class="mt-6 flex items-center justify-between">
            <a href="histori.php" class="text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition duration-150 underline">
                📜 Lihat Riwayat Saldo
            </a>
            <button id="toggleDark" class="flex items-center gap-2 bg-gray-200 dark:bg-gray-700 px-4 py-2 rounded-full text-sm font-medium text-gray-800 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                <span id="modeIcon">🌙</span> <span>Ganti Mode</span>
            </button>
        </div>
    </div>

    <script>
      const toggle = document.getElementById('toggleDark');
      const icon = document.getElementById('modeIcon');

      // Toggle class
      toggle.addEventListener('click', () => {
          document.documentElement.classList.toggle('dark');
          const isDark = document.documentElement.classList.contains('dark');
          icon.textContent = isDark ? '☀️' : '🌙';
          localStorage.setItem('mode', isDark ? 'dark' : 'light');
      });

      // Apply mode from localStorage
      if (localStorage.getItem('mode') === 'dark') {
          document.documentElement.classList.add('dark');
          icon.textContent = '☀️';
      } else {
          icon.textContent = '🌙';
      }
    </script>
</body>
</html>

