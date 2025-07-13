<?php
session_start();
$__start_time = microtime(true);
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$vpsList = [
    "sgdo-2dev" => ["ip" => "178.128.60.185", "config" => "/etc/xray/config.json"],
    "sgdo-mard1" => ["ip" => "152.42.182.187", "config" => "/etc/xray/config.json"],
    "rw-mard" => ["ip" => "203.194.113.140", "config" => "/etc/xray/config.json"]
];

$selectedVPS = $_GET['vps'] ?? 'sgdo-2dev';
$sshPrefix = '';
$configPath = $vpsList[$selectedVPS]['config'];
$logPath = '/var/log/xray/access.log'; // Bisa disesuaikan per-VPS jika berbeda

$dataList = [];

if ($selectedVPS === 'all') {
    foreach ($vpsList as $key => $info) {
        $path = $info['config'];
        if ($key === 'sgdo-2dev') {
            // Lokal
            if (file_exists($path)) {
                $content = file_get_contents($path);
                if ($content !== false) $dataList[] = $content;
            }
        } else {
            // Remote
            $ssh = 'ssh -o StrictHostKeyChecking=no -i /root/.ssh/id_rsa root@' . $info['ip'];
            $content = shell_exec("$ssh \"cat $path\"");
            if ($content) $dataList[] = $content;
        }
    }

    $data = implode("\n", $dataList);
} else {
    if ($selectedVPS === 'sgdo-2dev') {
        if (!file_exists($configPath)) {
            echo "<p style='color:red;'>❌ File config.json tidak ditemukan di VPS Lokal!</p>";
            exit;
        }
        $data = file_get_contents($configPath);
    } else {
        $sshPrefix = 'ssh -o StrictHostKeyChecking=no -i /root/.ssh/id_rsa root@' . $vpsList[$selectedVPS]['ip'];
        $data = shell_exec("$sshPrefix \"cat $configPath\"");
        if (!$data) {
            echo "<p style='color:red;'>❌ Gagal mengambil config.json dari VPS $selectedVPS!</p>";
            exit;
        }
    }
}

//$configPath = '/etc/xray/config.json';
//$logPath = '/var/log/xray/access.log';

//if (!file_exists($configPath)) {
//    echo "<p style='color:red;'>❌ File config.json tidak ditemukan!</p>";
//    exit;
//}
//$data = file_get_contents($configPath);
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

$logLines = [];

if ($selectedVPS === 'all') {
    foreach ($vpsList as $key => $info) {
        if ($key === 'sgdo-2dev') {
            if (file_exists($logPath)) {
                $output = shell_exec("tail -n 500 $logPath");
                if ($output) {
                    $logLines = array_merge($logLines, explode("\n", $output));
                }
            }
        } else {
            $ssh = 'ssh -o StrictHostKeyChecking=no -i /root/.ssh/id_rsa root@' . $info['ip'];
            $output = shell_exec("$ssh \"tail -n 500 $logPath\"");
            if ($output) {
                $logLines = array_merge($logLines, explode("\n", $output));
            }
        }
    }
} else {
    if ($selectedVPS === 'sgdo-2dev') {
        if (file_exists($logPath)) {
            $logLines = explode("\n", shell_exec("tail -n 500 $logPath"));
        }
    } else {
        $logLines = explode("\n", shell_exec("$sshPrefix \"tail -n 500 $logPath\""));
    }
}

// Proses log
$uniqueUsers = [];
foreach ($logLines as $logLine) {
    if (preg_match('/^(\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2}).*email: (\S+)/', $logLine, $matches)) {
        $logTime = $matches[1];
        $logUser = $matches[2];
        if ($logTime > $startTime && in_array($logUser, $usernames)) {
            $uniqueUsers[$logUser] = true;
            $activeUsers[$logUser] = true;
        }
    }
}

include 'templates/header.php';
error_log("⏱ Setelah include header.php: " . round(microtime(true) - $__start_time, 3) . "s");

?>

<!-- HTML CODE BELOW: Tailwind CSS style -->
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

