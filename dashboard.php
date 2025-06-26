<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);


session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}
$start = microtime(true);

// Ambil data akun dari config.json
$configPath = '/etc/xray/config.json';
if (!file_exists($configPath)) {
    die("❌ File config.json tidak ditemukan!");
}

$data = @file_get_contents($configPath);
if (!$data) {
    die("❌ Gagal membaca config.json!");
}


$lines = explode("\n", $data);
$total = 0;
$expired = 0;
$active = 0;
$today = date('Y-m-d');
$usernames = [];

// Cegah duplikat dari tag ganda (misal trojanws dan trojangrpc)
$seen = [];

foreach ($lines as $line) {
    $line = trim($line);
    if (preg_match('/^(###|#&|#!|#\$) (\S+) (\d{4}-\d{2}-\d{2})$/', $line, $match)) {
        $prefix = $match[1];
        $username = $match[2];
        $expDate = $match[3];

        // Jika user sudah pernah tercatat, lewati (untuk mencegah duplikat karena ada di 2 tag)
        if (isset($seen[$username])) continue;
        $seen[$username] = true;

        $usernames[] = $username;
        $total++;
        if ($expDate < $today) {
            $expired++;
        } else {
            $active++;
        }
    }
}

// Deteksi user aktif dari log
$accessLog = '/var/log/xray/access.log';
$logActive = 0;
if (file_exists($accessLog)) {
    $startTime = date('Y/m/d H:i:s', strtotime('-1 minute'));
    $logContent = explode("\n", shell_exec("tail -n 200 $accessLog"));
    $uniqueUsers = [];
    foreach ($logContent as $logLine) {
        if (preg_match('/^(\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2}).*email: (\S+)/', $logLine, $matches)) {
            $logTime = $matches[1];
            $logUser = $matches[2];
            if ($logTime > $startTime && in_array($logUser, $usernames)) {
                $uniqueUsers[$logUser] = true;
            }
        }
    }
    $logActive = count($uniqueUsers);
}


$trafficToday = '1.2 GB'; // Placeholder

include 'templates/header.php';

$end = microtime(true);
$duration = round($end - $start, 3);
error_log("⏱ Dashboard load time: {$duration} seconds");

?>

<div class="container mx-auto px-4 py-6">

  <!-- Hero Section -->
  <div class="text-center mb-10">
    <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-white">
      Selamat datang, <?php echo htmlspecialchars($_SESSION['username']); ?> 👋
    </h1>
    <p class="text-gray-400 mt-2 text-sm sm:text-base">
      Panel Tokomard adalah website untuk mengelola akun Xray dengan mudah dan efisien.
    </p>
  </div>

  <!-- Stats Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-12">
    <div class="bg-blue-600 rounded-xl p-4 sm:p-5 shadow text-white text-center">
      <h3 class="text-base sm:text-xl font-semibold">Total Akun</h3>
      <p class="text-2xl sm:text-3xl mt-2"><?php echo $total; ?></p>
    </div>
    <div class="bg-green-600 rounded-xl p-4 sm:p-5 shadow text-white text-center">
      <h3 class="text-base sm:text-xl font-semibold">Akun Aktif (Belum Expired)</h3>
      <p class="text-2xl sm:text-3xl mt-2"><?php echo $active; ?></p>
    </div>
    <div class="bg-yellow-500 rounded-xl p-4 sm:p-5 shadow text-white text-center">
      <h3 class="text-base sm:text-xl font-semibold">Akun Expired</h3>
      <p class="text-2xl sm:text-3xl mt-2"><?php echo $expired; ?></p>
    </div>
    <div class="bg-red-600 rounded-xl p-4 sm:p-5 shadow text-white text-center">
      <h3 class="text-base sm:text-xl font-semibold">User Aktif (Dalam 1 Menit)</h3>
      <p class="text-2xl sm:text-3xl mt-2"><?php echo $logActive; ?></p>
    </div>
  </div>

  <!-- Action Buttons -->
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6 text-center">
    <a href="kelola-akun.php" class="block bg-indigo-600 hover:bg-indigo-700 text-white py-3 sm:py-4 px-4 sm:px-6 rounded-xl text-base sm:text-lg font-semibold transition">
      ➕ Tambah / Kelola Akun
    </a>
    <a href="statistik.php" class="block bg-gray-700 hover:bg-gray-800 text-white py-3 sm:py-4 px-4 sm:px-6 rounded-xl text-base sm:text-lg font-semibold transition">
      📊 Lihat Statistik Lengkap
    </a>
        <a href="admin.php" class="block bg-pink-700 hover:bg-pink-800 text-white py-3 sm:py-4 px-4 sm:px-6 rounded-xl text-base sm:text-lg font-semibold transition">
      📊 Administratrol Tools
    </a>
    <a href="statis.php" class="block bg-blue-700 hover:bg-blue-800 text-white py-3 sm:py-4 px-4 sm:px-6 rounded-xl text-base sm:text-lg font-semibold transition">
      📊 Lihat Lengkap XxX
    </a>
  </div>

</div>

<?php include 'templates/footer.php'; ?>
