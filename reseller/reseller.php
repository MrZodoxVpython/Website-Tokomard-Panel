<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'reseller') {
    header("Location: ../index.php");
    exit;
}

$reseller = $_SESSION['username'];
$page = $_GET['page'] ?? 'dashboard';

// Toggle theme
if (isset($_GET['toggle_theme'])) {
    $_SESSION['theme'] = ($_SESSION['theme'] ?? 'light') === 'dark' ? 'light' : 'dark';
    header("Location: reseller.php?page={$page}");
    exit;
}

$isDark = ($_SESSION['theme'] ?? 'light') === 'dark';

// Read and count protocols
$stats = ['total'=>0,'trojan'=>0,'vmess'=>0,'vless'=>0,'shadowsocks'=>0];
$timeline = ['daily'=>[], 'weekly'=>[], 'monthly'=>[], 'yearly'=>[]];
$rows = [];

foreach (glob("/etc/xray/data-panel/reseller/akun-{$reseller}-*.txt") as $f) {
    $c = file_get_contents($f);
    $proto = '';
    foreach (['trojan','vmess','vless','shadowsocks'] as $p) {
        if (stripos($c, strtoupper($p).' ACCOUNT') !== false) {
            $proto = $p;
            $stats[$p]++;
            $stats['total']++;
            break;
        }
    }
    if (!$proto) continue;

    preg_match('/Expired On\s*:\s*([\d-]+)/i',$c,$m1);
    preg_match('/Remarks\s*:\s*(\S+)/i',$c,$m2);
    $expired = $m1[1] ?? '-';
    $user = $m2[1] ?? '-';
    $created = date('Y-m-d', filemtime($f));

    $rows[] = [$user, strtoupper($proto), $expired, $created, basename($f)];

    foreach ([
        'daily' => $created,
        'weekly' => date('o-\WW', strtotime($created)),
        'monthly' => date('Y-m', strtotime($created)),
        'yearly' => date('Y', strtotime($created))
    ] as $k=>$dt) {
        $timeline[$k][$dt] = ($timeline[$k][$dt] ?? 0) + 1;
    }
}
foreach ($timeline as &$t) ksort($t);
unset($t);
?>
<!DOCTYPE html>
<html lang="id" class="<?= $isDark ? 'dark' : '' ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Tokomard Reseller Panel</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={darkMode:'class'}</script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white min-h-screen">

<header class="p-4 bg-gray-100 dark:bg-gray-800 flex justify-between sticky top-0 z-50">
  <h1 class="text-xl font-bold">Tokomard Reseller</h1>
  <div class="flex gap-3">
    <a href="?toggle_theme=1" class="p-2 rounded bg-gray-200 dark:bg-gray-700"><?= $isDark?'🌞':'🌙' ?></a>
    <a href="../logout.php" class="px-3 py-1 bg-red-600 text-white rounded">Logout</a>
  </div>
</header>

<button id="btnSidebar" class="md:hidden fixed top-4 left-4 p-2 bg-gray-200 dark:bg-gray-700 rounded">☰</button>

