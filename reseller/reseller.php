<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'reseller') {
    header("Location: ../index.php");
    exit;
}
$reseller = $_SESSION['username'];
if (!isset($_SESSION['theme'])) $_SESSION['theme'] = 'light';

if (isset($_GET['toggleTheme'])) {
    $_SESSION['theme'] = ($_SESSION['theme'] === 'dark') ? 'light' : 'dark';
    header("Location: reseller.php?page=" . ($_GET['page'] ?? 'dashboard'));
    exit;
}

$page = $_GET['page'] ?? 'dashboard';
$themeClass = $_SESSION['theme'] === 'dark' ? 'dark' : '';
$loggedInUser = [
    'username' => $reseller,
    'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($reseller) . '&background=4F46E5&color=fff'
];
?>
<!DOCTYPE html>
<html lang="id" class="<?= $themeClass ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Panel Reseller - Tokomard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config = { darkMode: 'class' };</script>
</head>
<body class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white transition-all duration-300 min-h-screen">

<header class="p-4 bg-gray-100 dark:bg-gray-800 shadow-md flex justify-between items-center sticky top-0 z-50">
  <h1 class="text-xl font-bold">Tokomard Reseller Panel</h1>
  <div class="flex items-center gap-4">
    <a href="?toggleTheme=1" class="text-xl p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700">
        <?= $_SESSION['theme'] === 'dark' ? '🌞' : '🌙' ?>
    </a>
    <a href="../logout.php" class="px-3 py-2 bg-red-600 text-white rounded hover:bg-red-500 text-sm">Logout</a>
  </div>
</header>

<button id="toggleSidebar" class="md:hidden fixed top-4 left-4 z-50 p-2 bg-gray-200 dark:bg-gray-700 rounded-md shadow-md">
  <svg class="h-6 w-6 text-gray-800 dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
  </svg>
</button>

<main class="flex flex-col md:flex-row w-full px-4 md:px-8 py-6 gap-6">
  <aside id="sidebar" class="md:w-1/5 w-full md:max-w-xs bg-gray-100 dark:bg-gray-800 p-5 shadow-lg rounded-lg transition-transform duration-300 -translate-x-full md:translate-x-0 z-40 md:mr-1">
    <div class="flex flex-col items-center text-center mb-6">
      <img src="<?= $loggedInUser['avatar'] ?>" alt="Profile" class="w-20 h-20 rounded-full mb-2">
      <h2 class="text-base font-semibold">@<?= htmlspecialchars($reseller) ?></h2>
    </div>
    <nav class="space-y-2 text-sm">
      <a href="?page=dashboard" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">📊 Dashboard</a>
      <a href="?page=ssh" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">🔐 SSH</a>
      <a href="?page=vmess" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">🌀 Vmess</a>
      <a href="?page=vless" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">📡 Vless</a>
      <a href="?page=trojan" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">⚔ Trojan</a>
      <a href="?page=shadowsocks" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">🕶 Shadowsocks</a>
      <hr class="border-t border-blue-500 dark:border-blue-400 my-3">
      <a href="?page=topup" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">💳 Top Up</a>
      <a href="?page=cek-server" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">🖥 Cek Server</a>
      <a href="?page=vip" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">👑 Grup VIP</a>
    </nav>
  </aside>

  <section class="flex-1 p-5 bg-white dark:bg-gray-900 rounded-xl shadow-md">
    <?php
    if ($page === 'dashboard') {
        $stats = ['total'=>0,'vmess'=>0,'vless'=>0,'trojan'=>0,'shadowsocks'=>0];
        $rows = [];
        $no = 1;
        foreach (glob("/etc/xray/data-panel/reseller/akun-{$reseller}-*.txt") as $file) {
            $buyer = basename($file, ".txt");
            $buyer = str_replace("akun-{$reseller}-", "", $buyer);
            $isi = file_get_contents($file);
            foreach (['vmess', 'vless', 'trojan', 'shadowsocks'] as $proto) {
                if (stripos($isi, strtoupper($proto) . ' ACCOUNT') !== false) {
                    $stats[$proto]++;
                    $stats['total']++;
                    $rows[] = ['no'=>$no++, 'user'=>strtoupper($proto), 'proto'=>strtoupper($proto), 'exp'=>'-', 'buyer'=>$buyer];
                }
            }
        }

        echo '<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-8">';
        foreach (['total'=>'Total Akun','vmess'=>'VMess','vless'=>'VLess','trojan'=>'Trojan','shadowsocks'=>'Shadowsocks'] as $k=>$label) {
            $color = ['total'=>'blue','vmess'=>'purple','vless'=>'blue','trojan'=>'red','shadowsocks'=>'green'][$k];
            echo "<div class='bg-{$color}-100 dark:bg-{$color}-800 text-{$color}-900 dark:text-{$color}-100 p-5 rounded-lg shadow'>
                <p class='text-lg font-semibold'>{$label}</p>
                <p class='text-3xl mt-2 font-bold'>{$stats[$k]}</p>
            </div>";
        }
        echo '</div>';

        echo '<div class="overflow-x-auto"><table class="table-fixed w-full border border-gray-300 dark:border-gray-700 text-sm">';
        echo '<thead class="bg-gray-200 dark:bg-gray-700"><tr>';
        echo '<th class="w-10 p-2 text-left">No</th><th class="w-1/5 p-2 text-left">User</th><th class="w-1/5 p-2 text-left">Proto</th><th class="w-1/5 p-2 text-left">Expired</th><th class="w-1/5 p-2 text-left">Buyer</th>';
        echo '</tr></thead><tbody>';
        if (empty($rows)) {
            echo '<tr><td colspan="5" class="p-4 text-center text-gray-500 dark:text-gray-400">Belum ada akun Xray</td></tr>';
        } else {
            foreach ($rows as $r) {
                echo "<tr class='hover:bg-gray-100 dark:hover:bg-gray-800'>";
                echo "<td class='p-2'>{$r['no']}</td><td class='p-2'>{$r['user']}</td><td class='p-2'>{$r['proto']}</td><td class='p-2'>{$r['exp']}</td><td class='p-2'>{$r['buyer']}</td>";
                echo "</tr>";
            }
        }
        echo '</tbody></table></div>';
    } elseif (file_exists($file = __DIR__."/pages/{$page}.php")) {
        include $file;
    } else {
        echo "<div class='text-center text-red-500'>Halaman <b>{$page}</b> tidak ditemukan.</div>";
    }
    ?>
  </section>
</main>

<script>
document.getElementById('toggleSidebar').onclick = ()=>{
    document.getElementById('sidebar').classList.toggle('-translate-x-full');
};
</script>

</body>
</html>

