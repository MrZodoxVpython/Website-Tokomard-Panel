<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard Tokomard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      overflow-x: hidden;
    }
  </style>
</head>
<body class="bg-gray-900 text-white">

  <!-- Header -->
  <div class="flex justify-between items-center p-4">
    <h1 class="text-xl font-bold">Panel Reseller</h1>
    <button class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">Logout</button>
  </div>

  <!-- Statistik -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 px-4 mb-6">
    <div class="bg-green-500 rounded p-4 text-center">
      Total Akun<br><span class="text-2xl font-bold">0</span>
    </div>
    <div class="bg-blue-500 rounded p-4 text-center">
      VMess<br><span class="text-2xl font-bold">0</span>
    </div>
    <div class="bg-purple-500 rounded p-4 text-center">
      VLess<br><span class="text-2xl font-bold">0</span>
    </div>
    <div class="bg-red-600 rounded p-4 text-center">
      Trojan<br><span class="text-2xl font-bold">0</span>
    </div>
    <div class="bg-yellow-500 rounded p-4 text-center">
      Shadowsocks<br><span class="text-2xl font-bold">0</span>
    </div>
  </div>

  <!-- Grafik Chart -->
  <div class="px-4 mb-6">
    <div class="bg-gray-800 rounded p-4">
      <canvas id="akunChart" class="w-full max-w-full"></canvas>
    </div>
  </div>

  <!-- Tabel Akun -->
  <div class="px-4 mb-10">
    <div class="overflow-x-auto bg-gray-800 rounded p-4">
      <table class="min-w-full text-sm text-white">
        <thead>
          <tr class="bg-gray-700">
            <th class="px-4 py-2">NO</th>
            <th class="px-4 py-2">USERNAME</th>
            <th class="px-4 py-2">PROTOCOL</th>
            <th class="px-4 py-2">EXPIRED</th>
            <th class="px-4 py-2">UUID / PASSWORD</th>
            <th class="px-4 py-2">STATUS</th>
            <th class="px-4 py-2">ACTION</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td colspan="7" class="text-center py-4">Belum ada akun.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Chart.js Script -->
  <script>
    const ctx = document.getElementById('akunChart').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['VMess', 'VLess', 'Trojan', 'Shadowsocks'],
        datasets: [{
          label: 'Jumlah Akun',
          data: [0, 0, 0, 0],
          backgroundColor: ['#3B82F6', '#A855F7', '#EF4444', '#F59E0B']
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false
      }
    });
  </script>

</body>
</html>

