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
    "rw-mard" => ["ip" => "203.194.113.140", "config" => "/etc/xray/config.json"]
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

<div class="max-w-7xl mx-auto px-4 py-10">
  <div class="text-center mb-10">
    <h1 class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-sky-400 to-blue-600">
      🌐 Statistik Akun Xray
    </h1>
    <p class="mt-2 text-gray-400">Data akun dari VPS <span class="font-semibold text-blue-300"><?= strtoupper(htmlspecialchars($selectedVps)) ?></span></p>
  </div>

  <div class="flex justify-center mb-10">
    <form method="GET" class="bg-gray-800 px-6 py-3 rounded-lg shadow-md">
      <label for="vps" class="text-white font-medium mr-3">Pilih VPS:</label>
      <select name="vps" id="vps" class="bg-gray-700 text-white px-4 py-2 rounded-md focus:ring-2 focus:ring-blue-500" onchange="this.form.submit()">
        <?php foreach ($vpsList as $name => $info): ?>
          <option value="<?= $name ?>" <?= $name === $selectedVps ? 'selected' : '' ?>>
            <?= strtoupper($name) ?> (<?= $info['ip'] ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mb-12">
    <?php foreach ($statistik as $proto => $akun): ?>
      <div class="bg-gradient-to-br from-gray-800 to-gray-900 p-6 rounded-2xl text-white text-center shadow hover:shadow-xl transition-shadow duration-300">
        <h2 class="text-2xl font-semibold tracking-wide"><?= strtoupper($proto) ?></h2>
        <p class="mt-2">Total: <span class="font-bold"><?= count($akun) ?></span></p>
        <p class="text-green-400">Aktif: <?= countStatus($akun, 'active') ?></p>
        <p class="text-yellow-400">Mau Expired: <?= countStatus($akun, 'expiring') ?></p>
        <p class="text-red-400">Expired: <?= countStatus($akun, 'expired') ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <?php foreach ($statistik as $proto => $akun): ?>
    <?php if (empty($akun)) continue; ?>
    <div class="bg-gray-900 rounded-2xl p-6 shadow-xl mb-10">
      <h2 class="text-2xl font-bold text-white mb-6 border-b border-gray-700 pb-2"><?= strtoupper($proto) ?> - Detail Akun</h2>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-800 text-sm">
          <thead class="bg-gray-800 text-gray-300">
            <tr>
              <th class="px-4 py-3 text-left">👤 Username</th>
              <th class="px-4 py-3 text-left">📅 Expired</th>
              <th class="px-4 py-3 text-left">📌 Status</th>
              <th class="px-4 py-3 text-left">🌐 Online</th>
            </tr>
          </thead>
          <tbody class="bg-gray-700 divide-y divide-gray-800 text-white">
            <?php foreach ($akun as $u): ?>
              <tr class="hover:bg-gray-600 transition">
                <td class="px-4 py-2"><?= htmlspecialchars($u['username']) ?></td>
                <td class="px-4 py-2"><?= $u['expired'] ?></td>
                <td class="px-4 py-2">
                  <?php
                    echo match($u['status']) {
                      'active'   => '<span class="inline-block px-2 py-1 text-green-400 bg-green-900 rounded-full text-xs">Aktif</span>',
                      'expiring' => '<span class="inline-block px-2 py-1 text-yellow-400 bg-yellow-900 rounded-full text-xs">Segera Expired</span>',
                      'expired'  => '<span class="inline-block px-2 py-1 text-red-400 bg-red-900 rounded-full text-xs">Expired</span>',
                    };
                  ?>
                </td>
                <td class="px-4 py-2">
                  <?= $u['online'] ? '<span class="text-green-300 font-medium">Online</span>' : '<span class="text-gray-400">Offline</span>' ?>
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

