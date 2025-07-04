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
<!-- HEADER -->
<header class="flex justify-between items-center bg-gray-100 dark:bg-gray-800 p-3 shadow sticky top-0 z-20">
  <div class="flex items-center space-x-3">
    <button id="sidebarToggle" class="md:hidden text-gray-700 dark:text-gray-300">
      &#9776;
    </button>
    <div class="relative cursor-pointer" onclick="toggleNotif(event)">
      <img src="https://i.imgur.com/q3DzxiB.png" class="w-10 h-10" alt="Logo">
      <?php if ($notifCount): ?>
      <span class="absolute top-0 right-0 bg-red-600 text-white text-xs rounded-full px-1 animate-pulse"><?= $notifCount>9?'9+':$notifCount ?></span>
      <?php endif; ?>
    </div>
    <h1 class="text-xl font-bold">Panel Reseller</h1>
  </div>
  <div class="flex items-center space-x-3">
    <button onclick="toggleTheme()" class="p-2 rounded bg-gray-200 dark:bg-gray-700">
      <span id="themeIcon"><?= $theme==='dark'?'🌞':'🌙' ?></span>
    </button>
    <a href="../logout.php" class="bg-red-600 text-white px-3 py-1 rounded">Logout</a>
  </div>
</header>

<!-- NOTIF DROPDOWN -->
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

<!-- SIDEBAR -->
<div class="flex">
<aside id="sidebar" class="fixed inset-y-0 left-0 bg-white dark:bg-gray-800 w-64 p-4 transform -translate-x-full md:translate-x-0 transition-transform z-20">
  <!-- Avatar Upload -->
  <form action="upload-avatar.php" method="POST" enctype="multipart/form-data" class="flex flex-col items-center mb-1 relative">
    <label for="avatarUpload" class="cursor-pointer relative mt-1">
      <?php
      $avatarPath = 'uploads/avatars/default.png';
      $avatarJson = __DIR__.'/uploads/avatar.json';
      if (file_exists($avatarJson)) {
        $arr = json_decode(file_get_contents($avatarJson), true);
        if(isset($arr[$reseller]) && file_exists(__DIR__.'/'.$arr[$reseller])) $avatarPath = $arr[$reseller];
      }
      ?>
      <img src="<?= $avatarPath ?>?v=<?= time() ?>" class="w-20 h-20 rounded-full mb-2">

      <!-- Titik merah di avatar DIHAPUS -->
      <!--
      <?php if ($notifCount > 0): ?>
      <span class="absolute top-0 right-0 w-5 h-5 text-[12px] flex items-center justify-center font-bold text-white bg-red-600 border-2 border-white dark:border-gray-800 rounded-full animate-pulse z-10">
        <?= $notifCount > 9 ? '9+' : $notifCount ?>
      </span>
      <?php endif; ?>
      -->
    </label>

    <input type="file" name="avatar" id="avatarUpload" class="hidden" accept="image/*" onchange="this.form.submit()">
  </form>

  <!-- Username reseller + titik merah NOTIF -->
  <div class="relative text-center font-semibold cursor-pointer -mt-2 mb-8" onclick="toggleNotif(event)">
    @<?= htmlspecialchars($reseller) ?>
    <?php if ($notifCount > 0): ?>
    <span class="absolute -top-1.5 right-5 w-4 h-4 bg-red-600 text-white text-[13px] flex items-center justify-center font-bold rounded-full animate-pulse z-10">
      <?= $notifCount > 9 ? '9+' : $notifCount ?>
    </span>
    <?php endif; ?>
  </div>

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

<audio id="notifSound" src="uploads/notification.mp3" preload="auto" loop></audio>
<script>
var notifCount = <?= $notifCount ?>;
if (notifCount > 0) {
    let show = false;
    setInterval(() => {
        document.title = (show ? "🔔 " : "") + "<?= ($notifCount > 0 ? "($notifCount) " : "") ?>Tokomard Panel";
        show = !show;
    }, 3000);

    const audio = document.getElementById('notifSound');
    if (audio) {
        const playAudio = () => {
            audio.play().catch(() => {
                document.addEventListener('click', () => audio.play(), { once: true });
            });
        };
        playAudio();
    }
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
function toggleNotif(){
  document.getElementById('notifDropdown').classList.toggle('hidden');
}
// Toggle sidebar (mobile)
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('sidebarToggle');

// Saat klik tombol ☰
toggleBtn.addEventListener('click', (e) => {
  e.stopPropagation(); // Jangan biarkan klik ini menutup sidebar langsung
  sidebar.classList.toggle('-translate-x-full');
});

// Auto close jika klik di luar sidebar (khusus mobile)
document.addEventListener('click', (e) => {
  if (!sidebar.classList.contains('-translate-x-full') && !sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
    sidebar.classList.add('-translate-x-full');
  }
});
// Toggle Notifikasi
function toggleNotif(event) {
  event.stopPropagation(); // <- penting! mencegah dropdown langsung tertutup saat diklik
  const dropdown = document.getElementById('notifDropdown');
  dropdown.classList.toggle('hidden');
}

// Tutup dropdown saat klik di luar
document.addEventListener('click', function(event) {
  const dropdown = document.getElementById('notifDropdown');
  const trigger = document.querySelectorAll('[onclick^="toggleNotif"]');
let clickedInsideTrigger = false;
trigger.forEach(t => {
  if (t.contains(event.target)) clickedInsideTrigger = true;
});
if (
  !dropdown.classList.contains('hidden') &&
  !dropdown.contains(event.target) &&
  !clickedInsideTrigger
) {
  dropdown.classList.add('hidden');
}
 
});

// Toggle dropdown dengan status aktif
let notifIsOpen = false;

function toggleNotif(event) {
  event.stopPropagation();
  const dropdown = document.getElementById('notifDropdown');
  notifIsOpen = !notifIsOpen;
  dropdown.classList.toggle('hidden', !notifIsOpen);
}

// Tutup dropdown jika klik di luar area dropdown atau icon
document.addEventListener('click', function(event) {
  const dropdown = document.getElementById('notifDropdown');
  const notifButtons = document.querySelectorAll('[onclick^="toggleNotif"]');

  let clickedInside = false;
  notifButtons.forEach(btn => {
    if (btn.contains(event.target)) clickedInside = true;
  });

  if (!dropdown.contains(event.target) && !clickedInside) {
    notifIsOpen = false;
    dropdown.classList.add('hidden');
  }
});

</script>
</body>
</html>