<main class="flex flex-col md:flex-row p-4 gap-6">
  <aside id="sidebar" class="bg-gray-100 dark:bg-gray-800 p-5 rounded shadow md:w-1/5 w-full -translate-x-full md:translate-x-0 transition">
    <div class="text-center mb-6">
      <img src="<?= "https://ui-avatars.com/api/?name=".urlencode($reseller)."&background=4F46E5&color=fff" ?>" class="w-20 h-20 rounded-full mx-auto">
      <p>@<?= htmlspecialchars($reseller) ?></p>
    </div>
    <nav class="space-y-2">
      <?php
      $menu=['dashboard'=>'📊 Dashboard','ssh'=>'🔐 SSH','vmess'=>'🌀 VMess','vless'=>'📡 VLess','trojan'=>'⚔ Trojan','shadowsocks'=>'🕶 Shadowsocks','topup'=>'💳 Top Up','cek-server'=>'🖥 Cek Server','vip'=>'👑 Grup VIP'];
      foreach ($menu as $p=>$lbl) {
        echo "<a href='?page={$p}' class='block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600'>{$lbl}</a>";
        if ($p==='shadowsocks') echo "<hr class='border-gray-400 dark:border-gray-600 my-2'>";
      }
      ?>
    </nav>
  </aside>

  <section class="flex-1 p-6 bg-white dark:bg-gray-900 rounded shadow overflow-hidden">

    <?php if ($page === 'dashboard'): ?>

      <!-- statistik -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
        <?php foreach (['total'=>'Total','trojan'=>'Trojan','vmess'=>'VMess','vless'=>'VLess','shadowsocks'=>'Shadowsocks'] as $k=>$lbl): ?>
          <div class="p-3 bg-<?= $k=='trojan'?'red':($k=='vmess'?'purple':($k=='vless'?'blue':'green')) ?>-100 dark:bg-<?= $k=='trojan'?'red':($k=='vmess'?'purple':($k=='vless'?'blue':'green')) ?>-800 rounded text-center">
            <p class="font-semibold"><?= $lbl ?></p>
            <p class="text-2xl font-bold"><?= $stats[$k] ?></p>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- grafik -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <?php foreach (['weekly'=>'Per Minggu','monthly'=>'Per Bulan'] as $tf=>$lbl): ?>
          <div class="bg-white dark:bg-gray-800 p-4 rounded shadow">
            <h2 class="font-semibold mb-2"><?= $lbl ?></h2>
            <canvas id="chart_<?= $tf ?>" class="h-48"></canvas>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- tabel akun -->
      <div class="overflow-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-200 dark:bg-gray-700">
            <tr><th>#</th><th>User</th><th>Proto</th><th>Expired</th><th>Dibuat</th><th>File</th></tr>
          </thead>
          <tbody>
            <?php if (empty($rows)): ?>
              <tr><td colspan="6" class="p-4 text-center">Belum ada akun</td></tr>
            <?php else: foreach ($rows as $i=>$r): ?>
              <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                <?php foreach ($r as $td): ?>
                  <td class="px-2 py-1"><?= htmlspecialchars($td) ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

    <?php elseif (file_exists(__DIR__ . "/pages/{$page}.php")): ?>
      <?php include __DIR__ . "/pages/{$page}.php"; ?>
    <?php else: ?>
      <div class="text-red-500 text-center">Halaman <b><?= htmlspecialchars($page) ?></b> tidak ditemukan.</div>
    <?php endif; ?>

  </section>
</main>

<script>
document.getElementById('btnSidebar').onclick = () => {
  document.getElementById('sidebar').classList.toggle('-translate-x-full');
};

function buildChart(tf, labels, data) {
  const ctx = document.getElementById('chart_'+tf).getContext('2d');
  const grad = ctx.createLinearGradient(0,0,0,200);
  grad.addColorStop(0, 'rgba(59,130,246,0.6)');
  grad.addColorStop(1, 'rgba(59,130,246,0.1)');
  new Chart(ctx, {
    type: 'line',
    data: { labels, datasets: [{ data, fill:true, backgroundColor:grad, borderColor:'#3b82f6', tension:0.3, pointRadius:3 }] },
    options: {
      responsive:true, maintainAspectRatio:false,
      scales: {
        x:{grid:{display:false}, ticks:{color:document.documentElement.classList.contains('dark')?'#fff':'#000'}},
        y:{grid:{color:document.documentElement.classList.contains('dark')?'#374151':'#e5e7eb'}, ticks:{color:document.documentElement.classList.contains('dark')?'#fff':'#000'}}
      },
      plugins:{legend:{display:false}}
    }
  });
}

const labelsWeekly = <?= json_encode(array_keys($timeline['weekly'])) ?>;
const dataWeekly = <?= json_encode(array_values($timeline['weekly'])) ?>;
const labelsMonthly = <?= json_encode(array_keys($timeline['monthly'])) ?>;
const dataMonthly = <?= json_encode(array_values($timeline['monthly'])) ?>;

buildChart('weekly', labelsWeekly, dataWeekly);
buildChart('monthly', labelsMonthly, dataMonthly);
</script>

</body>
</html>

