<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
ob_start();

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

// Ambil config.json tergantung lokal/remote
if ($selectedVps === 'sgdo-2dev') {
    if (!file_exists($configPath)) {
        die("<p style='color:red;'>❌ File config.json tidak ditemukan!</p>");
    }
    $data = file_get_contents($configPath);
} else {
    $sshUser = 'root';
    $sshKeyPath = '/root/.ssh/id_rsa';
    $sshCmd = "ssh -i $sshKeyPath -o StrictHostKeyChecking=no $sshUser@$vpsIp 'cat $configPath' 2>/dev/null";
    $data = shell_exec($sshCmd);
    if (empty($data)) {
        die("<p style='color:red;'>❌ Gagal mengambil config.json dari VPS $selectedVps!</p>");
    }
}

// Proses regex semua protokol sekaligus
$regex = '/^(###|#&|#!|#\$)\s+(\S+)\s+(\d{4}-\d{2}-\d{2})$/m';
preg_match_all($regex, $data, $matches, PREG_SET_ORDER);

$today = date('Y-m-d');
$sevenDaysLater = date('Y-m-d', strtotime('+7 days'));

$statistik = [
    'vmess' => [],
    'vless' => [],
    'trojan' => [],
    'shadowsocks' => []
];

foreach ($matches as $match) {
    $tag = $match[1];
    $username = $match[2];
    $expired = $match[3];

    // Tentukan protokol
    switch ($tag) {
        case '###': $proto = 'vmess'; break;
        case '#&':  $proto = 'vless'; break;
        case '#!':  $proto = 'trojan'; break;
        case '#$':  $proto = 'shadowsocks'; break;
        default:    $proto = 'unknown';
    }

    $status = ($expired < $today) ? 'expired' : (($expired <= $sevenDaysLater) ? 'expiring' : 'active');
    $statistik[$proto][] = ['username' => $username, 'expired' => $expired, 'status' => $status];
}

function countStatus($data, $status) {
    return count(array_filter($data, fn($x) => $x['status'] === $status));
}

include '../templates/header.php';
?>

<div class="container mx-auto px-4 py-6">
  <div class="text-center mb-6">
    <h1 class="text-3xl font-extrabold text-white">Statistik Semua Protokol</h1>
    <p class="text-gray-400 mt-2 text-base">Menampilkan akun dari semua protokol (VMess, VLESS, Trojan, Shadowsocks).</p>
  </div>

  <!-- Dropdown VPS -->
  <div class="mb-6 text-center">
    <form method="GET">
      <label class="text-white mr-2">Pilih VPS:</label>
      <select name="vps" class="bg-gray-800 text-white px-4 py-2 rounded-lg" onchange="this.form.submit()">
        <?php foreach ($vpsList as $name => $info): ?>
          <option value="<?= $name ?>" <?= $name === $selectedVps ? 'selected' : '' ?>>
            <?= strtoupper($name) ?> (<?= $info['ip'] ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </form>
    <p class="text-sm text-gray-400 mt-2">IP VPS: <?= htmlspecialchars($vpsIp) ?></p>
  </div>

  <!-- Statistik per protokol -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-10">
    <?php foreach ($statistik as $proto => $akun): ?>
      <div class="bg-gray-800 p-4 rounded-xl shadow text-white text-center">
        <h2 class="text-xl font-bold uppercase"><?= $proto ?></h2>
        <p>Total: <span class="font-semibold"><?= count($akun) ?></span></p>
        <p>Aktif: <span class="text-green-400"><?= countStatus($akun, 'active') ?></span></p>
        <p>Expiring: <span class="text-yellow-400"><?= countStatus($akun, 'expiring') ?></span></p>
        <p>Expired: <span class="text-red-400"><?= countStatus($akun, 'expired') ?></span></p>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Detail akun expiring -->
  <?php foreach ($statistik as $proto => $akun): ?>
    <?php
      $expiring = array_filter($akun, fn($x) => $x['status'] === 'expiring');
      if (empty($expiring)) continue;
    ?>
    <div class="bg-yellow-800 rounded-xl p-6 shadow mb-10">
      <h2 class="text-xl font-bold text-white mb-4 uppercase">Expiring (≤ 7 Hari): <?= $proto ?></h2>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-yellow-700">
          <thead class="bg-yellow-700 text-white">
            <tr>
              <th class="px-4 py-2">Username</th>
              <th class="px-4 py-2">Expired</th>
            </tr>
          </thead>
          <tbody class="bg-yellow-600 divide-y divide-yellow-700 text-white">
            <?php foreach ($expiring as $u): ?>
              <tr>
                <td class="px-4 py-2"><?= htmlspecialchars($u['username']) ?></td>
                <td class="px-4 py-2"><?= $u['expired'] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endforeach; ?>

  <!-- Detail akun expired -->
  <?php foreach ($statistik as $proto => $akun): ?>
    <?php
      $expired = array_filter($akun, fn($x) => $x['status'] === 'expired');
      if (empty($expired)) continue;
    ?>
    <div class="bg-red-800 rounded-xl p-6 shadow mb-10">
      <h2 class="text-xl font-bold text-white mb-4 uppercase">EXPIRED: <?= $proto ?></h2>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-red-700">
          <thead class="bg-red-700 text-white">
            <tr>
              <th class="px-4 py-2">Username</th>
              <th class="px-4 py-2">Expired</th>
            </tr>
          </thead>
          <tbody class="bg-red-600 divide-y divide-red-700 text-white">
            <?php foreach ($expired as $u): ?>
              <tr>
                <td class="px-4 py-2"><?= htmlspecialchars($u['username']) ?></td>
                <td class="px-4 py-2"><?= $u['expired'] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php include '../templates/footer.php'; ?>

