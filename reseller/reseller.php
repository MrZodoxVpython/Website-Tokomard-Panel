<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'reseller') {
    header("Location: ../index.php");
    exit;
}
$reseller = $_SESSION['username'];
$theme = $_SESSION['theme'] ?? 'light';
if (isset($_GET['theme'])) {
    $_SESSION['theme'] = $_GET['theme'];
    header("Location: reseller.php");
    exit;
}
$loggedInUser = [
    'username' => $reseller,
    'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($reseller) . '&background=4F46E5&color=fff'
];
$page = $_GET['page'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="id" class="<?= $theme === 'dark' ? 'dark' : '' ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reseller Panel</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={ darkMode:'class' }</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white min-h-screen transition-colors">

<header class="p-4 bg-gray-100 dark:bg-gray-800 flex justify-between items-center shadow sticky top-0 z-10">
  <h1 class="font-bold text-xl">Tokomard Reseller Panel</h1>
  <div class="flex gap-3">
    <a href="?theme=<?= $theme==='dark'?'light':'dark' ?>" class="p-2 rounded-md bg-gray-200 dark:bg-gray-700"><?= $theme==='dark'?'🌞':'🌙' ?></a>
    <a href="../logout.php" class="px-3 py-1 bg-red-600 rounded text-white">Logout</a>
  </div>
</header>

<button id="btnSidebar" class="md:hidden fixed top-4 left-4 p-2 bg-gray-200 dark:bg-gray-700 rounded z-50">
  ☰
</button>

<main class="flex flex-col md:flex-row p-4 gap-6">
  <aside id="sidebar" class="bg-gray-100 dark:bg-gray-800 p-4 rounded-lg shadow transition-transform -translate-x-full md:translate-x-0 md:w-1/5">
    <div class="text-center mb-6">
      <img src="<?= $loggedInUser['avatar'] ?>" class="w-20 h-20 mx-auto rounded-full mb-2">
      <p>@<?= htmlspecialchars($reseller) ?></p>
    </div>
    <nav class="space-y-1 text-sm">
      <?php
        $menu = ['dashboard'=>'📊 Dashboard','ssh'=>'🔐 SSH','vmess'=>'🌀 Vmess','vless'=>'📡 VLess','trojan'=>'⚔ Trojan','shadowsocks'=>'🕶 Shadowsocks','topup'=>'💳 Top Up','cek-server'=>'🖥 Cek Server','vip'=>'👑 Grup VIP'];
        foreach ($menu as $k=>$lbl) {
          echo "<a href='?page=$k' class='block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600'>$lbl</a>";
          if ($k === 'shadowsocks') {
            echo "<hr class='border-gray-300 dark:border-gray-600 my-2'>";
          }
        }
      ?>
    </nav>
  </aside>

  <section class="flex-1 bg-white dark:bg-gray-900 rounded-lg shadow p-5 overflow-auto">
    <?php if ($page === 'dashboard'): ?>
      <?php
        $dir = "/etc/xray/data-panel/reseller";
        $stats = ['total'=>0,'vmess'=>0,'vless'=>0,'trojan'=>0,'shadowsocks'=>0];
        $rows=[]; $no=1;
        foreach (glob("$dir/akun-$reseller-*.txt") as $f) {
          $buyer = str_replace("akun-$reseller-","",basename($f,".txt"));
          $c = file_get_contents($f);
          foreach (['vmess','vless','trojan','shadowsocks'] as $p) {
            if (stripos($c, strtoupper($p).' ACCOUNT')!==false) {
              $stats[$p]++; $stats['total']++;
              $rows[]=['no'=>$no++,'user'=>$buyer,'proto'=>strtoupper($p),'exp'=>'-','buyer'=>$buyer];
              break;
            }
          }
        }
      ?>

      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
        <?php foreach (['total'=>'Total Akun','vmess'=>'VMess','vless'=>'VLess','trojan'=>'Trojan','shadowsocks'=>'Shadowsocks'] as $k=>$lbl): ?>
          <?php $c=['total'=>'blue','vmess'=>'purple','vless'=>'blue','trojan'=>'red','shadowsocks'=>'green'][$k]; ?>
          <div class="bg-<?= $c ?>-100 dark:bg-<?= $c ?>-800 p-4 rounded-lg text-center">
            <p class="font-semibold"><?= $lbl ?></p>
            <p class="text-2xl font-bold"><?= $stats[$k] ?></p>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="mb-6 bg-gray-100 dark:bg-gray-800 p-4 rounded-lg">
        <canvas id="chartStats" height="100"></canvas>
      </div>
      <script>
      const ctx = document.getElementById('chartStats').getContext('2d');
      new Chart(ctx,{
        type:'bar',
        data:{
          labels:['VMess','VLess','Trojan','Shadowsocks'],
          datasets:[{ data:[<?= $stats['vmess'] ?>,<?= $stats['vless'] ?>,<?= $stats['trojan'] ?>,<?= $stats['shadowsocks'] ?>],
            backgroundColor:['#8B5CF6','#3B82F6','#EF4444','#10B981'], borderRadius:6 }]
        },
        options:{ responsive:true, scales:{ y:{ beginAtZero:true } }, plugins:{ legend:{display:false} } }
      });
      </script>

      <div class="overflow-x-auto">
        <table class="w-full table-fixed border border-gray-300 dark:border-gray-700 text-sm">
          <thead class="bg-gray-200 dark:bg-gray-700">
            <tr class="text-left">
              <th class="w-12 p-2">No</th>
              <th class="w-1/4 p-2">User</th>
              <th class="w-1/5 p-2">Proto</th>
              <th class="w-1/4 p-2">Expired</th>
              <th class="w-1/4 p-2">Buyer</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($rows)): ?>
              <tr><td colspan="5" class="p-4 text-center text-gray-500 dark:text-gray-400">Belum ada akun Xray</td></tr>
            <?php else: foreach($rows as $r): ?>
              <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                <td class="p-2"><?= $r['no'] ?></td>
                <td class="p-2"><?= htmlspecialchars($r['user']) ?></td>
                <td class="p-2"><?= htmlspecialchars($r['proto']) ?></td>
                <td class="p-2"><?= htmlspecialchars($r['exp']) ?></td>
                <td class="p-2"><?= htmlspecialchars($r['buyer']) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

    <?php elseif(file_exists(__DIR__."/pages/{$page}.php")): ?>
      <?php include __DIR__."/pages/{$page}.php"; ?>
    <?php else: ?>
      <p class="text-red-500 text-center">Halaman <?= htmlspecialchars($page) ?> tidak ditemukan.</p>
    <?php endif; ?>
  </section>
</main>

<script>
document.getElementById('btnSidebar').onclick = () => {
  document.getElementById('sidebar').classList.toggle('-translate-x-full');
};
</script>

</body>
</html>

