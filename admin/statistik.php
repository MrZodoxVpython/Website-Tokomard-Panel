<?php
session_start();
$__start_time = microtime(true);
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit;
}

$configPath = '/etc/xray/config.json';
$logPath = '/var/log/xray/access.log';

if (!file_exists($configPath)) {
    echo "<p style='color:red;'>❌ File config.json tidak ditemukan!</p>";
    exit;
}

$data = file_get_contents($configPath);
if ($data === false) {
    echo "<p style='color:red;'>❌ Gagal membaca file config.json!</p>";
    exit;
}
error_log("⏱ Setelah baca config.json: " . round(microtime(true) - $__start_time, 3) . "s");

$lines = preg_grep('/^\s*#/', explode("\n", $data)); // hanya baris komentar akun
$protocolCounts = [
    'vmess' => 0,
    'vless' => 0,
    'trojan' => 0,
    'ss' => 0
];

$expiredUsers = [];
$expiringSoonUsers = [];
$seenUsers = [];
$usersByProtocol = [
    'vmess' => [],
    'vless' => [],
    'trojan' => [],
    'ss' => []
];
$today = date('Y-m-d');
$sevenDaysLater = date('Y-m-d', strtotime('+7 days'));

foreach ($lines as $line) {
    $line = trim($line);

    if (preg_match('/^(###|#&|#!|#\$)\s+(\S+)\s+(\d{4}-\d{2}-\d{2})$/', $line, $match)) {
        $prefix = $match[1];
        $username = $match[2];
        $expDate = $match[3];

        if (isset($seenUsers[$username])) continue;
        $seenUsers[$username] = true;

        switch ($prefix) {
            case '###': $protocol = 'vmess'; break;
            case '#&': $protocol = 'vless'; break;
            case '#!': $protocol = 'trojan'; break;
            case '#$': $protocol = 'ss'; break;
            default: $protocol = 'unknown';
        }

        $protocolCounts[$protocol]++;
        $usersByProtocol[$protocol][] = [
            'username' => $username,
            'expired' => $expDate
        ];

        if ($expDate < $today) {
            $expiredUsers[] = [
                'username' => $username,
                'protocol' => strtoupper($protocol),
                'expired' => $expDate
            ];
        } elseif ($expDate <= $sevenDaysLater) {
            $expiringSoonUsers[] = [
                'username' => $username,
                'protocol' => strtoupper($protocol),
                'expired' => $expDate
            ];
        }
    }
}

$activeUsers = [];
$startTime = date('Y/m/d H:i:s', strtotime('-1 minute'));
$usernames = array_keys($seenUsers);

if (file_exists($logPath)) {
    $logContent = explode("\n", shell_exec("tail -n 500 /var/log/xray/access.log"));
    $uniqueUsers = [];
    foreach ($logContent as $logLine) {
        if (preg_match('/^(\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2}).*email: (\S+)/', $logLine, $matches)) {
            $logTime = $matches[1];
            $logUser = $matches[2];
            if ($logTime > $startTime && in_array($logUser, $usernames)) {
                $uniqueUsers[$logUser] = true;
                $activeUsers[$logUser] = true;
            }
        }
    }
}

// ✅ Perbaikan path include dengan __DIR__
include __DIR__ . '/../templates/header.php';
error_log("⏱ Setelah include header.php: " . round(microtime(true) - $__start_time, 3) . "s");
?>

<!-- HTML TETAP SAMA: -->
<div class="container mx-auto px-4 py-6">
  <div class="text-center mb-10">
    <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-white">Statistik Akun VPN</h1>
    <p class="text-gray-400 mt-2 text-sm sm:text-base">Menampilkan jumlah akun yang terdaftar berdasarkan jenis protokol.</p>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-12">
    <?php foreach ($protocolCounts as $key => $val): ?>
    <div class="bg-<?php echo $key === 'vmess' ? 'blue' : ($key === 'vless' ? 'purple' : ($key === 'trojan' ? 'pink' : 'yellow')); ?>-600 rounded-xl p-4 sm:p-5 shadow text-white text-center">
      <h3 class="text-base sm:text-xl font-semibold">
         Akun <?php echo $key === 'ss' ? 'Shadowsocks' : strtoupper($key); ?>
      </h3>
      <p class="text-2xl sm:text-3xl mt-2"><?php echo $val; ?></p>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- tabel lainnya tidak diubah -->
  <!-- ... semua HTML lainnya tetap sama seperti yang kamu kirim ... -->

  <div class="text-center">
    <a href="dashboard.php" class="inline-block bg-gray-700 hover:bg-gray-800 text-white py-3 px-6 rounded-xl text-base sm:text-lg font-semibold transition">⬅ Kembali ke Dashboard</a>
  </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

