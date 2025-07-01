<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$reseller = $_SESSION['reseller'] ?? $_SESSION['username'] ?? 'unknown';

// Contoh data dummy
$total = 0;
$vmess = 0;
$vless = 0;
$trojan = 0;
$shadowsocks = 0;
?>

<!-- Container Full Screen -->
<div class="w-full min-h-screen bg-gray-100 dark:bg-gray-900 pt-16 px-2 sm:px-4 overflow-x-hidden">
  <div class="max-w-full mx-auto">

    <!-- Statistik Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 sm:gap-4 mb-6">
      <div class="flex flex-col items-center justify-center h-24 bg-green-500 text-white rounded-lg font-semibold shadow">
        Total Akun <div class="text-2xl"><?= $total ?></div>
      </div>
      <div class="flex flex-col items-center justify-center h-24 bg-blue-600 text-white rounded-lg font-semibold shadow">
        VMess <div class="text-2xl"><?= $vmess ?></div>
      </div>
      <div class="flex flex-col items-center justify-center h-24 bg-purple-500 text-white rounded-lg font-semibold shadow">
        VLess <div class="text-2xl"><?= $vless ?></div>
      </div>
      <div class="flex flex-col items-center justify-center h-24 bg-red-600 text-white rounded-lg font-semibold shadow">
        Trojan <div class="text-2xl"><?= $trojan ?></div>
      </div>
      <div class="flex flex-col items-center justify-center h-24 bg-yellow-600 text-white rounded-lg font-semibold shadow">
        Shadowsocks <div class="text-2xl"><?= $shadowsocks ?></div>
      </div>
    </div>

    <!-- Grafik -->
    <div class="w-full bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
      <div class="w-full h-[300px] relative">
        <canvas id="myChart"></canvas>
      </div>
    </div>

    <!-- Tabel -->
    <div class="w-full overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow mb-12">
      <table class="min-w-full text-sm text-left text-gray-700 dark:text-gray-300">
        <thead class="text-xs uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-200">
          <tr>
            <th class="px-4 py-3">No</th>
            <th class="px-4 py-3">Username</th>
            <th class="px-4 py-3">Protocol</th>
            <th class="px-4 py-3">Expired</th>
            <th class="px-4 py-3">UUID / Password</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">Action</th>
          </tr>
        </thead>
        <tbody>
          <tr class="border-t dark:border-gray-700">
            <td colspan="7" class="text-center px-4 py-4">Belum ada akun.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ChartJS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('myChart').getContext('2d');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: ['VMess', 'VLess', 'Trojan', 'Shadowsocks'],
    datasets: [{
      label: 'Jumlah Akun',
      data: [<?= $vmess ?>, <?= $vless ?>, <?= $trojan ?>, <?= $shadowsocks ?>],
      backgroundColor: ['#3b82f6', '#a855f7', '#ef4444', '#f59e0b'],
      borderWidth: 1
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      y: {
        beginAtZero: true,
        ticks: { precision: 0 }
      }
    }
  }
});
</script>

