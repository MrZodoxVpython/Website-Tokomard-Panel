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

$dataDir = "/etc/xray/data-panel/reseller/";
$username = $_SESSION['username'];
$akunFiles = glob($dataDir . "akun-{$username}-*.txt");

$statistik = [
    'vmess' => 0,
    'vless' => 0,
    'trojan' => 0,
    'shadowsocks' => 0,
    'aktif' => 0,
    'expired' => 0,
    'akan_expired' => 0
];

$daftarAkun = [];

foreach ($akunFiles as $file) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '{') !== false) {
            $json = json_decode($line, true);
            if (is_array($json)) {
                $protokol = $json['protokol'] ?? 'unknown';
                $expired = $json['expired'] ?? '';
                $akunUser = $json['username'] ?? '';
                $statistik[$protokol]++;

                $expTime = strtotime($expired);
                $now = time();
                $sisa = floor(($expTime - $now) / (60 * 60 * 24));

                if ($sisa < 0) {
                    $statistik['expired']++;
                } elseif ($sisa <= 7) {
                    $statistik['akan_expired']++;
                    $statistik['aktif']++;
                } else {
                    $statistik['aktif']++;
                }

                $daftarAkun[] = [
                    'username' => $akunUser,
                    'protokol' => strtoupper($protokol),
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
  <meta charset="UTF-8" />
  <title>Statistik Akun - Panel Reseller</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config = { darkMode: 'class' }</script>
  <script>
    function toggleTheme() {
      const html = document.documentElement;
      html.classList.toggle('dark');
      localStorage.setItem('theme', html.classList.contains('dark') ? 'dark' : 'light');
      document.getElementById('themeToggleBtn').textContent = html.classList.contains('dark') ? '🌞' : '🌙';
    }
    document.addEventListener('DOMContentLoaded', () => {
      if (localStorage.getItem('theme') === 'dark') document.documentElement.classList.add('dark');
      document.getElementById('themeToggleBtn').textContent = document.documentElement.classList.contains('dark') ? '🌞' : '🌙';
    });
  </script>
</head>
<body class="bg-white text-gray-900 dark:bg-gray-900 dark:text-white transition">
  <header class="flex justify-between items-center px-6 py-4 bg-gray-100 dark:bg-gray-800 shadow sticky top-0 z-50">
    <h1 class="text-xl font-bold">Statistik Akun Reseller</h1>
    <div class="flex gap-4 items-center">
      <button id="themeToggleBtn" onclick="toggleTheme()" class="text-2xl">🌙</button>
      <a href="../logout.php" class="px-4 py-2 bg-red-600 text-white rounded text-sm hover:bg-red-500">Logout</a>
    </div>
  </header>

  <main class="p-6 space-y-6">
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
      <?php foreach (['vmess','vless','trojan','shadowsocks'] as $p): ?>
        <div class="p-4 rounded-lg shadow bg-gradient-to-br from-blue-500 to-blue-700 text-white">
          <h2 class="text-sm font-semibold"><?= strtoupper($p) ?> Akun</h2>
          <div class="text-2xl font-bold"><?= $statistik[$p] ?></div>
        </div>
      <?php endforeach; ?>
      <div class="p-4 rounded-lg shadow bg-gradient-to-br from-green-500 to-green-700 text-white">
        <h2 class="text-sm font-semibold">Akun Aktif</h2>
        <div class="text-2xl font-bold"><?= $statistik['aktif'] ?></div>
      </div>
      <div class="p-4 rounded-lg shadow bg-gradient-to-br from-yellow-400 to-yellow-600 text-white">
        <h2 class="text-sm font-semibold">Akan Expired</h2>
        <div class="text-2xl font-bold"><?= $statistik['akan_expired'] ?></div>
      </div>
      <div class="p-4 rounded-lg shadow bg-gradient-to-br from-red-600 to-red-800 text-white">
        <h2 class="text-sm font-semibold">Expired</h2>
        <div class="text-2xl font-bold"><?= $statistik['expired'] ?></div>
      </div>
    </div>

    <div class="overflow-auto">
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
  </main>
</body>
</html>

