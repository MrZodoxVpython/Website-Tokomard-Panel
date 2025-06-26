<?php
session_start();
$__start_time = microtime(true);

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$configPath = '/etc/xray/config.json';
$configRaw = file_get_contents($configPath);
$config = json_decode($configRaw, true);

$vpsList = $config['vpsList'] ?? ['local' => '127.0.0.1'];
$selectedVps = $_GET['vps'] ?? array_key_first($vpsList);
$serverIP = $vpsList[$selectedVps] ?? '127.0.0.1';

$configFile = ($serverIP === '127.0.0.1') ? '/etc/xray/config.json' : "/tmp/config-$selectedVps.json";
$logPath    = ($serverIP === '127.0.0.1') ? '/var/log/xray/access.log' : "/tmp/access-$selectedVps.log";

if ($serverIP !== '127.0.0.1') {
    shell_exec("ssh -o StrictHostKeyChecking=no root@$serverIP 'cat /etc/xray/config.json' > $configFile");
    shell_exec("ssh -o StrictHostKeyChecking=no root@$serverIP 'tail -n 500 /var/log/xray/access.log' > $logPath");
}

if (!file_exists($configFile)) {
    echo "<p class='text-red-500'>❌ File config.json tidak ditemukan di VPS $selectedVps!</p>";
    exit;
}

$data = file_get_contents($configFile);
if ($data === false) {
    echo "<p class='text-red-500'>❌ Gagal membaca file config.json!</p>";
    exit;
}

$lines = preg_grep('/^\s*#/', explode("\n", $data));
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
        [$_, $prefix, $username, $expDate] = $match;
        if (isset($seenUsers[$username])) continue;
        $seenUsers[$username] = true;
        $protocol = match($prefix) {
            '###' => 'vmess',
            '#&'  => 'vless',
            '#!'  => 'trojan',
            '#$'  => 'ss',
            default => 'unknown'
        };
        $protocolCounts[$protocol]++;
        $usersByProtocol[$protocol][] = ['username' => $username, 'expired' => $expDate];

        if ($expDate < $today) {
            $expiredUsers[] = ['username' => $username, 'protocol' => strtoupper($protocol), 'expired' => $expDate];
        } elseif ($expDate <= $sevenDaysLater) {
            $expiringSoonUsers[] = ['username' => $username, 'protocol' => strtoupper($protocol), 'expired' => $expDate];
        }
    }
}

$activeUsers = [];
$startTime = date('Y/m/d H:i:s', strtotime('-1 minute'));
$usernames = array_keys($seenUsers);

if (file_exists($logPath)) {
    $logContent = explode("\n", file_get_contents($logPath));
    foreach ($logContent as $logLine) {
        if (preg_match('/^(\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2}).*email: (\S+)/', $logLine, $matches)) {
            [$_, $logTime, $logUser] = $matches;
            if ($logTime > $startTime && in_array($logUser, $usernames)) {
                $activeUsers[$logUser] = true;
            }
        }
    }
}

include 'templates/header.php';
?>

