<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'reseller') {
    header("Location: ../index.php");
    exit;
}
$reseller = $_SESSION['username'];
$loggedInUser = [
    'username' => $reseller,
    'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($reseller) . '&background=4F46E5&color=fff'
];
$page = $_GET['page'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Panel Reseller - Tokomard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' };</script>
</head>
<body class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white transition-colors duration-300 min-h-screen">
<header class="p-4 bg-gray-100 dark:bg-gray-800 shadow-md flex justify-between items-center sticky top-0 z-50">
    <h1 class="text-xl font-bold">Tokomard Reseller Panel</h1>
    <div class="flex items-center gap-4">
        <button id="themeToggleBtn" class="text-xl p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700">🌙</button>
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
          <?php foreach (['dashboard'=>'📊 Dashboard','ssh'=>'🔐 SSH','vmess'=>'🌀 Vmess','vless'=>'📡 Vless','trojan'=>'⚔ Trojan','shadowsocks'=>'🕶 Shadowsocks','topup'=>'💳 Top Up','cek-server'=>'🖥 Cek Server','vip'=>'👑 Grup VIP'] as $p => $label): ?>
            <a href="?page=<?= $p ?>" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600"><?= $label ?></a>
          <?php endforeach; ?>
        </nav>
    </aside>
    <section class="flex-1 p-5 bg-white dark:bg-gray-900 rounded-xl shadow-md">
        <?php
        if ($page === 'dashboard') {
            // Hitung statistik
            $stats = ['total'=>0,'vmess'=>0,'vless'=>0,'trojan'=>0,'shadowsocks'=>0];
            $dir = "/etc/xray/data-panel/";
            $no = 1;
            $rows = [];
            foreach (glob("{$dir}akun-{$reseller}-*.txt") as $file) {
                $buyer = basename($file, ".txt");
                $buyer = str_replace("akun-{$reseller}-","",$buyer);
                foreach (file($file, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line) {
                    if (strpos($line,'{')===false) continue;
                    $j = json_decode(trim($line), true);
                    if (!$j || !isset($j['protocol'])) continue;
                    $proto = strtolower($j['protocol']);
                    if (isset($stats[$proto])) $stats[$proto]++;
                    $stats['total']++;
                    $rows[] = [
                        'no'=>$no++,
                        'user'=>$j['user'] ?? '-',
                        'proto'=>strtoupper($proto),
                        'exp'=>$j['expired'] ?? '-',
                        'buyer'=>$buyer
                    ];
                }
            }
            // Output dashboard
            echo '<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-8">';
            foreach (['total'=>'Total Akun','vmess'=>'VMess','vless'=>'VLess','trojan'=>'Trojan','shadowsocks'=>'Shadowsocks'] as $k=>$label) {
                $color = [
                    'total'=>'blue','vmess'=>'purple','vless'=>'blue','trojan'=>'red','shadowsocks'=>'green'
                ][$k];
                echo "
                <div class='bg-{$color}-100 dark:bg-{$color}-800 text-{$color}-900 dark:text-{$color}-200 p-5 rounded-lg shadow'>
                    <p class='text-lg font-semibold'>{$label}</p>
                    <p class='text-3xl mt-2 font-bold'>{$stats[$k]}</p>
                </div>";
            }
            echo "</div>";
            // Output tabel akun
            echo '<div class="overflow-x-auto"><table class="w-full table-auto border border-gray-300 dark:border-gray-700 rounded text-sm"><thead class="bg-gray-200 dark:bg-gray-700"><tr><th class="p-2">#</th><th class="p-2">User</th><th class="p-2">Proto</th><th class="p-2">Expired</th><th class="p-2">Buyer</th></tr></thead><tbody>';
            if (empty($rows)) {
                echo '<tr><td colspan="5" class="p-4 text-center text-gray-500 dark:text-gray-400">Belum ada akun Xray</td></tr>';
            } else {
                foreach ($rows as $r) {
                    echo "<tr class='hover:bg-gray-100 dark:hover:bg-gray-700'><td class='p-2'>{$r['no']}</td><td class='p-2'>{$r['user']}</td><td class='p-2'>{$r['proto']}</td><td class='p-2'>{$r['exp']}</td><td class='p-2'>{$r['buyer']}</td></tr>";
                }
            }
            echo '</tbody></table></div>';
        } elseif (file_exists($pagePath = __DIR__."/pages/{$page}.php")) {
            include $pagePath;
        } else {
            echo "<div class='text-center text-red-500'>Halaman <b>{$page}</b> tidak ditemukan.</div>";
        }
        ?>
    </section>
</main>
<script>
document.getElementById('themeToggleBtn').onclick = ()=>{
    document.documentElement.classList.toggle('dark');
};
document.getElementById('toggleSidebar').onclick = ()=>{
    document.getElementById('sidebar').classList.toggle('-translate-x-full');
};
</script>
</body>
</html>

