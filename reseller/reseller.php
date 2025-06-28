<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'reseller') {
    header("Location: ../index.php");
    exit;
}

$reseller = $_SESSION['username'];
$page = $_GET['page'] ?? 'dashboard';

if (isset($_GET['toggle_theme'])) {
    $_SESSION['theme'] = ($_SESSION['theme'] ?? 'light') === 'dark' ? 'light' : 'dark';
    header("Location: reseller.php?page={$page}");
    exit;
}

$isDark = ($_SESSION['theme'] ?? 'light') === 'dark';
$themeClass = $isDark ? 'dark' : '';

$loggedInUser = [
    'username' => $reseller,
    'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($reseller) . '&background=4F46E5&color=fff'
];

// Statistik protokol dari file .txt
$stats = ['total'=>0,'trojan'=>0,'vmess'=>0,'vless'=>0,'shadowsocks'=>0];

// Data timeline & rows
$timeline = ['daily'=>[], 'weekly'=>[], 'monthly'=>[], 'yearly'=>[]];
$rows = [];
$dir = "/etc/xray/data-panel/reseller/";
foreach (glob("{$dir}akun-{$reseller}-*.txt") as $file) {
    $content = file_get_contents($file);
    $protoFound = '';
    foreach (['trojan','vmess','vless','shadowsocks'] as $p) {
        if (stripos($content, strtoupper($p).' ACCOUNT') !== false) {
            $stats[$p]++;
            $stats['total']++;
            $protoFound = $p;
            break;
        }
    }
    // Jika tidak ditemukan protokol, lanjut
    if (!$protoFound) continue;

    // Parse tanggal dan user JSON-like jika ada
    preg_match('/Expired On\s*:\s*([\d-]+)/i', $content, $mExp);
    $expired = $mExp[1] ?? '-';
    preg_match('/Remarks\s*:\s*(\S+)/i', $content, $mUser);
    $user = $mUser[1] ?? '-';

    // Use filemtime as creation date fallback
    $createdDate = date('Y-m-d', filemtime($file));
    $rows[] = [$user, strtoupper($protoFound), $expired, $createdDate, basename($file, ".txt")];

    // Timeline
    $timeline['daily'][$createdDate] = ($timeline['daily'][$createdDate] ?? 0) + 1;
    $week = date('o-W', strtotime($createdDate));
    $timeline['weekly'][$week] = ($timeline['weekly'][$week] ?? 0) + 1;
    $month = date('Y-m', strtotime($createdDate));
    $timeline['monthly'][$month] = ($timeline['monthly'][$month] ?? 0) + 1;
    $year = date('Y', strtotime($createdDate));
    $timeline['yearly'][$year] = ($timeline['yearly'][$year] ?? 0) + 1;
}

foreach ($timeline as &$ar) ksort($ar);
?>
<!DOCTYPE html>
<html lang="id" class="<?= $themeClass ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Reseller Panel</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={darkMode:'class'}</script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white min-h-screen transition">
<header class="p-4 bg-gray-100 dark:bg-gray-800 flex justify-between sticky top-0 z-50">
  <h1 class="font-bold">Tokomard Reseller</h1>
  <div class="flex gap-4">
    <a href="?toggle_theme=1" class="rounded-full p-2 hover:bg-gray-200 dark:hover:bg-gray-700"><?= $isDark?'🌞':'🌙' ?></a>
    <a href="../logout.php" class="px-3 py-2 bg-red-600 text-white rounded">Logout</a>
  </div>
</header>

<button id="toggleSidebar" class="md:hidden p-2 fixed top-4 left-4 bg-gray-200 dark:bg-gray-700 rounded">
  <svg class="w-6 h-6"><path stroke="currentColor" stroke-width="2"d="M4 6h16M4 12h16M4 18h16"/></svg>
</button>