<div class="container mx-auto px-4 py-6">
  <div class="text-center mb-10">
    <h1 class="text-3xl md:text-4xl font-extrabold text-white">Statistik Akun VPN</h1>
    <p class="text-gray-400 mt-2">Menampilkan jumlah akun yang terdaftar berdasarkan jenis protokol.</p>
  </div>

  <!-- VPS Dropdown -->
  <div class="text-center mb-8">
    <form method="get" class="inline-flex items-center gap-3">
      <label for="vps" class="text-white text-lg">Pilih VPS:</label>
      <select name="vps" id="vps" onchange="this.form.submit()" class="bg-gray-900 border border-gray-700 text-white px-4 py-2 rounded-lg">
        <?php foreach ($vpsList as $vpsName => $vpsIP): ?>
        <option value="<?= $vpsName ?>" <?= $vpsName === $selectedVps ? 'selected' : '' ?>>
          <?= htmlspecialchars($vpsName) ?> (<?= $vpsIP ?>)
        </option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>

  <!-- Kotak Statistik -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
    <?php foreach ($protocolCounts as $key => $val): ?>
    <div class="bg-<?php echo $key === 'vmess' ? 'blue' : ($key === 'vless' ? 'purple' : ($key === 'trojan' ? 'pink' : 'yellow'); ?>-600 rounded-xl p-4 shadow text-white text-center">
      <h3 class="text-xl font-semibold">
         Akun <?= $key === 'ss' ? 'Shadowsocks' : strtoupper($key); ?>
      </h3>
      <p class="text-3xl mt-2"><?= $val; ?></p>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Tabel Akun Aktif -->
  <div class="bg-green-800 rounded-xl p-6 shadow mb-10">
    <h2 class="text-xl font-bold text-white mb-4">Akun Aktif (&lt; 1 Menit)</h2>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-green-700">
        <thead class="bg-green-700">
          <tr>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white">Username</th>
          </tr>
        </thead>
        <tbody class="bg-green-600 divide-y divide-green-700">
          <?php foreach (array_keys($activeUsers) as $username): ?>
          <tr><td class="px-4 py-2 text-sm text-white"><?= htmlspecialchars($username); ?></td></tr>
          <?php endforeach; ?>
          <?php if (empty($activeUsers)): ?>
          <tr><td class="px-4 py-2 text-sm text-white text-center">Tidak ada akun aktif saat ini.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Tabel Per Protokol -->
  <?php foreach ($usersByProtocol as $proto => $entries): ?>
  <div class="bg-gray-800 rounded-xl p-6 shadow mb-10">
    <h2 class="text-xl font-bold text-white mb-4">Akun <?= $proto === 'ss' ? 'Shadowsocks' : strtoupper($proto); ?></h2>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-700">
        <thead class="bg-gray-700">
          <tr>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white">Username</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white">Expired</th>
          </tr>
        </thead>
        <tbody class="bg-gray-600 divide-y divide-gray-700">
          <?php foreach ($entries as $entry): ?>
          <tr>
            <td class="px-4 py-2 text-sm text-white"><?= htmlspecialchars($entry['username']); ?></td>
            <td class="px-4 py-2 text-sm text-white"><?= htmlspecialchars($entry['expired']); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- Akan Expired -->
  <div class="bg-yellow-700 rounded-xl p-6 shadow mb-10">
    <h2 class="text-xl font-bold text-white mb-4">Akun Akan Expired (≤ 7 Hari)</h2>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-yellow-800">
        <thead class="bg-yellow-800">
          <tr>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white">Username</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white">Protokol</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white">Expired</th>
          </tr>
        </thead>
        <tbody class="bg-yellow-600 divide-y divide-yellow-800">
          <?php foreach ($expiringSoonUsers as $user): ?>
          <tr>
            <td class="px-4 py-2 text-sm text-white"><?= htmlspecialchars($user['username']); ?></td>
            <td class="px-4 py-2 text-sm text-white"><?= htmlspecialchars($user['protocol']); ?></td>
            <td class="px-4 py-2 text-sm text-white"><?= htmlspecialchars($user['expired']); ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($expiringSoonUsers)): ?>
          <tr><td colspan="3" class="px-4 py-2 text-sm text-white text-center">Tidak ada akun akan expired.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Expired -->
  <?php if (!empty($expiredUsers)): ?>
  <div class="bg-red-800 rounded-xl p-6 shadow mb-10">
    <h2 class="text-xl font-bold text-white mb-4">Akun Expired</h2>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-red-800">
        <thead class="bg-red-900">
          <tr>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white">Username</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white">Protokol</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white">Expired</th>
          </tr>
        </thead>
        <tbody class="bg-red-700 divide-y divide-red-800">
          <?php foreach ($expiredUsers as $user): ?>
          <tr>
            <td class="px-4 py-2 text-sm text-white"><?= htmlspecialchars($user['username']); ?></td>
            <td class="px-4 py-2 text-sm text-white"><?= htmlspecialchars($user['protocol']); ?></td>
            <td class="px-4 py-2 text-sm text-white"><?= htmlspecialchars($user['expired']); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <div class="text-center">
    <a href="dashboard.php" class="inline-block bg-gray-700 hover:bg-gray-800 text-white py-3 px-6 rounded-xl text-base font-semibold transition">⬅ Kembali ke Dashboard</a>
  </div>
</div>

<?php include 'templates/footer.php'; ?>

