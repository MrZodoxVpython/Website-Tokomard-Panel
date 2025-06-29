<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'koneksi.php';  // <<< tambahkan ini

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


// Jumlah notifikasi admin (untuk ikon bel)
$resNotif = $conn->query("SELECT COUNT(*) as jumlah FROM notifikasi_admin");
$jumlahNotif = $resNotif ? ($resNotif->fetch_assoc()['jumlah'] ?? 0) : 0;

// Ambil notifikasi untuk dropdown
$notifications = [];
$notifCount = 0;
$stmt = $conn->prepare("SELECT id, pesan, sudah_dibaca, dibuat_pada FROM notifikasi_reseller WHERE username IS NULL OR username = ? ORDER BY dibuat_pada DESC LIMIT 10");
if ($stmt) {
    $stmt->bind_param("s", $reseller);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if (!$row['sudah_dibaca']) {
            $notifCount++;
        }
        $notifications[] = $row;
    }
    $stmt->close();
} else {
    // Tambahkan logging atau debug error jika perlu
    $notifCount = 0;
    $notifications = [];
}

// Ambil notifikasi untuk bagian atas (jika berbeda)
$notifResult = $conn->prepare("SELECT pesan, waktu FROM notifikasi_reseller WHERE username = ? ORDER BY waktu DESC");
if ($notifResult) {
    $notifResult->bind_param("s", $reseller);
    $notifResult->execute();
    $notifResult = $notifResult->get_result();
} else {
    // fallback jika prepare gagal
    $notifResult = new stdClass();
    $notifResult->num_rows = 0;
    $notifResult->fetch_assoc = function () { return null; };
}

?>
<!DOCTYPE html>
<html lang="id" class="<?= $theme === 'dark' ? 'dark' : '' ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta property="og:title" content="Tokomard Panel VPN - Kelola Trojan & Xray dengan Mudah">
  <meta property="og:description" content="Panel untuk manajemen SSH, Xray (VLESS, VMess, Trojan, Shadowsocks).">
  <meta property="og:image" content="https://i.imgur.com/q3DzxiB.png">
  <meta property="og:url" content="https://panel.tokomard.store/">
  <meta property="og:type" content="website">
  <title>Tokomard</title>
  <link rel="SHORTCUT ICON" href="https://i.imgur.com/q3DzxiB.png">
  <!-- Tailwind CSS CDN -->
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "Tokomard VPN Panel",
  "operatingSystem": "Linux",
  "applicationCategory": "DeveloperApplication",
  "description": "Panel web untuk mengelola akun VPN berbasis Xray.",
  "url": "https://tokomard.com/",
  "author": {
    "@type": "Person",
    "name": "Benjamin Wickman"
  }
}
  </script>
  <!-- Tailwind CSS -->
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <!-- AlpineJS for slider -->
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <title>Panel Reseller</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config = { darkMode: 'class' };</script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white transition-all duration-300">
<header class="px-4 py-3 bg-gray-100 dark:bg-gray-800 shadow-md flex justify-between items-center sticky top-0 z-10">
  <!-- Logo + Judul -->
  <div class="flex items-center gap-3 cursor-pointer relative" onclick="document.getElementById('notifDropdown').classList.toggle('hidden')">

    <!-- Logo dengan badge -->
    <div class="relative">
      <img src="https://i.imgur.com/q3DzxiB.png" class="w-10 h-10" alt="Logo Imgur" />
      <?php if ($notifCount > 0): ?>
      <span
        class="absolute top-1 right-1 transform translate-x-1/4 -translate-y-1/4 w-5 h-5 text-[12px] flex items-center justify-center font-bold text-white bg-red-600 border-2 border-white dark:border-gray-800 rounded-full animate-pulse"
      >
        <?= $notifCount > 9 ? '9+' : $notifCount ?>
      </span>
      <?php endif; ?>
    </div>

    <!-- Judul Panel -->
    <h1 class="text-lg md:text-xl font-bold text-gray-800 dark:text-white whitespace-nowrap select-none">
      Panel Reseller Tokomard
    </h1>
  </div>

  <!-- Tombol Aksi -->
  <div class="flex items-center gap-4">
<button id="themeToggleBtn" class="text-xl p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700">
  <span id="themeIcon"><?= $theme === 'dark' ? '🌞' : '🌙' ?></span>
