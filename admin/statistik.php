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

$lines = preg_grep('/^\s*#/', explode("\n", $data)); // baris komentar akun
$protocolCounts = ['vmess' => 0, 'vless' => 0, 'trojan' => 0, 'ss' => 0];
$expiredUsers = [];
$expiringSoonUsers = [];
$seenUsers = [];
$usersByProtocol = ['vmess' => [], 'vless' => [], 'trojan' => [], 'ss' => []];

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

        $protocol = match ($prefix) {
            '###' => 'vmess',
            '#&'  => 'vless',
            '#!'  => 'trojan',
            '#$'  => 'ss',
            default => 'unknown',
        };

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
$startTime = strtotime('-1 minute');
$usernames = array_keys($seenUsers);

if (file_exists($logPath)) {
    $logLines = explode("\n", shell_exec("tail -n 500 $logPath"));
    foreach ($logLines as $line) {
        if (preg_match('/^(\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2})/', $line, $timeMatch) &&
            preg_match('/email:\s*(\S+)/', $line, $userMatch)) {

            $logTimestamp = strtotime(str_replace('/', '-', $timeMatch[1]));
            $logUser = trim($userMatch[1]);

            if ($logTimestamp >= $startTime && in_array($logUser, $usernames)) {
                $activeUsers[$logUser] = true;
            }
        }
    }
}

include '../templates/header.php';
?>

<!-- Tailwind HTML UI -->
<div class="container mx-auto px-4 py-6">
  <div class="text-center mb-10">
    <h1 class="text-3xl font-extrabold text-white">Statistik Akun VPN</h1>
    <p class="text-gray-400 mt-2 text-sm">Menampilkan statistik akun berdasarkan VPS dan protokol.</p>
  </div>

  <!-- Kartu Statistik -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
    <?php foreach ($protocolCounts as $proto => $jumlah): ?>
    <div class="rounded-xl p-4 shadow text-white text-center 
                <?php echo match($proto) {
                  'vmess' => 'bg-blue-600',
                  'vless' => 'bg-purple-600',
                  'trojan' => 'bg-pink-600',
                  'ss'     => 'bg-yellow-600',
                }; ?>">
      <h3 class="text-xl font-semibold">
        Akun <?php echo $proto === 'ss' ? 'Shadowsocks' : strtoupper($proto); ?>
      </h3>
      <p class="text-3xl mt-2"><?php echo $jumlah; ?></p>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Tabel Aktif -->
  <div class="bg-green-800 rounded-xl p-4 mb-8 shadow">
    <h2 class="text-xl font-bold text-white mb-4">Daftar Akun Aktif (Online &lt; 1 Menit)</h2>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-green-700">
        <thead class="bg-green-700">
          <tr><th class="px-4 py-2 text-left text-sm font-semibold text-white">Username</th></tr>
        </thead>
        <tbody class="bg-green-600 divide-y divide-green-700">
          <?php if (!empty($activeUsers)): ?>
            <?php foreach (array_keys($activeUsers) as $username): ?>
              <tr><td class="px-4 py-2 text-sm text-white"><?php echo htmlspecialchars($username); ?></td></tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td class="px-4 py-2 text-sm text-center text-white">Tidak ada akun aktif saat ini.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Tabel per Protokol -->
  <?php foreach ($usersByProtocol as $proto => $daftar): ?>
  <div class="bg-gray-800 rounded-xl p-4 mb-8 shadow">
    <h2 class="text-xl font-bold text-white mb-4">Daftar Akun <?php echo $proto === 'ss' ? 'Shadowsocks' : strtoupper($proto); ?></h2>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-700">
        <thead class="bg-gray-700">
          <tr>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white">Username</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white">Expired</th>
          </tr>
        </thead>
        <tbody class="bg-gray-600 divide-y divide-gray-700">
          <?php foreach ($daftar as $entry): ?>
          <tr>
            <td class="px-4 py-2 text-sm text-white"><?php echo htmlspecialchars($entry['username']); ?></td>
            <td class="px-4 py-2 text-sm text-white"><?php echo htmlspecialchars($entry['expired']); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- Tabel Akan Expired -->
  <div class="bg-yellow-800 rounded-xl p-4 mb-8 shadow">
    <h2 class="text-xl font-bold text-white mb-4">Daftar Akun Akan Expired (≤ 7 Hari)</h2>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-yellow-700">
        <thead class="bg-yellow-700">
          <tr>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white">Username</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white">Protokol</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white">Expired</th>
          </tr>
        </thead>
        <tbody class="bg-yellow-600 divide-y divide-yellow-700">
          <?php foreach ($expiringSoonUsers as $user): ?>
          <tr>
            <td class="px-4 py-2 text-sm text-white"><?php echo htmlspecialchars($user['username']); ?></td>
            <td class="px-4 py-2 text-sm text-white"><?php echo htmlspecialchars($user['protocol']); ?></td>
            <td class="px-4 py-2 text-sm text-white"><?php echo htmlspecialchars($user['expired']); ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($expiringSoonUsers)): ?>
          <tr><td colspan="3" class="px-4 py-2 text-sm text-center text-white">Belum ada akun yang akan expired dalam 7 hari.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Tabel Expired -->
  <?php if (!empty($expiredUsers)): ?>
  <div class="bg-red-800 rounded-xl p-4 mb-10 shadow">
    <h2 class="text-xl font-bold text-white mb-4">Daftar Akun Expired</h2>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-red-700">
        <thead class="bg-red-700">
          <tr>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white">Username</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white">Protokol</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white">Expired</th>
          </tr>
        </thead>
        <tbody class="bg-red-600 divide-y divide-red-700">
          <?php foreach ($expiredUsers as $user): ?>
          <tr>
            <td class="px-4 py-2 text-sm text-white"><?php echo htmlspecialchars($user['username']); ?></td>
            <td class="px-4 py-2 text-sm text-white"><?php echo htmlspecialchars($user['protocol']); ?></td>
            <td class="px-4 py-2 text-sm text-white"><?php echo htmlspecialchars($user['expired']); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <div class="text-center">
    <a href="dashboard.php" class="inline-block bg-gray-700 hover:bg-gray-800 text-white py-3 px-6 rounded-xl font-semibold transition">⬅ Kembali ke Dashboard</a>
  </div>
</div>

<?php include '../templates/footer.php'; ?>

