<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'reseller') {
    header("Location: ../index.php");
    exit;
}

$loggedInUser = [
    'username' => $_SESSION['username'],
    'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['username']) . '&background=4F46E5&color=fff'
];

$reseller = $_SESSION['username'];
$dataDir = '/etc/xray/data-panel/reseller';
$files = glob("$dataDir/akun-$reseller-*.txt");

$statistik = array_fill_keys(['vmess', 'vless', 'trojan', 'shadowsocks'], []);
$today = date('Y-m-d');
$sevenDaysLater = date('Y-m-d', strtotime('+7 days'));

foreach ($files as $file) {
    $isi = file_get_contents($file);
    $proto = null;
    $u = $e = null;

    if (preg_match('/\s*([A-Z]+)\s+ACCOUNT/i', $isi, $m)) {
        $p = strtolower($m[1]);
        if (isset($statistik[$p])) $proto = $p;
    }

    if (!$proto && preg_match('/^(###|#&|#!|#\$)\s+(\S+)\s+(\d{4}-\d{2}-\d{2})$/m', $isi, $m2)) {
        $map = ['###' => 'vmess', '#&' => 'vless', '#!' => 'trojan', '#$' => 'shadowsocks'];
        $proto = $map[$m2[1]] ?? null;
        $u = $m2[2];
        $e = $m2[3];
    }

    if ($proto) {
        if (!$u) {
            preg_match('/Remarks\s*:\s*(\S+)/i', $isi, $mu);
            preg_match('/Expired On\s*:\s*(\d{4}-\d{2}-\d{2})/i', $isi, $me);
            $u = $mu[1] ?? 'unknown';
            $e = $me[1] ?? $today;
        }

        $status = ($e < $today) ? 'expired' : (($e <= $sevenDaysLater) ? 'expiring' : 'active');
        $statistik[$proto][] = [
            'username' => $u,
            'expired' => $e,
            'status' => $status,
            'online' => false // ❗bisa dikembangkan deteksi online
        ];
    }
}

