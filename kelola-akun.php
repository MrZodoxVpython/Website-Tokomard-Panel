<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
#echo "✅ No error found!<br>";

session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Daftar IP VPS + user SSH
$vpsList = [
    'sgdo-2dev'   => ['ip' => '206.189.42.23', 'user' => 'root'],
    'sgdo-mard1'  => ['ip' => '152.42.182.187', 'user' => 'root'],
    'rw-mard'     => ['ip' => '203.194.113.140', 'user' => 'root'],
];

// Path config untuk masing-masing VPS
$vpsMap = [
    'sgdo-2dev'   => '/etc/xray/config.json',
    'sgdo-mard1'  => '/etc/xray/config.json',
    'rw-mard'     => '/etc/xray/config.json',
];

// Ambil dan validasi $vps sekali saja
$vpsInput = trim($_POST['vps'] ?? $_GET['vps'] ?? '');
$vps = ($vpsInput === '' || !isset($vpsList[$vpsInput]) || !isset($vpsMap[$vpsInput])) ? 'sgdo-2dev' : $vpsInput;

// Ambil input lain
$username = trim($_POST['username'] ?? '');
$expired  = trim($_POST['expired'] ?? '');
$protokol = trim($_POST['protokol'] ?? '');
$key      = trim($_POST['key'] ?? '');

// Tentukan path config
$configPath = $vpsMap[$vps] ?? '/etc/xray/config.json';

// Jika config tidak ada, hentikan
$isRemote = ($vps !== 'sgdo-2dev'); // sgdo-2dev = lokal

