<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'reseller') {
    header("Location: ../index.php");
    exit;
}

$reseller = $_SESSION['username'];
$avatar = 'https://ui-avatars.com/api/?name=' . urlencode($reseller) . '&background=4F46E5&color=fff';

$statistik = [
    'vmess' => 0, 'vless' => 0, 'trojan' => 0, 'shadowsocks' => 0,
    'aktif' => 0, 'expired' => 0, 'akan_expired' => 0
];

$daftarAkun = [];
$totalAkun = 0;
$dataDir = '/etc/xray/data-panel/';
$akunFiles = glob($dataDir . "akun-{$reseller}-*.txt");

foreach ($akunFiles as $file) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '{') !== false) {
            $json = json_decode($line, true);
            if (is_array($json)) {
                $totalAkun++;
                $proto = strtolower($json['protokol'] ?? 'unknown');
                $expired = $json['expired'] ?? '';
                $akunUser = $json['username'] ?? '';

                if (isset($statistik[$proto])) $statistik[$proto]++;

                $expTime = strtotime($expired);
                $now = time();
                $sisa = floor(($expTime - $now) / (60 * 60 * 24));

                if ($sisa < 0) $statistik['expired']++;
                elseif ($sisa <= 7) {
                    $statistik['akan_expired']++;
                    $statistik['aktif']++;
                } else $statistik['aktif']++;

                $daftarAkun[] = [
                    'username' => $akunUser,
                    'protokol' => strtoupper($proto),
                    'expired' => $expired,
                    'days_left' => $sisa
                ];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Statistik Akun - Tokomard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = { darkMode: 'class' };
  </script>
  <script>
    function updateThemeIcon() {
        const html = document.documentElement;
        const isDark = html.classList.contains('dark');
        const btn = document.getElementById('themeToggleBtn');
        if (btn) btn.textContent = isDark ? '🌞' : '🌙';
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

<header class="p-4 bg-gray-100 dark:bg-gray-800 shadow-md flex justify-between items-center sticky top-0 z-50">
  <h1 class="text-xl font-bold">Statistik Akun - Tokomard</h1>
  <div class="flex items-center gap-4">
    <button id="themeToggleBtn" onclick="toggleTheme()" class="text-xl p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition">🌙</button>
    <a href="../logout.php" class="px-3 py-2 bg-red-600 text-white rounded hover:bg-red-500 text-sm">Logout</a>
  </div>
</header>

<main class="max-w-6xl mx-auto px-4 md:px-8 py-8">
  <div class="flex flex-col md:flex-row gap-8">
    <!-- Sidebar -->
    <aside class="md:w-1/4 bg-gray-100 dark:bg-gray-800 rounded-xl shadow-md p-5">
      <div class="text-center">
        <img src="<?= $avatar ?>" class="w-20 h-20 rounded-full mx-auto mb-2">
        <h2 class="font-semibold text-sm">@<?= htmlspecialchars($reseller) ?></h2>
      </div>
      <nav class="mt-6 space-y-2 text-sm">
        <a href="../reseller.php?page=dashboard" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">📊 Dashboard</a>
        <a href="../reseller.php?page=ssh" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">🔐 SSH</a>
        <a href="../reseller.php?page=vmess" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">🌀 Vmess</a>
        <a href="../reseller.php?page=vless" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">📡 Vless</a>
        <a href="../reseller.php?page=trojan" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">⚔ Trojan</a>
        <a href="../reseller.php?page=shadowsocks" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">🕶 Shadowsocks</a>
        <hr class="my-4 border-gray-400 dark:border-gray-600">
        <a href="../reseller.php?page=topup" class="block px-3 py-2 rounded hover:bg-green-500 hover:text-white dark:hover:bg-green-600">💳 Top Up</a>
        <a href="../reseller.php?page=cek-server" class="block px-3 py-2 rounded hover:bg-indigo-500 hover:text-white dark:hover:bg-indigo-600">🖥 Cek Server</a>
        <a href="../reseller.php?page=vip" class="block px-3 py-2 rounded hover:bg-yellow-500 hover:text-white dark:hover:bg-yellow-600">👑 Grup VIP</a>
      </nav>
    </aside>

    <!-- Konten Statistik -->
    <section class="flex-1 space-y-6">
      <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <?php
        $cards = [
            ['Total Akun Dibuat', $totalAkun, 'from-indigo-500 to-indigo-700'],
            ['VMess', $statistik['vmess'], 'from-blue-500 to-blue-700'],
            ['VLESS', $statistik['vless'], 'from-blue-500 to-blue-700'],
            ['Trojan', $statistik['trojan'], 'from-blue-500 to-blue-700'],
            ['Shadowsocks', $statistik['shadowsocks'], 'from-blue-500 to-blue-700'],
            ['Aktif', $statistik['aktif'], 'from-green-500 to-green-700'],
            ['Akan Expired', $statistik['akan_expired'], 'from-yellow-400 to-yellow-600'],
            ['Expired', $statistik['expired'], 'from-red-600 to-red-800']
        ];
        foreach ($cards as [$label, $value, $bg]):
        ?>
        <div class="p-4 rounded-lg shadow bg-gradient-to-br <?= $bg ?> text-white">
          <h2 class="text-sm font-semibold"><?= $label ?></h2>
          <div class="text-2xl font-bold"><?= $value ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm table-auto divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-100 dark:bg-gray-800">
            <tr>
              <th class="px-4 py-2 text-left">Username</th>
              <th class="px-4 py-2 text-left">Protokol</th>
              <th class="px-4 py-2 text-left">Expired</th>
              <th class="px-4 py-2 text-left">Sisa Hari</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
            <?php foreach ($daftarAkun as $akun): ?>
            <tr>
              <td class="px-4 py-2"><?= htmlspecialchars($akun['username']) ?></td>
              <td class="px-4 py-2"><?= $akun['protokol'] ?></td>
              <td class="px-4 py-2"><?= $akun['expired'] ?></td>
              <td class="px-4 py-2"><?= $akun['days_left'] ?> hari</td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</main>
</body>
</html>

