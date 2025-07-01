<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Akun Xray</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-white">
  <div class="max-w-screen-xl mx-auto px-4 py-6">
    <?php
    $reseller = $_SESSION['reseller'] ?? 'unknown';

    $stats = ['total' => 0, 'vmess' => 0, 'vless' => 0, 'trojan' => 0, 'shadowsocks' => 0];
    $rows = [];
    $dir = "/etc/xray/data-panel/reseller/";
    $no = 1;

    foreach (glob("{$dir}akun-{$reseller}-*.txt") as $file) {
        $buyer = basename($file, ".txt");
        $buyer = str_replace("akun-{$reseller}-", "", $buyer);
        $lines = file($file);
        $proto = null;
        $expired = "-";
        $uuidOrPass = "-";
        foreach ($lines as $line) {
            if (stripos($line, 'TROJAN ACCOUNT') !== false) $proto = 'trojan';
            elseif (stripos($line, 'VMESS ACCOUNT') !== false) $proto = 'vmess';
            elseif (stripos($line, 'VLESS ACCOUNT') !== false) $proto = 'vless';
            elseif (stripos($line, 'SHADOWSOCKS ACCOUNT') !== false) $proto = 'shadowsocks';
            elseif (stripos($line, 'Expired On') !== false) {
                $expParts = explode(':', $line, 2);
                $expired = trim($expParts[1] ?? '-');
            }
            if (stripos($line, 'Password') !== false) {
                $uuidOrPass = trim(explode(':', $line, 2)[1] ?? '-');
            }
        }
        if ($proto) {
            $stats[$proto]++;
            $stats['total']++;
            $rows[] = [
                'no' => $no++, 'user' => $buyer,
                'proto' => strtoupper($proto), 'exp' => $expired, 'buyer' => $uuidOrPass
            ];
        }
    }
    ?>

    <!-- Statistik -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 mb-6">
    <?php
    foreach (['total' => 'Total Akun', 'vmess' => 'VMess', 'vless' => 'VLess', 'trojan' => 'Trojan', 'shadowsocks' => 'Shadowsocks'] as $k => $label) {
        $color = ['total' => 'green', 'vmess' => 'blue', 'vless' => 'purple', 'trojan' => 'red', 'shadowsocks' => 'yellow'][$k];
        echo "<div class='bg-{$color}-100 dark:bg-{$color}-800 text-{$color}-900 dark:text-white p-4 rounded-lg shadow'>
            <p class='text-sm sm:text-base font-semibold'>{$label}</p>
            <p class='text-xl sm:text-2xl mt-2 font-bold'>{$stats[$k]}</p>
        </div>";
    }
    ?>
    </div>

    <!-- Grafik -->
    <div class="mb-8 bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
      <div class="w-full h-[300px] sm:h-[400px] relative">
        <canvas id="myChart" class="w-full h-full"></canvas>
      </div>
    </div>

    <!-- Tabel -->
    <div class="w-full overflow-x-auto rounded-lg border border-gray-300 dark:border-gray-700 shadow">
      <table class="w-full text-sm text-left text-gray-700 dark:text-white">
        <thead class="bg-gray-100 dark:bg-gray-700">
          <tr>
            <th class="px-4 py-2 whitespace-nowrap">No</th>
            <th class="px-4 py-2 whitespace-nowrap">Username</th>
            <th class="px-4 py-2 whitespace-nowrap">Protocol</th>
            <th class="px-4 py-2 whitespace-nowrap">Expired</th>
            <th class="px-4 py-2 whitespace-nowrap">UUID / Pass</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="5" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">Belum ada akun.</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
              <td class="px-4 py-2"><?= $r['no'] ?></td>
              <td class="px-4 py-2 break-words"><?= $r['user'] ?></td>
              <td class="px-4 py-2"><?= $r['proto'] ?></td>
              <td class="px-4 py-2"><?= $r['exp'] ?></td>
              <td class="px-4 py-2 break-all font-mono"><?= $r['buyer'] ?></td>
            </tr>
          <?php endforeach ?>
        <?php endif ?>
        </tbody>
      </table>
    </div>
  </div>

  <script>
  const ctx = document.getElementById("myChart").getContext("2d");
  new Chart(ctx, {
      type: "bar",
      data: {
          labels: ["VMess", "VLess", "Trojan", "Shadowsocks"],
          datasets: [{
              label: "Akun Terjual",
              data: [<?= $stats['vmess'] ?>, <?= $stats['vless'] ?>, <?= $stats['trojan'] ?>, <?= $stats['shadowsocks'] ?>],
              backgroundColor: ["#6366f1", "#3b82f6", "#ef4444", "#10b981"],
              borderRadius: 8
          }]
      },
      options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
              legend: { display: false },
              tooltip: {
                  backgroundColor: "#1f2937",
                  titleColor: "#fff",
                  bodyColor: "#ddd"
              }
          },
          scales: {
              y: { beginAtZero: true, ticks: { color: "#94a3b8" } },
              x: { ticks: { color: "#94a3b8" } }
          }
      }
  });
  </script>
</body>
</html>

