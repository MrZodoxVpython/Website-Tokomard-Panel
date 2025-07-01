<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$reseller = $_SESSION['reseller'] ?? $_SESSION['username'] ?? 'unknown';

// Contoh data dummy, kamu bisa ganti dengan data asli
$total = 0;
$vmess = 0;
$vless = 0;
$trojan = 0;
$shadowsocks = 0;
?>

<div class="p-4 sm:ml-64 bg-gray-100 dark:bg-gray-900 min-h-screen">
  <div class="p-4 rounded-lg mt-14">
    <!-- Statistik Kotak -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-5 gap-4 mb-4">
      <div class="flex items-center justify-center h-24 rounded bg-green-500 text-white text-lg font-semibold">Total Akun<br><?= $total ?></div>
      <div class="flex items-center justify-center h-24 rounded bg-blue-600 text-white text-lg font-semibold">VMess<br><?= $vmess ?></div>
      <div class="flex items-center justify-center h-24 rounded bg-purple-500 text-white text-lg font-semibold">VLess<br><?= $vless ?></div>
      <div class="flex items-center justify-center h-24 rounded bg-red-600 text-white text-lg font-semibold">Trojan<br><?= $trojan ?></div>
      <div class="flex items-center justify-center h-24 rounded bg-yellow-700 text-white text-lg font-semibold">Shadowsocks<br><?= $shadowsocks ?></div>
    </div>

    <!-- Grafik (Fix Terpotong) -->
    <div class="mb-8 bg-white dark:bg-gray-800 p-4 rounded-lg shadow w-full overflow-x-auto">
      <div class="min-w-[500px] h-[300px] relative">
        <canvas id="myChart" class="w-full h-full"></canvas>
      </div>
    </div>

    <!-- Tabel Akun -->
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
      <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
          <tr>
            <th scope="col" class="px-6 py-3">No</th>
            <th scope="col" class="px-6 py-3">Username</th>
            <th scope="col" class="px-6 py-3">Protocol</th>
            <th scope="col" class="px-6 py-3">Expired</th>
            <th scope="col" class="px-6 py-3">UUID / Password</th>
            <th scope="col" class="px-6 py-3">Status</th>
            <th scope="col" class="px-6 py-3">Action</th>
          </tr>
        </thead>
        <tbody>
          <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
            <td colspan="7" class="px-6 py-4 text-center">Belum ada akun.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const ctx = document.getElementById('myChart').getContext('2d');
  const myChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['VMess', 'VLess', 'Trojan', 'Shadowsocks'],
      datasets: [{
        label: 'Jumlah Akun',
        data: [<?= $vmess ?>, <?= $vless ?>, <?= $trojan ?>, <?= $shadowsocks ?>],
        backgroundColor: ['#2563eb', '#9333ea', '#dc2626', '#d97706'],
        borderColor: ['#1d4ed8', '#7e22ce', '#b91c1c', '#b45309'],
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            precision: 0
          }
        }
      }
    }
  });
</script>