<!-- DropDown pilih VPS -->
<div class="flex flex-wrap items-center justify-between mb-6">
  <h2 class="text-xl font-bold text-white mb-2 sm:mb-0">Pilih VPS:</h2>
  <form method="get" class="w-full sm:w-auto">
    <select name="vps" onchange="this.form.submit()" class="bg-gray-800 text-white border border-gray-600 rounded-xl px-4 py-2">
  <option value="all" <?= $selectedVPS === 'all' ? 'selected' : '' ?>>ALL VPS</option>
  <?php foreach ($vpsList as $key => $info): ?>
    <option value="<?= $key ?>" <?= $key === $selectedVPS ? 'selected' : '' ?>><?= strtoupper($key) ?> (<?= $info['ip'] ?>)</option>
  <?php endforeach; ?>
    </select>
  </form>
</div>

  <!-- Tabel Daftar Akun Aktif -->
  <div class="bg-green-800 rounded-xl p-4 sm:p-6 shadow mb-10">
    <h2 class="text-xl font-bold text-white mb-4">Daftar Akun Aktif (Online &lt; 1 Menit)</h2>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-green-700">
	<thead class="bg-green-700">
	  <tr>
	    <th class="px-4 py-2 text-left text-sm font-semibold text-white">No</th>
	    <th class="px-4 py-2 text-left text-sm font-semibold text-white">Username</th>
	  </tr>
	</thead>
	<tbody class="bg-green-600 divide-y divide-green-700">
	  <?php
	  $no = 1;
	  foreach (array_keys($activeUsers) as $username): ?>
	  <tr>
	    <td class="px-4 py-2 text-sm text-white"><?php echo $no++; ?></td>
	    <td class="px-4 py-2 text-sm text-white"><?php echo htmlspecialchars($username); ?></td>
	  </tr>
	  <?php endforeach; ?>
          <?php if (empty($activeUsers)): ?>
          <tr><td class="px-4 py-2 text-sm text-white text-center">Tidak ada akun aktif saat ini.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Tabel Daftar Akun per Protokol -->
  <?php foreach ($usersByProtocol as $proto => $entries): ?>
  <div class="bg-gray-800 rounded-xl p-4 sm:p-6 shadow mb-10">
    <h2 class="text-xl font-bold text-white mb-4">
  	Daftar Akun <?php echo $proto === 'ss' ? 'Shadowsocks' : strtoupper($proto); ?>
    </h2>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-700">
        <thead class="bg-gray-700">
          <tr>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white">Username</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white">Tanggal Expired</th>
          </tr>
        </thead>
        <tbody class="bg-gray-600 divide-y divide-gray-700">
          <?php foreach ($entries as $entry): ?>
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

  <!-- Expiring Soon -->
  <div class="bg-gradient-to-br from-yellow-900 to-yellow-700 rounded-xl p-4 sm:p-6 shadow mb-10">
    <h2 class="text-xl font-bold text-white mb-4">Daftar Akun Akan Expired (≤ 7 Hari)</h2>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-yellow-800">
        <thead class="bg-yellow-800">
          <tr>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white">Username</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white">Protokol</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white">Tanggal Expired</th>
          </tr>
        </thead>
        <tbody class="bg-yellow-700 divide-y divide-yellow-800">
          <?php foreach ($expiringSoonUsers as $user): ?>
          <tr>
            <td class="px-4 py-2 text-sm text-white"><?php echo htmlspecialchars($user['username']); ?></td>
            <td class="px-4 py-2 text-sm text-white"><?php echo htmlspecialchars($user['protocol']); ?></td>
            <td class="px-4 py-2 text-sm text-white"><?php echo htmlspecialchars($user['expired']); ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($expiringSoonUsers)): ?>
          <tr><td colspan="3" class="px-4 py-2 text-sm text-white text-center">Belum ada akun yang akan expired dalam 7 hari.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Expired Users -->
  <?php if (!empty($expiredUsers)): ?>
  <div class="bg-gradient-to-br from-red-900 to-red-700 rounded-xl p-4 sm:p-6 shadow mb-10">
    <h2 class="text-xl font-bold text-white mb-4">Daftar Akun Expired</h2>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-red-800">
        <thead class="bg-red-800">
          <tr>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white">Username</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white">Protokol</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-white">Tanggal Expired</th>
          </tr>
        </thead>
        <tbody class="bg-red-700 divide-y divide-red-800">
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
    <a href="dashboard.php" class="inline-block bg-gray-700 hover:bg-gray-800 text-white py-3 px-6 rounded-xl text-base sm:text-lg font-semibold transition">⬅ Kembali ke Dashboard</a>
  </div>
</div>

<?php include 'templates/footer.php'; ?>
