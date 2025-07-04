<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../../koneksi.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'reseller') {
    header("Location: ../index.php");
    exit;
}

$reseller = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en" class="transition duration-300">
<head>
    <meta charset="UTF-8">
    <title>Topup Saldo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        darkMode: 'class'
      }
    </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-100 min-h-screen flex items-center justify-center transition duration-300">
    <div class="w-full max-w-2xl p-6 bg-white dark:bg-gray-800 shadow-xl rounded-3xl">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">💰 Topup Saldo</h1>
            <button id="toggleDark" class="flex items-center gap-2 text-sm bg-gray-200 dark:bg-gray-700 px-3 py-1 rounded-full transition">
                <span id="modeIcon">🌙</span> Mode
            </button>
        </div>

        <form action="proses_topup.php" method="POST" class="space-y-6">
            <div>
                <label class="block font-semibold mb-1">Username Anda</label>
                <input type="text" readonly value="<?= htmlspecialchars($reseller) ?>" class="w-full px-4 py-2 rounded border dark:bg-gray-700 dark:text-white">
            </div>

            <div>
                <label class="block font-semibold mb-2">Nominal Topup</label>
                <input type="number" name="nominal" min="1000" step="500" required placeholder="Contoh: 10000" class="w-full px-4 py-2 border rounded dark:bg-gray-700 dark:text-white">
            </div>

            <div>
                <label class="block font-semibold mb-2">Metode Pembayaran</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <label class="flex flex-col items-center bg-gray-50 dark:bg-gray-700 p-4 rounded-xl shadow cursor-pointer">
                        <input type="radio" name="metode" value="qris" required class="hidden">
                        <img src="https://img.icons8.com/ios-filled/100/000000/qr-code.png" class="w-12 h-12 mb-2" alt="QRIS"/>
                        <span class="text-sm font-semibold">QRIS</span>
                    </label>
                    <label class="flex flex-col items-center bg-gray-50 dark:bg-gray-700 p-4 rounded-xl shadow cursor-pointer">
                        <input type="radio" name="metode" value="dana" required class="hidden">
                        <img src="https://img.icons8.com/color/96/dana.png" class="w-12 h-12 mb-2" alt="Dana"/>
                        <span class="text-sm font-semibold">DANA</span>
                    </label>
                    <label class="flex flex-col items-center bg-gray-50 dark:bg-gray-700 p-4 rounded-xl shadow cursor-pointer">
                        <input type="radio" name="metode" value="bank" required class="hidden">
                        <img src="https://img.icons8.com/color/96/bank-building.png" class="w-12 h-12 mb-2" alt="Bank"/>
                        <span class="text-sm font-semibold">Bank Transfer</span>
                    </label>
                </div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-xl text-sm">
                <p class="font-semibold mb-1">📌 Info Pembayaran:</p>
                <ul class="list-disc ml-5">
                    <li><strong>QRIS:</strong> Scan QR dari admin (hubungi CS)</li>
                    <li><strong>DANA:</strong> 0812-3456-7890 a.n. TOKOMARD</li>
                    <li><strong>Bank:</strong> BCA 1234567890 a.n. TOKOMARD</li>
                </ul>
            </div>

            <div>
                <label class="block font-semibold mb-1">Catatan (Opsional)</label>
                <textarea name="catatan" class="w-full px-4 py-2 rounded border dark:bg-gray-700 dark:text-white" placeholder="Contoh: Sudah transfer via Dana"></textarea>
            </div>

            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-xl font-bold text-lg transition">
                🚀 Konfirmasi Topup
            </button>
        </form>
    </div>

    <script>
      const toggle = document.getElementById('toggleDark');
      const icon = document.getElementById('modeIcon');

      toggle.addEventListener('click', () => {
          document.documentElement.classList.toggle('dark');
          const isDark = document.documentElement.classList.contains('dark');
          icon.textContent = isDark ? '☀️' : '🌙';
          localStorage.setItem('mode', isDark ? 'dark' : 'light');
      });

      if (localStorage.getItem('mode') === 'dark') {
          document.documentElement.classList.add('dark');
          icon.textContent = '☀️';
      } else {
          icon.textContent = '🌙';
      }
    </script>
</body>
</html>

