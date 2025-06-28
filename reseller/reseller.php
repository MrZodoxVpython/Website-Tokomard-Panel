<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'reseller') {
    header("Location: ../index.php");
    exit;
}

$reseller = $_SESSION['username'];
$theme = $_SESSION['theme'] ?? 'light';
$page = $_GET['page'] ?? 'dashboard';
$avatar = $_SESSION['avatar'] ?? 'uploads/avatars/default.png';

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
<html lang="id" class="<?= ($theme === 'dark') ? 'dark' : '' ?>">
<head>
  <meta charset="UTF-8">
  <title><?= ($notifCount > 0 ? "($notifCount) " : "") . "Tokomard Panel" ?></title>
  <link rel="icon" href="<?= $avatar ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">

<!-- Header -->
<header class="px-3 py-2 bg-gray-100 dark:bg-gray-800 shadow-md flex justify-between items-center sticky top-0 z-10">
  <div class="flex items-center space-x-3">
    <img src="https://i.imgur.com/q3DzxiB.png" class="w-10 cursor-pointer" onclick="document.getElementById('notifDropdown').classList.toggle('hidden')" />
    <h1 class="text-xl font-bold">Panel Reseller Tokomard</h1>
  </div>
  <div class="flex items-center gap-4">
    <div class="relative">
      <?php if ($notifCount > 0): ?>
        <span class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full px-1"><?= $notifCount ?></span>
      <?php endif; ?>
    </div>
    <button id="themeToggleBtn" class="text-xl p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700"><?= ($theme === 'dark') ? '🌞' : '🌙' ?></button>
    <a href="pesan.php" class="px-3 py-2 bg-red-600 text-white rounded hover:bg-red-500 text-sm">Logout</a>
  </div>
</header>

<!-- Dropdown Notifikasi -->
<div class="relative">
  <div id="notifDropdown" class="hidden absolute top-16 left-4 w-80 bg-gray-50 dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden z-20">
    <div class="px-4 py-2 font-semibold border-b dark:border-gray-700">Notifikasi Kamu</div>
    <?php if (count($notifications) > 0): ?>
      <?php foreach ($notifications as $n): ?>
        <div class="px-4 py-2 flex justify-between items-center <?= $n['sudah_dibaca'] ? 'bg-gray-100 dark:bg-gray-700' : 'bg-blue-100 dark:bg-blue-700' ?>">
          <span class="text-sm"><?= htmlspecialchars($n['pesan']) ?></span>
          <span class="text-xs text-gray-500"><?= date('d M H:i', strtotime($n['dibuat_pada'])) ?></span>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="px-4 py-2 text-gray-500 italic">Belum ada notifikasi.</div>
    <?php endif; ?>
    <div class="px-4 py-2 text-center border-t dark:border-gray-700">
      <form action="tandai-notif-dibaca.php" method="POST">
        <button type="submit" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">Tandai sudah dibaca</button>
      </form>
    </div>
  </div>
</div>

<!-- Sidebar & Main -->
<main class="flex flex-col md:flex-row w-full px-4 md:px-8 py-6 gap-6">
  <aside class="md:w-1/5 bg-gray-100 dark:bg-gray-800 p-5 shadow-lg rounded-lg">
    <div class="flex flex-col items-center text-center mb-6">
      <form id="avatarForm" action="upload-avatar.php" method="POST" enctype="multipart/form-data">
        <label for="avatarUpload" class="cursor-pointer">
          <img src="<?= $avatar . '?v=' . time() ?>" class="w-20 h-20 rounded-full mb-2 hover:opacity-80" />
        </label>
        <input type="file" name="avatar" id="avatarUpload" accept="image/*" class="hidden" onchange="document.getElementById('avatarForm').submit()" />
      </form>
      <h2 class="text-base font-semibold">@<?= htmlspecialchars($reseller) ?></h2>
    </div>
    <nav class="space-y-2 text-sm">
      <?php
      $menus = [
        'dashboard' => '📊 Dashboard',
        'ssh' => '🔐 SSH',
        'vmess' => '🌀 Vmess',
        'vless' => '📡 Vless',
        'trojan' => '⚔ Trojan',
        'shadowsocks' => '🕶 Shadowsocks',
        'topup' => '💳 Top Up',
        'cek-server' => '🖥 Cek Server',
        'vip' => '👑 Grup VIP'
      ];
      foreach ($menus as $key => $label) {
          $active = ($page === $key) ? 'bg-blue-500 text-white dark:bg-blue-600' : 'hover:bg-blue-200 dark:hover:bg-gray-700';
          echo "<a href='?page=$key' class='block px-3 py-2 rounded $active'>$label</a>";
          if ($key === 'shadowsocks') echo "<hr class='my-2 border-gray-400 dark:border-gray-600'/>";
      }
      ?>
    </nav>
  </aside>

  <section class="flex-1 p-5 bg-white dark:bg-gray-900 rounded-xl shadow-md">
    <?php
    $pagePath = __DIR__ . "/pages/" . $page . ".php";
    if ($page === 'dashboard') {
        include "pages/dashboard.php";
    } elseif (file_exists($pagePath)) {
        include $pagePath;
    } else {
        echo "<div class='text-red-500 text-center'>Halaman <b>" . htmlspecialchars($page) . "</b> tidak ditemukan.</div>";
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
    $_SESSION['theme'] = ($_GET['theme'] === 'dark') ? 'dark' : 'light';
    exit;
}
?>
</body>
</html>

