<?php
session_start();
$__start_time = microtime(true);
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
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

$lines = preg_grep('/^\s*#/', explode("\n", $data));
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

include 'templates/header.php';
error_log("⏱ Setelah include header.php: " . round(microtime(true) - $__start_time, 3) . "s");
?>

<!-- Tailwind dark mode version -->
<div class="container mx-auto px-4 py-6">
  <div class="text-center mb-10">
    <h1 class="text-4xl font-extrabold text-white dark:text-gray-100">Statistik Akun VPN</h1>
    <p class="text-gray-400 dark:text-gray-300 mt-2 text-base">Menampilkan jumlah akun yang terdaftar berdasarkan jenis protokol.</p>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
    <?php foreach ($protocolCounts as $key => $val): ?>
    <div class="bg-<?php echo $key === 'vmess' ? 'blue' : ($key === 'vless' ? 'purple' : ($key === 'trojan' ? 'pink' : 'yellow')); ?>-600 dark:bg-<?php echo $key === 'vmess' ? 'blue' : ($key === 'vless' ? 'purple' : ($key === 'trojan' ? 'pink' : 'yellow')); ?>-800 rounded-xl p-5 shadow text-white text-center">
      <h3 class="text-xl font-semibold">
         Akun <?php echo $key === 'ss' ? 'Shadowsocks' : strtoupper($key); ?>
      </h3>
      <p class="text-3xl mt-2"><?php echo $val; ?></p>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="bg-green-800 dark:bg-green-900 rounded-xl p-6 shadow mb-10">
    <h2 class="text-xl font-bold text-white dark:text-gray-100 mb-4">Daftar Akun Aktif (Online < 1 Menit)</h2>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-green-700 dark:divide-green-800">
        <thead class="bg-green-700 dark:bg-green-800">
          <tr>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white dark:text-gray-100">Username</th>
          </tr>
        </thead>
        <tbody class="bg-green-600 dark:bg-green-700 divide-y divide-green-700 dark:divide-green-800">
          <?php foreach (array_keys($activeUsers) as $username): ?>
          <tr>
            <td class="px-4 py-2 text-sm text-white dark:text-gray-100"><?php echo htmlspecialchars($username); ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($activeUsers)): ?>
          <tr><td class="px-4 py-2 text-sm text-white dark:text-gray-100 text-center">Tidak ada akun aktif saat ini.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php foreach ($usersByProtocol as $proto => $entries): ?>
  <div class="bg-gray-800 dark:bg-gray-900 rounded-xl p-6 shadow mb-10">
    <h2 class="text-xl font-bold text-white dark:text-gray-100 mb-4">Daftar Akun <?php echo $proto === 'ss' ? 'Shadowsocks' : strtoupper($proto); ?></h2>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-700 dark:divide-gray-800">
        <thead class="bg-gray-700 dark:bg-gray-800">
          <tr>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white dark:text-gray-100">Username</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white dark:text-gray-100">Tanggal Expired</th>
          </tr>
        </thead>
        <tbody class="bg-gray-600 dark:bg-gray-700 divide-y divide-gray-700 dark:divide-gray-800">
          <?php foreach ($entries as $entry): ?>
          <tr>
            <td class="px-4 py-2 text-sm text-white dark:text-gray-100"><?php echo htmlspecialchars($entry['username']); ?></td>
            <td class="px-4 py-2 text-sm text-white dark:text-gray-100"><?php echo htmlspecialchars($entry['expired']); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endforeach; ?>

  <div class="bg-yellow-800 dark:bg-yellow-900 rounded-xl p-6 shadow mb-10">
    <h2 class="text-xl font-bold text-white dark:text-gray-100 mb-4">Daftar Akun Akan Expired (≤ 7 Hari)</h2>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-yellow-800 dark:divide-yellow-900">
        <thead class="bg-yellow-800 dark:bg-yellow-900">
          <tr>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white dark:text-gray-100">Username</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white dark:text-gray-100">Protokol</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white dark:text-gray-100">Tanggal Expired</th>
          </tr>
        </thead>
        <tbody class="bg-yellow-700 dark:bg-yellow-800 divide-y divide-yellow-800 dark:divide-yellow-900">
          <?php foreach ($expiringSoonUsers as $user): ?>
          <tr>
            <td class="px-4 py-2 text-sm text-white dark:text-gray-100"><?php echo htmlspecialchars($user['username']); ?></td>
            <td class="px-4 py-2 text-sm text-white dark:text-gray-100"><?php echo htmlspecialchars($user['protocol']); ?></td>
            <td class="px-4 py-2 text-sm text-white dark:text-gray-100"><?php echo htmlspecialchars($user['expired']); ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($expiringSoonUsers)): ?>
          <tr><td colspan="3" class="px-4 py-2 text-sm text-white dark:text-gray-100 text-center">Belum ada akun yang akan expired dalam 7 hari.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if (!empty($expiredUsers)): ?>
  <div class="bg-red-800 dark:bg-red-900 rounded-xl p-6 shadow mb-10">
    <h2 class="text-xl font-bold text-white dark:text-gray-100 mb-4">Daftar Akun Expired</h2>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-red-800 dark:divide-red-900">
        <thead class="bg-red-800 dark:bg-red-900">
          <tr>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white dark:text-gray-100">Username</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white dark:text-gray-100">Protokol</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white dark:text-gray-100">Tanggal Expired</th>
          </tr>
        </thead>
        <tbody class="bg-red-700 dark:bg-red-800 divide-y divide-red-800 dark:divide-red-900">
          <?php foreach ($expiredUsers as $user): ?>
          <tr>
            <td class="px-4 py-2 text-sm text-white dark:text-gray-100"><?php echo htmlspecialchars($user['username']); ?></td>
            <td class="px-4 py-2 text-sm text-white dark:text-gray-100"><?php echo htmlspecialchars($user['protocol']); ?></td>
            <td class="px-4 py-2 text-sm text-white dark:text-gray-100"><?php echo htmlspecialchars($user['expired']); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <div class="text-center">
    <a href="dashboard.php" class="inline-block bg-gray-700 dark:bg-gray-800 hover:bg-gray-800 dark:hover:bg-gray-900 text-white py-3 px-6 rounded-xl text-lg font-semibold transition">⬅ Kembali ke Dashboard</a>
  </div>
</div>

<?php include 'templates/footer.php'; ?>

