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

<div class="w-screen min-h-screen bg-gray-100 dark:bg-gray-900 pt-16 px-4 sm:ml-64 overflow-x-hidden">
  <div class="max-w-full mx-auto">
    <!-- Kotak Statistik -->
    <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
      <div class="flex flex-col items-center justify-center h-24 bg-green-500 text-white rounded-md font-semibold">
        Total Akun <div class="text-2xl"><?= $total ?></div>
      </div>
      <div class="flex flex-col items-center justify-center h-24 bg-blue-600 text-white rounded-md font-semibold">
        VMess <div class="text-2xl"><?= $vmess ?></div>
      </div>
      <div class="flex flex-col items-center justify-center h-24 bg-purple-500 text-white rounded-md font-semibold">
        VLess <div class="text-2xl"><?= $vless ?></div>
      </div>
      <div class="flex flex-col items-center justify-center h-24 bg-red-600 text-white rounded-md font-semibold">
        Trojan <div class="text-2xl"><?= $trojan ?></div>
      </div>
      <div class="flex flex-col items-center justify-center h-24 bg-yellow-600 text-white rounded-md font-semibold">
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
    <div class="overflow-x-auto shadow-md sm:rounded-lg mb-12">
      <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
          <tr>
            <th class="px-6 py-3">No</th>
            <th class="px-6 py-3">Username</th>
            <th class="px-6 py-3">Protocol</th>
            <th class="px-6 py-3">Expired</th>
            <th class="px-6 py-3">UUID / Password</th>
            <th class="px-6 py-3">Status</th>
            <th class="px-6 py-3">Action</th>
          </tr>
        </thead>
        <tbody>
          <tr class="bg-white dark:bg-gray-800">
            <td colspan="7" class="px-6 py-4 text-center">Belum ada akun.</td>
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