function countStatus($data, $s)
{
    $c = 0;
    foreach ($data as $x) if ($x['status'] === $s) $c++;
    return $c;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Panel Reseller - Statistik</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' };
    </script>
    <script>
        function updateThemeIcon() {
            const html = document.documentElement;
            const isDark = html.classList.contains('dark');
            const btn = document.getElementById('themeToggleBtn');
            if (btn) {
                btn.textContent = isDark ? '🌞' : '🌙';
            }
        }

        function toggleTheme() {
            const html = document.documentElement;
            const isDark = html.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            updateThemeIcon();
        }

        document.addEventListener('DOMContentLoaded', () => {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.classList.toggle('dark', theme === 'dark');
            updateThemeIcon();
        });
    </script>
</head>
<body class="bg-white text-gray-900 dark:bg-gray-900 dark:text-white transition-colors duration-300 min-h-screen">

<!-- Header -->
<header class="p-4 bg-gray-100 dark:bg-gray-800 shadow-md flex justify-between items-center sticky top-0 z-50">
    <h1 class="text-xl font-bold">Tokomard Reseller Panel</h1>
    <div class="flex items-center gap-4">
        <button id="themeToggleBtn" onclick="toggleTheme()" class="text-xl p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition">🌙</button>
        <a href="../logout.php" class="px-3 py-2 bg-red-600 text-white rounded hover:bg-red-500 text-sm">Logout</a>
    </div>
</header>

<!-- Mobile Sidebar Toggle -->
<button id="toggleSidebar" class="md:hidden fixed top-4 left-4 z-50 p-2 bg-gray-200 dark:bg-gray-700 rounded-md shadow-md">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-800 dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
    </svg>
</button>

<!-- Main Layout -->
<main class="flex flex-col md:flex-row w-full px-4 md:px-8 py-6 gap-6">
    <!-- Sidebar -->
    <aside id="sidebar" class="md:w-1/5 w-full md:max-w-xs bg-gray-100 dark:bg-gray-800 p-5 shadow-lg rounded-lg transition-transform duration-300 -translate-x-full md:translate-x-0 z-40 md:mr-1">
        <div class="flex flex-col items-center text-center mb-6">
            <img src="<?= $loggedInUser['avatar'] ?>" alt="Profile" class="w-20 h-20 rounded-full mb-2">
            <h2 class="text-base font-semibold">@<?= htmlspecialchars($loggedInUser['username']) ?></h2>
        </div>
        <nav class="space-y-2 text-sm">
            <a href="#" class="block px-3 py-2 rounded bg-blue-600 text-white">📊 Statistik Akun</a>
            <a href="#" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">🔐 SSH</a>
            <a href="#" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">🌀 Vmess</a>
            <a href="#" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">📡 Vless</a>
            <a href="#" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">⚔ Trojan</a>
            <a href="#" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">🕶 Shadowsocks</a>

            <hr class="my-4 border-gray-400 dark:border-gray-600">

            <a href="#" class="block px-3 py-2 rounded hover:bg-green-500 hover:text-white dark:hover:bg-green-600">💳 Top Up</a>
            <a href="#" class="block px-3 py-2 rounded hover:bg-indigo-500 hover:text-white dark:hover:bg-indigo-600">🖥 Cek Server</a>
            <a href="#" class="block px-3 py-2 rounded hover:bg-yellow-500 hover:text-white dark:hover:bg-yellow-600">👑 Grup VIP</a>
        </nav>
    </aside>

    <!-- Konten Utama -->
    <section class="flex-1 p-5 bg-white dark:bg-gray-900 rounded-xl shadow-md">
        <div class="max-w-7xl mx-auto">
            <h1 class="text-3xl font-bold text-center text-transparent bg-clip-text bg-gradient-to-r from-sky-400 to-blue-600 mb-10">📊 Statistik Akun @<?= htmlspecialchars($reseller) ?></h1>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mb-12">
                <?php
                $icons = ['vmess' => '🌀', 'vless' => '📡', 'trojan' => '⚔', 'shadowsocks' => '🕶'];
                $colors = ['vmess' => 'from-blue-500 to-blue-700', 'vless' => 'from-purple-400 to-purple-600', 'trojan' => 'from-yellow-400 to-orange-500', 'shadowsocks' => 'from-green-300 to-teal-400'];

                foreach ($statistik as $proto => $akun):
                    $total = count($akun);
                    $active = countStatus($akun, 'active');
                    $expiring = countStatus($akun, 'expiring');
                    $expired = countStatus($akun, 'expired');
                    if ($total === 0) continue;
                ?>
                <div class="flex flex-col justify-between h-full min-h-[220px] rounded-xl p-6 shadow-lg text-white bg-gradient-to-br <?= $colors[$proto] ?> hover:scale-105 transition">
                    <div class="text-center">
                        <div class="text-4xl"><?= $icons[$proto] ?></div>
                        <h2 class="text-lg font-semibold mt-2"><?= strtoupper($proto) ?></h2>
                    </div>
                    <div class="mt-4 space-y-1 text-sm">
                        <div class="flex justify-between"><span>Total</span><span class="font-bold"><?= $total ?></span></div>
                        <div class="flex justify-between text-green-300"><span>Aktif</span><span><?= $active ?></span></div>
                        <div class="flex justify-between text-yellow-300"><span>Expiring</span><span><?= $expiring ?></span></div>
                        <div class="flex justify-between text-red-400"><span>Expired</span><span><?= $expired ?></span></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php foreach ($statistik as $proto => $akun): if (empty($akun)) continue; ?>
            <div class="bg-gray-800 rounded-2xl p-6 shadow-xl mb-10">
                <h2 class="text-2xl font-bold text-white mb-6 border-b border-gray-600 pb-2"><?= strtoupper($proto) ?> - Detail Akun</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto divide-y divide-gray-700 text-sm text-white">
                        <thead class="bg-gray-700 text-gray-300">
                            <tr><th class="px-4 py-3">#</th><th class="px-4 py-3">Username</th><th class="px-4 py-3">Expired</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Online</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-600">
                            <?php $i = 1; foreach ($akun as $x): ?>
                            <tr class="hover:bg-gray-700">
                                <td class="px-4 py-2"><?= $i++ ?></td>
                                <td class="px-4 py-2 break-words"><?= htmlspecialchars($x['username']) ?></td>
                                <td class="px-4 py-2"><?= $x['expired'] ?></td>
                                <td class="px-4 py-2">
                                    <?php if ($x['status'] == 'active'): ?><span class="text-green-400">Aktif</span>
                                    <?php elseif ($x['status'] == 'expiring'): ?><span class="text-yellow-400">Segera Exp</span>
                                    <?php else: ?><span class="text-red-400">Expired</span><?php endif; ?>
                                </td>
                                <td class="px-4 py-2"><?= $x['online'] ? '🟢' : '⚪' ?></td>
                            </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach ?>
        </div>
    </section>
</main>

<script>
    const toggleBtn = document.getElementById('toggleSidebar');
    const sidebar = document.getElementById('sidebar');
    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('-translate-x-full');
    });
</script>
</body>
</html>

