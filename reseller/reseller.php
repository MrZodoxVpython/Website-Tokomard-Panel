<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'reseller') {
    header("Location: ../index.php");
    exit;
}
$reseller = $_SESSION['username'];
$theme = $_SESSION['theme'] ?? 'light';
$page = $_GET['page'] ?? 'dashboard';
$loggedInUser = [
    'username' => $reseller,
    'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($reseller) . '&background=4F46E5&color=fff'
];
?>
<!DOCTYPE html>
<html lang="id" class="<?= $theme === 'dark' ? 'dark' : '' ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Panel Reseller</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config = { darkMode: 'class' };</script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white transition-all duration-300">
<header class="p-2 px-5  bg-gray-100 dark:bg-gray-800 shadow-md flex justify-between items-center sticky z-10">
  <h1 class="text-xl font-bold">Panel Reseller Tokomard</h1>
  <div class="flex items-center gap-4">
    <button id="themeToggleBtn" class="text-xl p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700"><?= $theme === 'dark' ? '🌞' : '🌙' ?></button>
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
      <img src="<?= $loggedInUser['avatar'] ?>" class="w-20 h-20 rounded-full mb-2" />
      <h2 class="text-base font-semibold">@<?= htmlspecialchars($reseller) ?></h2>
    </div>
    <nav class="space-y-2 text-sm">
      <?php
      $menus = [
        'dashboard' => '📊 Dashboard', 'ssh' => '🔐 SSH', 'vmess' => '🌀 Vmess',
        'vless' => '📡 Vless', 'trojan' => '⚔ Trojan', 'shadowsocks' => '🕶 Shadowsocks',
        'topup' => '💳 Top Up', 'cek-server' => '🖥 Cek Server', 'vip' => '👑 Grup VIP'
      ];
      foreach ($menus as $key => $label) {
          echo "<a href='?page={$key}' class='block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600'>{$label}</a>";
          if ($key === 'shadowsocks') echo "<hr class='border-t border-gray-400 dark:border-gray-600 my-2' />";
      }
      ?>
    </nav>
  </aside>

  <section class="flex-1 p-5 bg-white dark:bg-gray-900 rounded-xl shadow-md">
    <?php
    $pagePath = __DIR__ . "/pages/{$page}.php";
    if ($page === 'dashboard') {
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
                // Ambil UUID atau Password
                if (stripos($line, 'Password') !== false && $proto === 'trojan') {
                    $uuidOrPass = trim(explode(':', $line, 2)[1] ?? '-');
                } elseif (stripos($line, 'Password') !== false && in_array($proto, ['vmess', 'vless', 'shadowsocks'])) {
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

        // Statistik
        echo '<div class="text-center grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">';
        foreach (['total' => 'Total Akun', 'vmess' => 'VMess', 'vless' => 'VLess', 'trojan' => 'Trojan', 'shadowsocks' => 'Shadowsocks'] as $k => $label) {
            $color = ['total' => 'green', 'vmess' => 'blue', 'vless' => 'purple', 'trojan' => 'red', 'shadowsocks' => 'yellow'][$k];
            echo "<div class='bg-{$color}-100 dark:bg-{$color}-800 text-{$color}-900 dark:text-white p-5 rounded-lg shadow'>
            <p class='text-lg font-semibold'>{$label}</p>
            <p class='text-3xl mt-2 font-bold'>{$stats[$k]}</p>
            </div>";
        }
        echo "</div>";

        // Grafik
        echo '<div class="mb-8 max-w-full bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
        <canvas id="myChart" class="h-[450px]"></canvas>
        </div>
        <script>
        const ctx = document.getElementById("myChart").getContext("2d");
        new Chart(ctx, {
            type: "bar",
            data: {
                labels: ["VMess", "VLess", "Trojan", "Shadowsocks"],
                datasets: [{
                    label: "Akun Terjual",
                    data: [' . $stats['vmess'] . ',' . $stats['vless'] . ',' . $stats['trojan'] . ',' . $stats['shadowsocks'] . '],
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
        </script>';

        // Tabel akun
        echo '<div class="overflow-x-auto">
            <table class="table-fixed w-full border border-gray-300 dark:border-gray-700 text-sm text-left">
            <thead class="bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white">
                <tr>
                    <th class="w-1/12 px-3 py-2">No</th>
                    <th class="w-3/12 px-3 py-2">Username</th>
                    <th class="w-2/12 px-3 py-2">Protocol</th>
                    <th class="w-3/12 px-3 py-2">Expired</th>
                    <th class="w-3/12 px-3 py-2">Uuid/Pass</th>
                </tr>
            </thead>
            <tbody>';
        if (empty($rows)) {
            echo '<tr><td colspan="5" class="text-center px-3 py-4 text-gray-500 dark:text-gray-400">Belum ada akun.</td></tr>';
        } else {
            foreach ($rows as $r) {
                echo "<tr class='hover:bg-gray-100 dark:hover:bg-gray-700'>
                        <td class='px-3 py-2'>{$r['no']}</td>
                        <td class='px-3 py-2'>{$r['user']}</td>
                        <td class='px-3 py-2'>{$r['proto']}</td>
                        <td class='px-3 py-2'>{$r['exp']}</td>
                        <td class='px-3 py-2 font-mono'>{$r['buyer']}</td>
                      </tr>";
            }
        }
        echo '</tbody></table></div>';
    } elseif (file_exists($pagePath)) {
        include $pagePath;
    } else {
        echo "<div class='text-red-500 text-center'>Halaman <b>{$page}</b> tidak ditemukan.</div>";
    }
    ?>
  </section>
</main>

<script>
document.getElementById("themeToggleBtn").onclick = function () {
    const html = document.documentElement;
    const isDark = html.classList.toggle("dark");
    fetch("?theme=" + (isDark ? "dark" : "light"));
};

document.getElementById("toggleSidebar").onclick = function () {
    document.getElementById("sidebar").classList.toggle("-translate-x-full");
};
</script>

<?php
if (isset($_GET['theme'])) {
    $_SESSION['theme'] = $_GET['theme'] === 'dark' ? 'dark' : 'light';
    exit;
}
?>
</body>
</html>

