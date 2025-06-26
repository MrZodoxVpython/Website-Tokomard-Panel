<?php
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start(); // <- TAMBAH INI

session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

// VPS List
$vpsList = [
    "sgdo-2dev" => ["ip" => "178.128.60.185", "config" => "/etc/xray/config.json"],
    "sgdo-mard1" => ["ip" => "152.42.182.187", "config" => "/etc/xray/config.json"],
    "tokopedia1" => ["ip" => "203.194.113.140", "config" => "/etc/xray/config.json"]
];

$selectedVps = $_GET['vps'] ?? 'sgdo-2dev';
$configPath = $vpsList[$selectedVps]['config'] ?? '';
$vpsIp = $vpsList[$selectedVps]['ip'] ?? '';

// Ambil config.json tergantung VPS lokal atau remote
if ($selectedVps === 'sgdo-2dev') {
    // VPS lokal, baca langsung
    if (!file_exists($configPath)) {
        echo "<p style='color:red;'>❌ File config.json tidak ditemukan di VPS $selectedVps!</p>";
        exit;
    }
    $data = file_get_contents($configPath);
    if ($data === false) {
        echo "<p style='color:red;'>❌ Gagal membaca file config.json di VPS $selectedVps!</p>";
        exit;
    }
} else {
    // VPS remote, ambil via SSH
    $sshUser = 'root';
    $sshKeyPath = '/root/.ssh/id_rsa'; // pastikan key ini cocok
    $sshCmd = "ssh -i $sshKeyPath -o StrictHostKeyChecking=no $sshUser@$vpsIp 'cat $configPath' 2>/dev/null";
    $data = shell_exec($sshCmd);

    if (empty($data)) {
        echo "<p style='color:red;'>❌ Gagal mengambil config.json dari VPS $selectedVps ($vpsIp)!</p>";
        exit;
    }
}

$lines = preg_grep('/^\s*#/', explode("\n", $data));
$protocolCounts = ['vmess' => 0, 'vless' => 0, 'trojan' => 0, 'ss' => 0];
$usersByProtocol = ['vmess' => [], 'vless' => [], 'trojan' => [], 'ss' => []];
$expiredUsers = [];
$expiringSoonUsers = [];
$seenUsers = [];
$today = date('Y-m-d');
$sevenDaysLater = date('Y-m-d', strtotime('+7 days'));

foreach ($lines as $line) {
    $line = trim($line);
    if (preg_match('/^(###|#&|#!|#\$)\s+(\S+)\s+(\d{4}-\d{2}-\d{2})$/', $line, $match)) {
        [$all, $prefix, $username, $expDate] = $match;
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

include '../templates/header.php';
?>

<div class="container mx-auto px-4 py-6">
  <div class="text-center mb-6">
    <h1 class="text-3xl font-extrabold text-white">Statistik Akun VPN</h1>
    <p class="text-gray-400 mt-2 text-base">Menampilkan statistik akun berdasarkan VPS dan protokol.</p>
  </div>

  <!-- VPS Dropdown -->
  <div class="mb-6 text-center">
    <form method="GET">
      <label for="vpsSelect" class="text-white mr-2">Pilih VPS:</label>
      <select name="vps" id="vpsSelect" class="bg-gray-800 text-white px-4 py-2 rounded-lg" onchange="this.form.submit()">
        <?php foreach ($vpsList as $name => $info): ?>
          <option value="<?= $name ?>" <?= $name === $selectedVps ? 'selected' : '' ?>>
            <?= strtoupper($name) ?> (<?= $info['ip'] ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </form>
    <p class="text-sm text-gray-400 mt-2">IP VPS Terpilih: <?= htmlspecialchars($vpsIp) ?></p>
  </div>

  <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-10">
    <?php foreach ($protocolCounts as $proto => $count): ?>
      <div class="bg-gray-800 p-4 rounded-xl shadow text-white text-center">
        <h2 class="text-xl font-bold"><?= strtoupper($proto) ?></h2>
        <p class="text-3xl mt-2"><?= $count ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="bg-yellow-800 rounded-xl p-6 shadow mb-10">
    <h2 class="text-xl font-bold text-white mb-4">Akan Expired (≤ 7 Hari)</h2>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-yellow-700">
        <thead class="bg-yellow-700 text-white">
          <tr>
            <th class="px-4 py-2 text-left text-sm font-semibold">Username</th>
            <th class="px-4 py-2 text-left text-sm font-semibold">Protokol</th>
            <th class="px-4 py-2 text-left text-sm font-semibold">Expired</th>
          </tr>
        </thead>
        <tbody class="bg-yellow-600 divide-y divide-yellow-700 text-white">
          <?php foreach ($expiringSoonUsers as $u): ?>
          <tr>
            <td class="px-4 py-2"><?= htmlspecialchars($u['username']) ?></td>
            <td class="px-4 py-2"><?= $u['protocol'] ?></td>
            <td class="px-4 py-2"><?= $u['expired'] ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($expiringSoonUsers)): ?>
          <tr><td colspan="3" class="text-center py-3">Tidak ada akun akan expired.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if (!empty($expiredUsers)): ?>
  <div class="bg-red-800 rounded-xl p-6 shadow mb-10">
    <h2 class="text-xl font-bold text-white mb-4">Akun Expired</h2>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-red-700">
        <thead class="bg-red-700 text-white">
          <tr>
            <th class="px-4 py-2 text-left text-sm font-semibold">Username</th>
            <th class="px-4 py-2 text-left text-sm font-semibold">Protokol</th>
            <th class="px-4 py-2 text-left text-sm font-semibold">Expired</th>
          </tr>
        </thead>
        <tbody class="bg-red-600 divide-y divide-red-700 text-white">
          <?php foreach ($expiredUsers as $u): ?>
          <tr>
            <td class="px-4 py-2"><?= htmlspecialchars($u['username']) ?></td>
            <td class="px-4 py-2"><?= $u['protocol'] ?></td>
            <td class="px-4 py-2"><?= $u['expired'] ?></td>
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

<?php include '../templates/footer.php'; ?>

