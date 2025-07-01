<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'koneksi.php';  
// Pastikan sebelum HTML dimulai
$theme = 'light';
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $themeFile = __DIR__ . '/uploads/theme.json';
    if (file_exists($themeFile)) {
        $themes = json_decode(file_get_contents($themeFile), true);
        if (isset($themes[$username])) {
            $theme = $themes[$username];
        }
    }
}
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'reseller') {
    header("Location: ../index.php");
    exit;
}
$reseller = $_SESSION['username'];
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
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <title>Tokomard Panel VPN</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 min-h-screen">

<div class="flex flex-col md:flex-row min-h-screen">
  <!-- SIDEBAR -->
  <aside class="w-full md:w-64 bg-white dark:bg-gray-900 text-gray-800 dark:text-white shadow-md p-4">
    <div class="text-center">
      <!-- Avatar Upload -->
      <form action="upload-avatar.php" method="POST" enctype="multipart/form-data">
        <label for="avatarUpload" class="cursor-pointer inline-block">
        <?php
        $avatarPath = 'uploads/avatars/default.png';
        $username = $_SESSION['username'] ?? 'guest';
        $avatarJsonPath = __DIR__ . '/uploads/avatar.json';

        if (file_exists($avatarJsonPath)) {
            $avatars = json_decode(file_get_contents($avatarJsonPath), true);
            if (isset($avatars[$username]) && file_exists(__DIR__ . '/' . $avatars[$username])) {
                $avatarPath = $avatars[$username];
            }
        }
        ?>
        <img src="<?= $avatarPath ?>?v=<?= time() ?>" class="w-20 h-20 rounded-full mb-2 hover:opacity-80 transition" />
        </label>
        <input type="file" name="avatar" id="avatarUpload" accept="image/*" class="hidden" onchange="this.form.submit()">
      </form>
      <h2 class="text-base font-semibold">@<?= htmlspecialchars($reseller) ?></h2>
    </div>

    <nav class="space-y-2 text-sm mt-6">
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

  <!-- MAIN CONTENT -->
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

        echo "<div class='grid grid-cols-2 md:grid-cols-5 gap-4 text-center mb-6'>";
        foreach ($stats as $key => $value) {
            echo "<div class='p-4 bg-blue-100 dark:bg-blue-800 rounded shadow'>
                    <div class='text-sm uppercase font-bold'>" . strtoupper($key) . "</div>
                    <div class='text-xl font-semibold'>$value</div>
                  </div>";
        }
        echo "</div>";

        echo "<div class='overflow-auto'>";
        echo "<table class='min-w-full text-sm text-left border border-gray-300 dark:border-gray-700'>";
        echo "<thead class='bg-gray-200 dark:bg-gray-700'>
                <tr>
                  <th class='px-3 py-2'>No</th>
                  <th class='px-3 py-2'>Username</th>
                  <th class='px-3 py-2'>Protocol</th>
                  <th class='px-3 py-2'>Expired</th>
                  <th class='px-3 py-2'>Password/UUID</th>
                </tr>
              </thead><tbody>";
        foreach ($rows as $row) {
            echo "<tr class='border-t border-gray-300 dark:border-gray-700'>
                    <td class='px-3 py-2'>{$row['no']}</td>
                    <td class='px-3 py-2'>{$row['user']}</td>
                    <td class='px-3 py-2'>{$row['proto']}</td>
                    <td class='px-3 py-2'>{$row['exp']}</td>
                    <td class='px-3 py-2'>{$row['buyer']}</td>
                  </tr>";
        }
        echo "</tbody></table></div>";
    } else {
        if (file_exists($pagePath)) include $pagePath;
        else echo "<div class='text-red-500'>Halaman tidak ditemukan.</div>";
    }
    ?>
  </section>
</div>

</body>
</html>
