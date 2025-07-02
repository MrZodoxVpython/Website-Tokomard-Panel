<?php
session_start();
ini_set('display_errors',1); error_reporting(E_ALL);
require 'koneksi.php';

// Validasi login & role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'reseller') {
    header("Location: ../index.php");
    exit;
}
$reseller = $_SESSION['username'];
$page = $_GET['page'] ?? 'dashboard';

// Tema dari JSON per user
$theme = 'light';
$themeFile = __DIR__ . '/uploads/theme.json';
if (file_exists($themeFile)) {
    $themes = json_decode(file_get_contents($themeFile), true);
    if (isset($themes[$reseller])) $theme = $themes[$reseller];
}

// Notifikasi
$resNotif = $conn->query("SELECT COUNT(*) as jumlah FROM notifikasi_admin");
$jumlahNotif = ($resNotif) ? ($resNotif->fetch_assoc()['jumlah'] ?? 0) : 0;

$notifications = []; $notifCount = 0;
$stmt = $conn->prepare("SELECT id, pesan, sudah_dibaca, dibuat_pada FROM notifikasi_reseller WHERE username IS NULL OR username = ? ORDER BY dibuat_pada DESC LIMIT 10");
if ($stmt) {
    $stmt->bind_param("s", $reseller);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if (!$row['sudah_dibaca']) $notifCount++;
        $notifications[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id" class="<?= $theme==='dark'?'dark':'' ?>">
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
<body class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
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
  <button onclick="toggleTheme()" class="text-xl p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700">
    <span id="themeIcon"><?= $theme === 'dark' ? '🌞' : '🌙' ?></span>
  </button>
    <a href="../logout.php" class="px-3 py-2 bg-red-600 text-white rounded hover:bg-red-500 text-sm">
      Logout
    </a>
  </div>
</header>

<!-- HEADER -->

<!-- NOTIF DROPDOWN -->
<div id="notifDropdown" class="hidden absolute top-16 right-4 w-80 bg-white dark:bg-gray-800 shadow-lg rounded border z-30">
  <div class="px-4 py-2 font-semibold border-b">🔔 Notifikasi</div>
  <div class="max-h-60 overflow-y-auto divide-y">
    <?php if(count($notifications)): foreach($notifications as $n): ?>
    <div class="px-4 py-3 <?= $n['sudah_dibaca']?'':'bg-blue-50 dark:bg-blue-900' ?>">
      <p class="text-sm"><?= htmlspecialchars($n['pesan']) ?></p>
      <span class="text-xs text-gray-500"><?= date('d M H:i', strtotime($n['dibuat_pada'])) ?></span>
    </div>
    <?php endforeach; else: ?>
    <div class="px-4 py-4 text-center text-gray-500 italic">Belum ada notifikasi.</div>
    <?php endif; ?>
    <div class="p-3 text-center border-t">
      <form action="tandai-notif-dibaca.php" method="POST">
        <button type="submit" class="text-sm text-blue-600">Tandai sudah dibaca</button>
      </form>
    </div>
  </div>
</div>

<div class="flex">
  <!-- SIDEBAR -->
  <aside id="sidebar" class="fixed inset-y-0 left-0 bg-white dark:bg-gray-800 w-64 p-4 transform -translate-x-full md:translate-x-0 transition-transform z-20">
    <!-- Avatar Upload -->
    <form action="upload-avatar.php" method="POST" enctype="multipart/form-data" class="flex flex-col items-center mb-4">
      <label for="avatarUpload" class="cursor-pointer">
        <?php
        $avatarPath = 'uploads/avatars/default.png';
        $avatarJson = __DIR__.'/uploads/avatar.json';
        if (file_exists($avatarJson)) {
          $arr = json_decode(file_get_contents($avatarJson), true);
          if(isset($arr[$reseller]) && file_exists(__DIR__.'/'.$arr[$reseller])) $avatarPath = $arr[$reseller];
        }
        ?>
        <img src="<?= $avatarPath ?>?v=<?= time() ?>" class="w-20 h-20 rounded-full mb-2">
      </label>
      <input type="file" name="avatar" id="avatarUpload" class="hidden" accept="image/*" onchange="this.form.submit()">
      <div class="font-semibold">@<?= htmlspecialchars($reseller) ?></div>
    </form>

    <!-- Menu -->
    <nav class="space-y-2">
      <?php
      $menus = [
        'dashboard'=>'📊 Dashboard','ssh'=>'🔐 SSH','vmess'=>'🌀 Vmess',
        'vless'=>'📡 Vless','trojan'=>'⚔ Trojan','shadowsocks'=>'🥷🏽 Shadowsocks',
        'topup'=>'💳 Top Up','cek-server'=>'🖥 Cek Server','vip'=>'👑 Grup VIP'
      ];
      foreach($menus as $k=>$lbl){
        $act = $page==$k?'bg-blue-500 text-white':'hover:bg-blue-100 dark:hover:bg-gray-700';
        echo "<a href='?page=$k' class='block px-3 py-2 rounded $act'>$lbl</a>";
        if($k=='shadowsocks') echo "<hr class='my-2 border-gray-300 dark:border-gray-600'>";
      }
      ?>
    </nav>
  </aside>

  <!-- MAIN CONTENT -->
  <main class="flex-1 ml-0 md:ml-64 p-4">
  <?php
  $path = __DIR__ . "/pages/{$page}.php";
  if (file_exists($path)) {
      include $path;
  } else {
      echo "<div class='text-red-500'>Halaman <b>{$page}</b> tidak ditemukan.</div>";
  }
  ?>
  </main>
</div>

<script>
var notifCount = <?= $notifCount ?>;
if (notifCount > 0) {
    let show = false;
    setInterval(() => {
        document.title = (show ? "🔔 " : "") + "<?= ($notifCount > 0 ? "($notifCount) " : "") ?>Tokomard Panel";
        show = !show;
    }, 3000);
}
function toggleTheme() {
    const html = document.documentElement;
    const isDark = html.classList.contains('dark');
    const newTheme = isDark ? 'light' : 'dark';
    html.classList.toggle('dark');

    const icon = document.getElementById('themeIcon');
    if (icon) icon.textContent = newTheme === 'dark' ? '🌞' : '🌙';

    fetch('simpan-theme.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'theme=' + encodeURIComponent(newTheme)
    }).then(r => r.text()).then(console.log).catch(console.error);
}
</script>
</body>
</html>

