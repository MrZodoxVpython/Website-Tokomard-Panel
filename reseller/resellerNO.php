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
  <title><?= $notifCount ? "($notifCount) " : "" ?>Panel Reseller Tokomard</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">

<!-- HEADER -->
<header class="flex justify-between items-center bg-gray-100 dark:bg-gray-800 p-2 shadow sticky top-0 z-20">
  <div class="flex items-center space-x-3">
    <button id="sidebarToggle" class="md:hidden text-gray-700 dark:text-gray-300">
      &#9776;
    </button>
    <div class="relative cursor-pointer" onclick="toggleNotif()">
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
        'vless'=>'📡 Vless','trojan'=>'⚔ Trojan','shadowsocks'=>'🕶 Shadowsocks',
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
function toggleNotif(){
  document.getElementById('notifDropdown').classList.toggle('hidden');
}
document.getElementById('sidebarToggle').addEventListener('click',()=> {
  document.getElementById('sidebar').classList.toggle('-translate-x-full');
});
function toggleTheme(){
  let html=document.documentElement;
  html.classList.toggle('dark');
  document.getElementById('themeIcon').textContent = html.classList.contains('dark')?'🌞':'🌙';
}
</script>
</body>
</html>

