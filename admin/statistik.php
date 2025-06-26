<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
ob_start();
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$vpsList = [
    "sgdo-2dev" => ["ip" => "178.128.60.185", "config" => "/etc/xray/config.json"],
    "sgdo-mard1" => ["ip" => "152.42.182.187", "config" => "/etc/xray/config.json"],
    "tokopedia1" => ["ip" => "203.194.113.140", "config" => "/etc/xray/config.json"]
];

$selectedVps = $_GET['vps'] ?? 'sgdo-2dev';
$configPath = $vpsList[$selectedVps]['config'] ?? '';
$vpsIp = $vpsList[$selectedVps]['ip'] ?? '';

if ($selectedVps === 'sgdo-2dev') {
    if (!file_exists($configPath)) die("<p style='color:red;'>❌ File config.json tidak ditemukan!</p>");
    $data = file_get_contents($configPath);
} else {
    $sshCmd = "ssh -i /root/.ssh/id_rsa -o StrictHostKeyChecking=no root@$vpsIp 'cat $configPath' 2>/dev/null";
    $data = shell_exec($sshCmd);
    if (empty($data)) die("<p style='color:red;'>❌ Gagal ambil config.json dari VPS $selectedVps!</p>");
}

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

// Simpan daftar username unik per protokol
$uniqueUsernames = [
    'vmess' => [],
    'vless' => [],
    'trojan' => [],
    'shadowsocks' => []
];

foreach ($matches as $match) {
    [$full, $tag, $username, $expired] = $match;
    switch ($tag) {
        case '###': $proto = 'vmess'; break;
        case '#&':  $proto = 'vless'; break;
        case '#!':  $proto = 'trojan'; break;
        case '#$':  $proto = 'shadowsocks'; break;
        default:    continue 2;
    }

    // Skip jika username sudah pernah dimasukkan sebelumnya (anti duplikat)
    if (in_array($username, $uniqueUsernames[$proto])) {
        continue;
    }

    $uniqueUsernames[$proto][] = $username;

    $status = ($expired < $today) ? 'expired' : (($expired <= $sevenDaysLater) ? 'expiring' : 'active');
    $statistik[$proto][] = [
        'username' => $username,
        'expired' => $expired,
        'status' => $status,
        'online' => false
    ];
}

function countStatus($data, $status) {
    return count(array_filter($data, fn($x) => $x['status'] === $status));
}

include '../templates/header.php';
?>

<div class="container mx-auto px-4 py-6">
  <div class="text-center mb-6">
    <h1 class="text-3xl font-extrabold text-white">Statistik Akun Semua Protokol</h1>
    <p class="text-gray-400">Dari VPS: <span class="text-blue-300"><?= htmlspecialchars($selectedVps) ?></span></p>
  </div>

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
  </div>

  <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-10">
    <?php foreach ($statistik as $proto => $akun): ?>
      <div class="bg-gray-800 p-4 rounded-xl text-white text-center shadow">
        <h2 class="text-xl font-bold"><?= strtoupper($proto) ?></h2>
        <p>Total: <?= count($akun) ?></p>
        <p class="text-green-400">Aktif: <?= countStatus($akun, 'active') ?></p>
        <p class="text-yellow-400">Expiring: <?= countStatus($akun, 'expiring') ?></p>
        <p class="text-red-400">Expired: <?= countStatus($akun, 'expired') ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Tabel semua akun -->
  <?php foreach ($statistik as $proto => $akun): ?>
    <?php if (empty($akun)) continue; ?>
    <div class="bg-gray-900 rounded-xl p-6 shadow mb-10">
      <h2 class="text-xl font-bold text-white mb-4"><?= strtoupper($proto) ?> - Detail Akun</h2>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-700">
          <thead class="bg-gray-800 text-white">
            <tr>
              <th class="px-4 py-2">Username</th>
              <th class="px-4 py-2">Expired</th>
              <th class="px-4 py-2">Status</th>
              <th class="px-4 py-2">Online</th>
            </tr>
          </thead>
          <tbody class="bg-gray-700 divide-y divide-gray-800 text-white">
            <?php foreach ($akun as $u): ?>
              <tr>
                <td class="px-4 py-2"><?= htmlspecialchars($u['username']) ?></td>
                <td class="px-4 py-2"><?= $u['expired'] ?></td>
                <td class="px-4 py-2">
                  <?php
                  switch ($u['status']) {
                    case 'active': echo '<span class="text-green-400">Aktif</span>'; break;
                    case 'expiring': echo '<span class="text-yellow-300">Mau Expired</span>'; break;
                    case 'expired': echo '<span class="text-red-400">Expired</span>'; break;
                  }
                  ?>
                </td>
                <td class="px-4 py-2">
                  <?= $u['online'] ? '<span class="text-green-300">Online</span>' : '<span class="text-gray-400">Offline</span>' ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php include '../templates/footer.php'; ?>

