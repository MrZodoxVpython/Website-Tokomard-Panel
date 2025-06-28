<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'reseller') {
    header("Location: ../index.php");
    exit;
}

$reseller = $_SESSION['username'];
$theme = $_SESSION['theme'] ?? 'light';
$page = $_GET['page'] ?? 'dashboard';

function getAvatar($name) {
    return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=4F46E5&color=fff';
}

function getStats($reseller) {
    $stats = ['total'=>0,'vmess'=>0,'vless'=>0,'trojan'=>0,'shadowsocks'=>0];
    $rows = [];
    $dir = "/etc/xray/data-panel/reseller/";
    $no = 1;

    foreach (glob("{$dir}akun-{$reseller}-*.txt") as $file) {
        $buyer = basename($file, ".txt");
        $buyer = str_replace("akun-{$reseller}-","",$buyer);
        $content = file_get_contents($file);
        if (strpos($content, 'TROJAN ACCOUNT') !== false) $proto = 'trojan';
        elseif (strpos($content, 'VMESS ACCOUNT') !== false) $proto = 'vmess';
        elseif (strpos($content, 'VLESS ACCOUNT') !== false) $proto = 'vless';
        elseif (strpos($content, 'SHADOWSOCKS ACCOUNT') !== false) $proto = 'shadowsocks';
        else continue;
        $exp = '-';
        if (preg_match('/Expired On\s+:\s+([0-9\-]+)/', $content, $m)) $exp = $m[1];
        if (preg_match('/Remarks\s+:\s+(.+)/', $content, $m)) $user = $m[1];
        else $user = '-';

        $rows[] = [
            'no' => $no++,
            'user' => $user,
            'proto' => strtoupper($proto),
            'exp' => $exp,
            'buyer' => $buyer
        ];
        $stats[$proto]++;
        $stats['total']++;
    }

    return [$stats, $rows];
}

// Theme switcher
if (isset($_GET['theme'])) {
    $_SESSION['theme'] = $_GET['theme'];
    header("Location: reseller.php?page=$page");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id" class="<?= $theme === 'dark' ? 'dark' : '' ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reseller Panel</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>tailwind.config = { darkMode: 'class' }</script>
</head>
<body class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white transition min-h-screen">
<header class="p-4 bg-gray-100 dark:bg-gray-800 shadow-md flex justify-between items-center sticky top-0 z-50">
  <h1 class="text-xl font-bold">Panel Reseller</h1>
  <div class="flex gap-2 items-center">
    <a href="?theme=<?= $theme === 'dark' ? 'light' : 'dark' ?>" class="text-xl">
      <?= $theme === 'dark' ? '🌞' : '🌙' ?>
    </a>
    <a href="../logout.php" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-500 text-sm">Logout</a>
  </div>
</header>

<main class="flex flex-col md:flex-row px-4 md:px-8 py-4 gap-6">
  <aside class="md:w-1/5 bg-gray-100 dark:bg-gray-800 p-4 rounded shadow">
    <div class="text-center mb-4">
      <img src="<?= getAvatar($reseller) ?>" alt="avatar" class="w-20 h-20 rounded-full mx-auto mb-2">
      <p class="font-semibold">@<?= htmlspecialchars($reseller) ?></p>
    </div>
    <nav class="space-y-2 text-sm">
<nav class="space-y-2 text-sm">
<?php
$menu = [
  'dashboard'=>'📊 Dashboard','ssh'=>'🔐 SSH','vmess'=>'🌀 Vmess',
  'vless'=>'📡 Vless','trojan'=>'⚔ Trojan','shadowsocks'=>'🕶 Shadowsocks',
  'topup'=>'💳 Top Up','cek-server'=>'🖥 Cek Server','vip'=>'👑 Grup VIP'
];
foreach ($menu as $p => $label) {
    echo "<a href='?page={$p}' class='block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600'>$label</a>";

    // Tambah garis setelah Shadowsocks
    if ($p === 'shadowsocks') {
	    echo "<div class='my-3 border-t border-blue-500 dark:border-blue-400 bg-yellow-200 h-[2px]'></div>";

    }

}
?>
</nav>

    </nav>
  </aside>

  <section class="flex-1 bg-white dark:bg-gray-900 p-4 rounded shadow overflow-x-auto">
    <?php
    if ($page === 'dashboard') {
      list($stats, $rows) = getStats($reseller);

      echo '<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">';
      foreach (['total'=>'Total Akun','vmess'=>'VMess','vless'=>'VLess','trojan'=>'Trojan','shadowsocks'=>'Shadowsocks'] as $k=>$label) {
        $color = [
          'total'=>'blue','vmess'=>'indigo','vless'=>'cyan','trojan'=>'red','shadowsocks'=>'green'
        ][$k];
        echo "
        <div class='bg-{$color}-100 dark:bg-{$color}-800 text-{$color}-900 dark:text-{$color}-200 p-4 rounded shadow text-center'>
          <p class='text-sm font-semibold'>{$label}</p>
          <p class='text-2xl font-bold'>{$stats[$k]}</p>
        </div>";
      }
      echo '</div>';

      echo '<canvas id="akunChart" class="mb-6 w-full max-w-4xl mx-auto"></canvas>';

      echo '<div class="overflow-x-auto">';
      echo '<table class="table-fixed w-full text-sm border border-gray-300 dark:border-gray-600">';
      echo '<thead class="bg-gray-200 dark:bg-gray-700">';
      echo '<tr>';
      echo '<th class="w-12 p-2">No</th>';
      echo '<th class="w-1/5 p-2">User</th>';
      echo '<th class="w-1/6 p-2">Proto</th>';
      echo '<th class="w-1/5 p-2">Expired</th>';
      echo '<th class="p-2">Buyer</th>';
      echo '</tr></thead><tbody>';
      if (empty($rows)) {
        echo '<tr><td colspan="5" class="text-center py-4 text-gray-500 dark:text-gray-400">Belum ada akun Xray</td></tr>';
      } else {
        foreach ($rows as $r) {
          echo "<tr class='hover:bg-gray-100 dark:hover:bg-gray-700'>";
          echo "<td class='text-center p-2'>{$r['no']}</td>";
          echo "<td class='p-2'>{$r['user']}</td>";
          echo "<td class='text-center p-2'>{$r['proto']}</td>";
          echo "<td class='text-center p-2'>{$r['exp']}</td>";
          echo "<td class='p-2'>{$r['buyer']}</td>";
          echo "</tr>";
        }
      }
      echo '</tbody></table></div>';
    } elseif (file_exists(__DIR__."/pages/{$page}.php")) {
      include __DIR__."/pages/{$page}.php";
    } else {
      echo "<p class='text-center text-red-500'>Halaman <b>{$page}</b> tidak ditemukan.</p>";
    }
    ?>
  </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const ctx = document.getElementById('akunChart').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['Hari Ini', 'Minggu Ini', 'Bulan Ini', 'Tahun Ini'],
      datasets: [{
        label: 'Total Akun Dibuat',
        data: [<?= $stats['total'] ?>, <?= $stats['total'] ?>, <?= $stats['total'] ?>, <?= $stats['total'] ?>],
        backgroundColor: ['#3B82F6', '#6366F1', '#10B981', '#F59E0B'],
        borderRadius: 6
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: { mode: 'index', intersect: false }
      },
      scales: {
        x: { ticks: { color: '#6B7280' }, grid: { display: false }},
        y: { beginAtZero: true, ticks: { color: '#6B7280' }, grid: { color: '#E5E7EB' }}
      }
    }
  });
});
</script>
</body>
</html>