if ($isRemote) {
    $sshUser = $vpsList[$vps]['user'];
    $sshIp   = $vpsList[$vps]['ip'];
    $remotePath = $vpsMap[$vps];
    $command = "ssh -o StrictHostKeyChecking=no $sshUser@$sshIp 'cat $remotePath'";
    $configContent = shell_exec($command);
    
    if (!$configContent) {
        die("❌ Gagal membaca config dari VPS: $vps ($sshIp)");
    }
    
    $lines = explode("\n", $configContent);
} else {
    // Lokal
    if (!file_exists($configPath)) {
        die("❌ Config file tidak ditemukan: $configPath");
    }
    $lines = file($configPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
}

// Tentukan apakah form disubmit
$proses = ($_SERVER['REQUEST_METHOD'] === 'POST' && $username && $expired && $protokol);

// Fungsi
function generateUUID() {
    return trim(shell_exec('cat /proc/sys/kernel/random/uuid'));
}
function calculateExpiredDate($input) {
    return preg_match('/^\d+$/', $input) ? date('Y-m-d', strtotime("+$input days")) : $input;
}
function akunSudahAda($username, $expired, $configPath) {
    $lines = file($configPath);
    foreach ($lines as $line) {
        if (preg_match('/^\s*(###|#&|#!|#\$)\s+' . preg_quote($username, '/') . '\s+' . preg_quote($expired, '/') . '\s*$/', trim($line))) {
            return true;
        }
    }
    return false;
}
function insertIntoTag($configPath, $tag, $commentLine, $jsonLine) {
    $lines = file($configPath);
    $inserted = false;
    foreach ($lines as $i => $line) {
        if (strpos($line, "#$tag") !== false) {
            array_splice($lines, $i + 1, 0, [$commentLine . "\n", $jsonLine . "\n"]);
            file_put_contents($configPath, implode("\n", array_map('rtrim', $lines)) . "\n");
            $inserted = true;
            break;
        }
    }
    return $inserted;
}

// Handle start/stop akun

if (isset($_GET['action'], $_GET['user'], $_GET['proto'], $_GET['vps'])) {
    $action = $_GET['action'];
    $user   = $_GET['user'];
    $proto  = $_GET['proto'];
    $vps    = $_GET['vps'];

    // VPS Map dan Lokasi config
    $vpsList = [
        'sgdo-2dev' => ['user' => 'root', 'ip' => '127.0.0.1'],
        'sgdo-mard1' => ['user' => 'root', 'ip' => '152.42.182.187'],
        'rw-mard' => ['user' => 'root', 'ip' => '203.194.113.140'],
    ];
    $vpsMap = [
        'sgdo-2dev' => '/etc/xray/config.json',
        'sgdo-mard1' => '/etc/xray/config.json',
        'rw-mard' => '/etc/xray/config.json',
    ];

    $sshUser = $vpsList[$vps]['user'];
    $sshIp   = $vpsList[$vps]['ip'];
    $configPath = $vpsMap[$vps];
    $isRemote = $vps !== 'sgdo-2dev';

    // Ambil config
    if ($isRemote) {
        $configContent = shell_exec("ssh -o StrictHostKeyChecking=no $sshUser@$sshIp 'cat $configPath'");
        if (!$configContent) {
            die("❌ Gagal ambil config.json dari VPS $vps");
        }
        $lines = explode("\n", $configContent);
    } else {
        if (!file_exists($configPath)) {
            die("❌ Config file tidak ditemukan di lokal");
        }
        $lines = file($configPath);
    }

    $updated = false;

    for ($i = 0; $i < count($lines); $i++) {
        $line = trim($lines[$i]);
        if (preg_match('/^\s*(###|#&|#!|#\$)\s+(\S+)\s+(\d{4}-\d{2}-\d{2})/', $line, $match)) {
            $prefix = $match[1];
            $foundUser = $match[2];

            $protoMap = [
                '###' => 'vmess',
                '#&'  => 'vless',
                '#!'  => 'trojan',
                '#$'  => 'shadowsocks'
            ];
            $prefixProto = $protoMap[$prefix] ?? '';

            if ($foundUser === $user && $prefixProto === $proto) {
                for ($j = $i + 1; $j < count($lines); $j++) {
                    $jsonLine = trim($lines[$j]);
                    if (preg_match('/^\s*(###|#&|#!|#\$)\s+/', $jsonLine)) break;

                    // Lock/unlock ID
                    if (in_array($proto, ['vmess', 'vless']) && preg_match('/"id"\s*:\s*"(.*?)"/', $jsonLine)) {
                        if ($action === 'stop' && !preg_match('/^##LOCK##/', trim($lines[$j - 1]))) {
                            $uuid = preg_replace('/.*"id"\s*:\s*"(.*?)".*/', '$1', $jsonLine);
                            $lines[$j] = preg_replace('/"id"\s*:\s*"(.*?)"/', '"id": "locked"', $jsonLine);
                            array_splice($lines, $j, 0, ["##LOCK##$uuid"]);
                            $updated = true;
                        } elseif ($action === 'start') {
                            for ($k = $j - 1; $k >= 0; $k--) {
                                $lockLine = trim($lines[$k]);
                                if (preg_match('/^##LOCK##(.+)$/', $lockLine, $m)) {
                                    $realId = $m[1];
                                    if (strpos($jsonLine, '"id": "locked"') !== false) {
                                        $lines[$j] = preg_replace('/"id"\s*:\s*"locked"/', '"id": "' . $realId . '"', $jsonLine);
                                        array_splice($lines, $k, 1);
                                        $updated = true;
                                    }
                                    break;
                                }
                            }
                        }
                    }

                    // Lock/unlock password
                    if (in_array($proto, ['trojan', 'shadowsocks']) && preg_match('/"password"\s*:\s*"(.*?)"/', $jsonLine)) {
                        if ($action === 'stop' && !preg_match('/^##LOCK##/', trim($lines[$j - 1]))) {
                            $password = preg_replace('/.*"password"\s*:\s*"(.*?)".*/', '$1', $jsonLine);
                            $lines[$j] = preg_replace('/"password"\s*:\s*"(.*?)"/', '"password": "locked"', $jsonLine);
                            array_splice($lines, $j, 0, ["##LOCK##$password"]);
                            $updated = true;
                        } elseif ($action === 'start') {
                            for ($k = $j - 1; $k >= 0; $k--) {
                                $lockLine = trim($lines[$k]);
                                if (preg_match('/^##LOCK##(.+)$/', $lockLine, $m)) {
                                    $realPass = $m[1];
                                    if (strpos($jsonLine, '"password": "locked"') !== false) {
                                        $lines[$j] = preg_replace('/"password"\s*:\s*"locked"/', '"password": "' . $realPass . '"', $jsonLine);
                                        array_splice($lines, $k, 1);
                                        $updated = true;
                                    }
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    if ($updated) {
        $newConfig = implode("\n", array_map('rtrim', $lines)) . "\n";

        if ($isRemote) {
            $tmpFile = "/tmp/tmp_config_" . uniqid() . ".json";
            file_put_contents($tmpFile, $newConfig);
            shell_exec("scp -o StrictHostKeyChecking=no $tmpFile $sshUser@$sshIp:$configPath");
            shell_exec("ssh -o StrictHostKeyChecking=no $sshUser@$sshIp 'systemctl restart xray'");
            unlink($tmpFile);
        } else {
            file_put_contents($configPath, $newConfig);
            shell_exec('systemctl restart xray');
        }
    }

    header("Location: kelola-akun.php?vps=" . urlencode($vps));
    exit;
}
// Generate UUID kalau kosong
if (!$key) {
    $key = generateUUID();
}
$expired = calculateExpiredDate($expired);

// Eksekusi tambah akun via SSH
// Eksekusi tambah akun (lokal atau remote)
if ($proses && isset($vpsList[$vps])) {
    $vpsData = $vpsList[$vps];
    $vpsIp   = $vpsData['ip'];
    $vpsUser = $vpsData['user'];

    $usernameSafe = escapeshellarg($username);
    $expiredSafe  = escapeshellarg($expired);
    $protokolSafe = escapeshellarg($protokol);
    $keySafe      = escapeshellarg($key);

    // Deteksi apakah VPS adalah lokal (sgdo-2dev = 178.128.60.185)
    if ($vpsIp === '178.128.60.185' || $vps === 'sgdo-2dev') {
        // Jalankan script secara lokal tanpa SSH
        $cmd = "php /etc/xray/tambah-akun.php $usernameSafe $expiredSafe $protokolSafe $keySafe";
    } else {
        // Jalankan script di VPS remote melalui SSH
        $cmd = "ssh -o StrictHostKeyChecking=no $vpsUser@$vpsIp 'php /root/tambah-akun.php $usernameSafe $expiredSafe $protokolSafe $keySafe'";
    }

    $output = shell_exec($cmd);
    $hasilTambahAkun = "<pre class='bg-gray-900 text-green-300 p-4 rounded whitespace-pre'>$output</pre>";
} elseif ($proses) {
    echo "<p class='text-red-400'>❌ VPS tidak dikenali.</p>";
    return;
}
// Tambahkan sebelum $suksesSemua = true;

// Sekarang tinggal tampilkan form HTML...
include 'templates/header.php';
// Form HTML dan daftar akun lanjutan...
?>
<?php if ($proses): ?>
    
    <?php
    // Tampilkan hasil penambahan akun dari shell_exec (jika ada)
    if (!empty($hasilTambahAkun)) {
        echo $hasilTambahAkun;
    }
    ?>
    <!-- Tampilkan tombol "Tambah Akun Lagi" hanya setelah berhasil tambah akun -->
    <a href="kelola-akun.php" class="inline-block mt-4 bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded">
        ➕ Tambah Akun Lagi
    </a>
<?php else: ?>
<head>
  <meta charset="UTF-8">
   <title>Admin Tools</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<div class="max-w-7xl mx-auto p-4">
  <h1 class="text-center text-3xl font-bold mb-6 text-blue-400">Administrator kontrol</h1>

  <!-- Menu Admin -->
  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mb-8">
    <a href="admin/shell-access.php" class="bg-fuchsia-600 hover:bg-fuchsia-700 text-white px-4 py-3 rounded-xl text-center shadow">
      ⏳ Shell Access M
    </a>
    <a href="admin/vps-monitoring.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-xl text-center shadow">
      🖥 VPS Monitoring M
    </a>
    <a href="/cek-status-server-tokomard.php" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-3 rounded-xl text-center shadow">
      🧠 Cek Server M
    </a>
    <a href="admin/backup-restore.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-xl text-center shadow">
      🧑💻 Backup & Restore M
    </a>
    <a href="admin/statistik-vps.php" class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-3 rounded-xl text-center shadow">
      📊 Statistik Bandwith M
    </a>
    <a href="admin/statistik.php" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-3 rounded-xl text-center shadow">
      📈 Full Cek User Xray  M
    </a>
    <a href="admin/list-user.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-3 rounded-xl text-center shadow">
      📋 Registered Users M
    </a>
      <a href="admin/log-akses.php" class="bg-rose-600 hover:bg-rose-700 text-white px-4 py-3 rounded-xl text-center shadow">
      🧾 Log Akses User
    </a>
    <a href="admin/aktifitas-admin.php" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-3 rounded-xl text-center shadow">
      ⚙ Aktivitas Admin
    </a>
    <a href="admin/error-report.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-3 rounded-xl text-center shadow">
      ❗ Laporan Error
    </a>
    <a href="admin/statistik-vps.php" class="bg-violet-700 hover:bg-gray-800 text-white px-4 py-3 rounded-xl text-center shadow">
      📊 Stats VPS
    </a>
    <a href="admin/pengaturan.php" class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-3 rounded-xl text-center shadow">
      🔧 Pengaturan Sistem
    </a>
  </div>

    <!-- Form tambah akun baru -->
    <h2 class="text-xl font-bold mb-4">Tambah Akun Baru</h2>
    <form action="" method="POST" class="grid gap-4">
        <div>
            <label class="block mb-1">Username</label>
            <input type="text" name="username" class="w-full p-2 bg-gray-700 rounded" required>
        </div>
        <div>
            <label class="block mb-1">Expired (tanggal atau jumlah hari)</label>
            <input type="text" name="expired" placeholder="2025-07-01 atau 30" class="w-full p-2 bg-gray-700 rounded" required>
        </div>
        <!-- Dropdown Pilihan VPS -->
        <div>
            <label class="block mb-1">Pilih VPS</label>
            <select name="vps" class="w-full p-2 bg-gray-700 rounded" required>
                <option value="sgdo-2dev">SGDO-2DEV</option>
                <option value="sgdo-mard1">SGDO-MARD1</option>
                <option value="rw-mard">RW-MARD</option>
            </select>
        </div>
        <div>
            <label class="block mb-1">Protokol</label>
            <select name="protokol" class="w-full p-2 bg-gray-700 rounded" required>
                <option value="trojan">Trojan</option>
                <option value="vmess">Vmess</option>
                <option value="vless">Vless</option>
                <option value="shadowsocks">Shadowsocks</option>
                <option value="ssh">SSH</option>
            </select>
        </div>
        <div>
            <label class="block mb-1">UUID / Password (otomatis jika kosong)</label>
            <input type="text" name="key" class="w-full p-2 bg-gray-700 rounded">
        </div>
        <div>
            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 py-2 rounded">Simpan Akun</button>
        </div>
    </form>
<?php endif; ?>
<hr class="my-6 border-gray-600">
<form method="GET" class="mb-4">
  <label class="text-xl block mb-1 font-semibold">Tampilkan daftar akun client dari VPS:</label>
  <select name="vps" onchange="this.form.submit()" class="w-full p-2 bg-gray-700 rounded max-w-xs">
    <?php foreach ($vpsList as $key => $val): ?>
      <option value="<?= $key ?>" <?= ($vps === $key ? 'selected' : '') ?>>
        <?= strtoupper($key) ?>
      </option>
    <?php endforeach; ?>
  </select>
</form>

<!-- Tambahkan pembungkus scroll horizontal -->
<div class="overflow-x-auto">
  <table class="min-w-full text-sm text-left text-white border border-gray-600">
    <thead class="bg-gray-700">
      <tr>
        <th class="py-2 px-3">No</th>
        <th class="py-2 px-3">Nama Akun</th>
        <th class="py-2 px-3">Protokol</th>
        <th class="py-2 px-3">Expired</th>
        <th class="py-2 px-3">Aksi</th>
      </tr>
    </thead>
    <tbody>
<?php
$vps = $_GET['vps'] ?? 'sgdo-2dev';
if (!isset($vpsList[$vps]) || !isset($vpsMap[$vps])) {
    $vps = 'sgdo-2dev';
}

switch ($vps) {
    case 'sgdo-2dev':
        $configPath = '/etc/xray/config.json';
        break;
    case 'sgdo-mard1':
        $configPath = '/etc/xray/config.json';
        break;
    case 'rw-mard':
    default:
        $configPath = '/etc/xray/config.json';
        break;
}

//if (file_exists($configPath)) {
 //   $lines = file($configPath);
    $isRemote = ($vps !== 'sgdo-2dev'); // lokal hanya sgdo-2dev

if ($isRemote) {
    $sshUser = $vpsList[$vps]['user'];
    $sshIp   = $vpsList[$vps]['ip'];
    $remotePath = $vpsMap[$vps];
    $command = "ssh -o StrictHostKeyChecking=no $sshUser@$sshIp 'cat $remotePath'";
    $configContent = shell_exec($command);

    if (!$configContent) {
        die("❌ Gagal membaca config dari VPS: $vps ($sshIp)");
    }

    $lines = explode("\n", $configContent);
} else {
    if (!file_exists($configPath)) {
        die("❌ Config file tidak ditemukan: $configPath");
    }

    $lines = file($configPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
}

    $no = 1;
    $akunTertampil = [];

    foreach ($lines as $i => $line) {
        if (preg_match('/^\s*(###|#&|#!|#\$)\s+(\S+)\s+(\d{4}-\d{2}-\d{2})/', $line, $match)) {
            $prefix = $match[1];
            $user = $match[2];
            $date = $match[3];
            switch ($prefix) {
                case '###': $proto = 'vmess'; break;
                case '#&':  $proto = 'vless'; break;
                case '#!':  $proto = 'trojan'; break;
                case '#$':  $proto = 'shadowsocks'; break;
                default: $proto = 'unknown'; break;
            }

            $akunStatus = 'ACTIVE';
            for ($j = $i + 1; $j < count($lines); $j++) {
                $nextLine = trim($lines[$j]);

                if (preg_match('/^\s*(###|#&|#!|#\$)\s+/', $nextLine)) {
                    break;
                }

                if (in_array($proto, ['vmess', 'vless']) && preg_match('/"id"\s*:\s*"locked"/', $nextLine)) {
                    $akunStatus = 'INACTIVE';
                    break;
                }
                if (in_array($proto, ['trojan', 'shadowsocks']) && preg_match('/"password"\s*:\s*"locked"/', $nextLine)) {
                    $akunStatus = 'INACTIVE';
                    break;
                }
            }

            $uniqueKey = $proto . '|' . $user;
            if (isset($akunTertampil[$uniqueKey])) continue;
            $akunTertampil[$uniqueKey] = true;

            echo "<tr class='border-t border-gray-600'>";
            echo "<td class='py-2 px-3'>$no</td>";
            echo "<td class='py-2 px-3'>$user</td>";
            echo "<td class='py-2 px-3'>$proto</td>";
            echo "<td class='py-2 px-3'>$date</td>";
            echo "<td class='py-2 px-3'>";
            echo "<a href='edit-akun.php?user=$user&proto=$proto&vps=$vps' class='text-yellow-400 hover:underline'>Edit</a> | ";

            echo "<a href='hapus-akun.php?user=$user&proto=$proto&vps=$vps' class='text-red-400 hover:underline' onclick=\"return confirm('Yakin hapus akun $user?')\">Hapus</a> | ";
            if ($akunStatus === 'INACTIVE') {
                echo "<a href='?action=start&user=$user&proto=$proto&vps=$vps' class='text-green-400 hover:underline'>Start</a>";
            } else {
                echo "<a href='?action=stop&user=$user&proto=$proto&vps=$vps' class='text-red-400 hover:underline'>Stop</a>";
            }
            echo "</td></tr>";
            $no++;
        }
    }
$akunList = [];

// Ambil akun SSH dari file
$sshFile = '/var/www/html/data/akun-ssh.txt';
if (file_exists($sshFile)) {
    $sshLines = file($sshFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($sshLines as $line) {
        if (preg_match('/^#ssh\s+(\S+)\s+(\S+)/', $line, $m)) {
            $akunList[] = [
                'username' => $m[1],
                'protokol' => 'ssh',
                'expired'  => $m[2]
            ];
        }
    }
}
// Tampilkan akun-akun SSH ke tabel
foreach ($akunList as $akun) {
    echo "<tr class='border-t border-gray-600'>";
    echo "<td class='py-2 px-3'>$no</td>";
    echo "<td class='py-2 px-3'>{$akun['username']}</td>";
    echo "<td class='py-2 px-3'>{$akun['protokol']}</td>";
    echo "<td class='py-2 px-3'>{$akun['expired']}</td>";
    echo "<td class='py-2 px-3'>";
    echo "<a href='hapus-akun.php?user={$akun['username']}&proto=ssh' class='text-red-400 hover:underline' onclick=\"return confirm('Yakin hapus akun {$akun['username']}?')\">Hapus</a>";
    echo "</td></tr>";
    $no++;
}

    if ($no === 1) {
        echo "<tr><td colspan='5' class='text-center py-4 text-gray-400'>⚠ Tidak ditemukan akun dalam config.json.</td></tr>";
    }
?>
    </tbody>
  </table>
</div>


<?php include 'templates/footer.php'; ?>