<main class="flex flex-col md:flex-row p-4 gap-6">
  <aside id="sidebar" class="bg-gray-100 dark:bg-gray-800 p-5 rounded shadow md:w-1/5 w-full -translate-x-full md:translate-x-0 transition">
    <div class="text-center mb-6">
      <img src="<?= $loggedInUser['avatar'] ?>" class="w-20 h-20 rounded-full mx-auto">
      <p>@<?= htmlspecialchars($reseller) ?></p>
    </div>
    <nav class="space-y-2 text-sm">
      <?php 
      $menu=['dashboard'=>'📊 Dashboard','ssh'=>'🔐 SSH','vmess'=>'🌀 VMess','vless'=>'📡 VLess','trojan'=>'⚔ Trojan','shadowsocks'=>'🕶 Shadowsocks','topup'=>'💳 Top Up','cek-server'=>'🖥 Cek Server','vip'=>'👑 Grup VIP'];
      foreach($menu as $p=>$lbl){
        echo "<a href='?page={$p}' class='block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600'>{$lbl}</a>";
        if($p==='shadowsocks') echo "<hr class='border-gray-400 dark:border-gray-600 my-2'>";
      }
      ?>
    </nav>
  </aside>

  <section class="flex-1 bg-white dark:bg-gray-900 p-6 rounded shadow overflow-hidden">
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
      <?php foreach(['total'=>'Total Akun','trojan'=>'Trojan','vmess'=>'VMess','vless'=>'VLess','shadowsocks'=>'Shadowsocks'] as $k=>$lbl): ?>
        <div class="p-3 bg-<?= $k=='trojan'?'red':($k=='vmess'?'purple':($k=='vless'?'blue':'green')) ?>-100 dark:bg-<?= $k=='trojan'?'red':($k=='vmess'?'purple':($k=='vless'?'blue':'green')) ?>-800 rounded">
          <p class="font-semibold"><?= $lbl ?></p>
          <p class="text-2xl font-bold"><?= $stats[$k] ?></p>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
      <?php foreach(['daily'=>'Per Hari','weekly'=>'Per Minggu','monthly'=>'Per Bulan','yearly'=>'Per Tahun'] as $tf=>$lbl): ?>
        <div class="bg-white dark:bg-gray-800 p-4 rounded shadow">
          <h2 class="font-semibold mb-2"><?= $lbl ?></h2>
          <canvas id="chart_<?= $tf ?>" class="w-full h-48"></canvas>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="overflow-x-auto mt-6">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-200 dark:bg-gray-700">
          <tr><th>#</th><th>User</th><th>Proto</th><th>Expired</th><th>Dibuat</th><th>File</th></tr>
        </thead>
        <tbody>
          <?php if(empty($rows)): ?>
            <tr><td colspan="6" class="p-4 text-center">Belum ada akun</td></tr>
          <?php else: foreach($rows as $i=>$r): ?>
            <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
              <td><?= $i+1 ?></td>
              <td><?= htmlspecialchars($r[0]) ?></td>
              <td><?= $r[1] ?></td>
              <td><?= $r[2] ?></td>
              <td><?= $r[3] ?></td>
              <td><?= $r[4] ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>

<script>
document.getElementById('toggleSidebar').onclick = ()=>{
  document.getElementById('sidebar').classList.toggle('-translate-x-full');
};

const dataSets = {
  daily: <?= json_encode(array_values($timeline['daily'])) ?>,
  weekly: <?= json_encode(array_values($timeline['weekly'])) ?>,
  monthly: <?= json_encode(array_values($timeline['monthly'])) ?>,
  yearly: <?= json_encode(array_values($timeline['yearly'])) ?>
};

Object.entries(dataSets).forEach(([tf, data]) => {
  new Chart(document.getElementById('chart_' + tf).getContext('2d'), {
    type: 'bar',
    data: {
      labels: <?= json_encode(array_keys($timeline['daily'])) ?>, // sesuaikan jika label berbeda
      datasets: [{ data, backgroundColor: '#3b82f6', borderRadius:4 }]
    },
    options: {
      responsive:true,
      maintainAspectRatio:false,
      scales:{
        y:{beginAtZero:true,ticks:{color:document.documentElement.classList.contains('dark')?'#fff':'#000'}},
        x:{ticks:{color:document.documentElement.classList.contains('dark')?'#fff':'#000'}}
      },
      plugins:{legend:{display:false}}
    }
  });
});
</script>
</body>
</html>