</button>
    <a href="pesan.php" class="px-3 py-2 bg-red-600 text-white rounded hover:bg-red-500 text-sm">
      Logout
    </a>
  </div>
</header>

<!-- Dropdown Notifikasi -->
<div class="relative">
  <div id="notifDropdown" class="hidden absolute top-16 right-4 w-96 bg-white dark:bg-gray-900 shadow-xl rounded-xl border border-gray-200 dark:border-gray-700 z-50">
    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 text-lg font-semibold text-gray-800 dark:text-white">
      🔔 Notifikasi
    </div>

    <?php if (count($notifications) > 0): ?>
      <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-96 overflow-y-auto">
        <?php foreach ($notifications as $n): ?>
          <div class="flex items-start gap-3 px-5 py-4 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-150 <?= $n['sudah_dibaca'] ? '' : 'bg-blue-50 dark:bg-blue-900' ?>">
            <div class="flex-1">
              <p class="text-sm text-gray-800 dark:text-gray-200">
                <?= htmlspecialchars($n['pesan']) ?>
              </p>
              <span class="text-xs text-gray-500 dark:text-gray-400 block mt-1">
                <?= date('d M H:i', strtotime($n['dibuat_pada'])) ?>
              </span>
            </div>
            <?php if (!$n['sudah_dibaca']): ?>
              <span class="inline-block mt-1 bg-blue-600 text-white text-[10px] font-semibold px-2 py-0.5 rounded-full">
                Baru
              </span>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="px-5 py-6 text-center text-sm text-gray-500 dark:text-gray-400 italic">
        Tidak ada notifikasi baru.
      </div>
    <?php endif; ?>

    <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-700 text-center">
      <form action="tandai-notif-dibaca.php" method="POST">
        <button type="submit" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">
          Tandai semua sudah dibaca
        </button>
      </form>
    </div>
  </div>
</div>

<button id="toggleSidebar" class="md:hidden fixed top-4 left-4 z-50 p-2 bg-gray-200 dark:bg-gray-700 rounded-md shadow-md">
  <svg class="h-6 w-6 text-gray-800 dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
  </svg>
</button>

<main class="flex flex-col md:flex-row w-full px-4 md:px-8 py-6 gap-6">
  <aside id="sidebar" class="md:w-1/5 w-full md:max-w-xs bg-gray-100 dark:bg-gray-800 p-5 shadow-lg rounded-lg transition-transform duration-300 -translate-x-full md:translate-x-0 z-40 md:mr-1">
<div class="flex flex-col items-center text-center mb-6">
  <!-- Hidden file input -->
  <form id="avatarForm" action="upload-avatar.php" method="POST" enctype="multipart/form-data">
    <input type="file" name="avatar" id="avatarInput" accept="image/*" class="hidden" onchange="document.getElementById('avatarForm').submit()">
  </form>

  <!-- Klik gambar untuk trigger file input -->
<form action="upload-avatar.php" method="POST" enctype="multipart/form-data">
  <label for="avatarUpload" class="cursor-pointer">
    <img src="<?= $_SESSION['avatar'] ?? 'uploads/avatars/default.png' ?>?v=<?= time() ?>" class="w-20 h-20 rounded-full mb-2 hover:opacity-80 transition" />
  </label>
  <input type="file" name="avatar" id="avatarUpload" accept="image/*" class="hidden" onchange="this.form.submit()">
</form>
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

    // Ganti ikon 🌙/🌞
    const icon = document.getElementById("themeIcon");
    icon.textContent = isDark ? "🌞" : "🌙";

    // Simpan ke session tanpa reload
    fetch("?theme=" + (isDark ? "dark" : "light"));
};
document.getElementById("toggleSidebar").onclick = function () {
    document.getElementById("sidebar").classList.toggle("-translate-x-full");
};

var notifCount = <?= $notifCount ?>;
if (notifCount > 0) {
    let show = false;
    setInterval(() => {
        document.title = (show ? "🔔 " : "") + "<?= ($notifCount > 0 ? "($notifCount) " : "") ?>Tokomard Panel";
        show = !show;
    }, 3000);
}
</script>

<?php
if (isset($_GET['theme'])) {
    $_SESSION['theme'] = $_GET['theme'] === 'dark' ? 'dark' : 'light';
    exit;
}
?>
</body>
</html>

