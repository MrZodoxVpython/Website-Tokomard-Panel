<?php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Daftar IP VPS kamu + nama VPS + user SSH
$vpsList = [
    'rw-mard' => ['ip' => '203.194.113.140', 'user' => 'root'],
    'sgdo-mard1' => ['ip' => '143.198.202.86', 'user' => 'root'],
    'sgdo-2dev' => ['ip' => '178.128.60.185', 'user' => 'root'],
];

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$configPath = $vpsMap[$vps] ?? '/etc/xray/config.json';

if (isset($_GET['action']) && isset($_GET['user']) && isset($_GET['proto'])) {
    $action = $_GET['action'];
    $user = $_GET['user'];
    $proto = $_GET['proto'];

    $lines = file($configPath);
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

                // Berhenti jika menemukan akun berikutnya
                if (preg_match('/^\s*(###|#&|#!|#\$)\s+/', $jsonLine)) break;

                // Proses VMess & VLESS
                if (in_array($proto, ['vmess', 'vless']) && preg_match('/"id"\s*:\s*"(.*?)"/', $jsonLine)) {
                    if ($action === 'stop') {
                        if (!preg_match('/^##LOCK##/', trim($lines[$j - 1]))) {
                            $uuid = preg_replace('/.*"id"\s*:\s*"(.*?)".*/', '$1', $jsonLine);
                            $lines[$j] = preg_replace('/"id"\s*:\s*"(.*?)"/', '"id": "locked"', $jsonLine);
                            array_splice($lines, $j, 0, ["##LOCK##$uuid"]);
                            $updated = true;
                        }
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
                            if (preg_match('/^\s*(###|#&|#!|#\$)\s+/', $lockLine)) break;
                        }
                    }
                }

                // Proses Trojan & Shadowsocks
                if (in_array($proto, ['trojan', 'shadowsocks']) && preg_match('/"password"\s*:\s*"(.*?)"/', $jsonLine)) {
                    if ($action === 'stop') {
                        if (!preg_match('/^##LOCK##/', trim($lines[$j - 1]))) {
                            $password = preg_replace('/.*"password"\s*:\s*"(.*?)".*/', '$1', $jsonLine);
                            $lines[$j] = preg_replace('/"password"\s*:\s*"(.*?)"/', '"password": "locked"', $jsonLine);

                            // ❗❗❗ Tambahkan baris kunci secara terpisah (baris baru)
                            array_splice($lines, $j, 0, ["##LOCK##$password"]);
                            $updated = true;
                        }
                    } elseif ($action === 'start') {
                        for ($k = $j - 1; $k >= 0; $k--) {
                            $lockLine = trim($lines[$k]);
                            if (preg_match('/^##LOCK##(.+)$/', $lockLine, $m)) {
                                $realPass = $m[1];
                                if (strpos($jsonLine, '"password": "locked"') !== false) {
                                    $lines[$j] = preg_replace('/"password"\s*:\s*"locked"/', '"password": "' . $realPass . '"', $jsonLine);
                                    array_splice($lines, $k, 1); // Hapus ##LOCK## baris
                                    $updated = true;
                                }
                                break;
                            }
                            if (preg_match('/^\s*(###|#&|#!|#\$)\s+/', $lockLine)) break;
                        }
                    }
                }
            }
        }
    }
}


            if ($updated) {
        file_put_contents($configPath, implode("\n", array_map('rtrim', $lines)) . "\n");
	shell_exec('sudo /usr/local/bin/restart-xray.sh');
    }

    header("Location: kelola-akun.php");
    exit;
}


function generateUUID() {
    return trim(shell_exec('cat /proc/sys/kernel/random/uuid'));
}

function calculateExpiredDate($input) {
    if (preg_match('/^\d+$/', $input)) {
        return date('Y-m-d', strtotime("+$input days"));
    }
    return $input;
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

$username = trim($_POST['username'] ?? '');
$expired = trim($_POST['expired'] ?? '');
$protokol = trim($_POST['protokol'] ?? '');
$key = trim($_POST['key'] ?? '');

    // 🔽 Langkah No. 2: Eksekusi via SSH
    if (isset($vpsList[$vps])) {
        $vpsData = $vpsList[$vps];
        $vpsIp = $vpsData['ip'];
        $vpsUser = $vpsData['user'];

        $usernameSafe = escapeshellarg($username);
        $expiredSafe  = escapeshellarg($expired);
        $protokolSafe = escapeshellarg($protokol);
        $keySafe      = escapeshellarg($key);

        $sshCmd = "ssh -o StrictHostKeyChecking=no $vpsUser@$vpsIp 'php /root/tambah-akun.php $usernameSafe $expiredSafe $protokolSafe $keySafe'";
        $output = shell_exec($sshCmd);

        echo "<pre class='bg-gray-900 text-green-300 p-4 rounded'>$output</pre>";
        echo "<a href='kelola-akun.php' class='mt-4 inline-block bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded'>➕ Tambah Akun Lagi</a>";
        return;
    } else {
        echo "<p class='text-red-400'>❌ VPS tidak dikenali.</p>";
        return;
    }
}

if (!$key) {
    $key = generateUUID();
}
$expired = calculateExpiredDate($expired);
$proses = ($_SERVER['REQUEST_METHOD'] === 'POST' && $username && $expired && $protokol);

$vps = trim($_POST['vps'] ?? '');
$vpsMap = [
    'rw-mard' => '/etc/xray-rw/config.json',
    'sgdo-mard1' => '/etc/xray-sgdo/config.json',
    'sgdo-2dev' => '/etc/xray-dev/config.json'
];

$configPath = $vpsMap[$vps] ?? '/etc/xray/config.json';

include 'templates/header.php';
?>

<div class="max-w-5xl mx-auto mt-10 bg-gray-800 p-6 rounded-xl shadow-md text-white">
<?php if ($proses): ?>
    <?php


    if (akunSudahAda($username, $expired, $configPath)) {
        echo "<p class='text-yellow-400'>⚠ Akun sudah ada. Tidak ditambahkan ulang.</p>";
    } else {
        $tagMap = [
            'vmess' => ['vmess', 'vmessgrpc'],
            'vless' => ['vless', 'vlessgrpc'],
            'trojan' => ['trojanws', 'trojangrpc'],
            'shadowsocks' => ['ssws', 'ssgrpc']
        ];

        $commentPrefix = '';
        $jsonEntry = '';
        $tags = $tagMap[$protokol] ?? [];

        switch ($protokol) {
            case 'vmess':
                $commentPrefix = '###';
                $jsonEntry = "},{\"id\": \"$key\", \"alterId\": 0, \"email\": \"$username\"";
                break;
            case 'vless':
                $commentPrefix = '#&';
                $jsonEntry = "},{\"id\": \"$key\", \"email\": \"$username\"";
                break;
            case 'trojan':
                $commentPrefix = '#!';
                $jsonEntry = "},{\"password\": \"$key\", \"email\": \"$username\"";
                break;
            case 'shadowsocks':
                $commentPrefix = '#$';
                $jsonEntry = "},{\"password\": \"$key\", \"method\": \"aes-128-gcm\", \"email\": \"$username\"";
                break;
            default:
                echo "<p class='text-red-400'>❌ Protokol tidak dikenali.</p>";
                exit;
        }
        switch ($vps) {
    case 'rw-mard1':
        shell_exec("ssh root@IP_RW_MARD1 'systemctl restart xray'");
        break;
    case 'sgdo-mard1':
        shell_exec("ssh root@IP_SGDO_MARD1 'systemctl restart xray'");
        break;
    case 'sgdo-2dev':
        shell_exec("ssh root@IP_SGDO_2DEV 'systemctl restart xray'");
        break;
}
        

        // Backup config sebelum edit
        copy($configPath, $configPath . '.bak-' . date('YmdHis'));

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

        $suksesSemua = true;
        foreach ($tags as $tag) {
            $commentLine = "$commentPrefix $username $expired";
            if (!insertIntoTag($configPath, $tag, $commentLine, $jsonEntry)) {
                $suksesSemua = false;
            }
        }

        if ($suksesSemua) {
            echo "<h2 class='text-xl font-bold mb-4'>✅ Akun Berhasil Ditambahkan</h2>";
                $domain = trim(shell_exec('cat /etc/xray/domain'));
                $tls = "443";
                $ntls = "80";
                $path = "/trojan-ws";
                $servicename = "trojan-grpc";

                $trojanlink  = "trojan://$key@$domain:$tls?path=$path&security=tls&type=ws#$username";
                $trojanlink2 = "trojan://$key@$domain:$ntls?path=$path&security=none&type=ws#$username";
                $trojanlink1 = "trojan://$key@$domain:$tls?mode=gun&security=tls&type=grpc&serviceName=$servicename#$username";

                echo '<pre style="background-color: #1e1e2e; color: #cdd6f4; padding: 1em; border-radius: 10px; overflow-x: auto">';
                echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                echo "           TROJAN ACCOUNT           \n";
                echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                echo "Remarks        : $username\n";
                echo "Host/IP        : $domain\n";
                echo "Wildcard       : (bug.com).$domain\n";
                echo "Port TLS       : $tls\n";
                echo "Port none TLS  : $ntls\n";
                echo "Port gRPC      : $tls\n";
                echo "Key            : $key\n";
                echo "Path           : $path\n";
                echo "ServiceName    : $servicename\n";
                echo "Expired On     : $expired\n";
                echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                echo "Link TLS       : $trojanlink\n";
                echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                echo "Link none TLS  : $trojanlink2\n";
                echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                echo "Link gRPC      : $trojanlink1\n";
                echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                echo '</pre>';
        } else {
            echo "<p class='text-yellow-400'>⚠ Akun berhasil ditambahkan.</p>";
        }
    }
    ?>
    <a href="kelola-akun.php" class="inline-block mt-4 bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded">➕ Tambah Akun Lagi</a>
<?php else: ?>
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
	   <option value="rw-mard1">RW-MARD</option>
  	   <option value="sgdo-mard1">SGDO-MARD1</option>
  	   <option value="sgdo-2dev">SGDO-2DEV</option>
	  </select>
      </div>
      <div>
        <label class="block mb-1">Protokol</label>
        <select name="protokol" class="w-full p-2 bg-gray-700 rounded" required>
          <option value="trojan">Trojan</option>
          <option value="vmess">Vmess</option>
          <option value="vless">Vless</option>
          <option value="shadowsocks">Shadowsocks</option>
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
<h3 class="text-xl font-semibold mb-4">Daftar Akun Terdaftar</h3>

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
if (file_exists($configPath)) {
    $lines = file($configPath);
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
            echo "<a href='edit-akun.php?user=$user&proto=$proto' class='text-yellow-400 hover:underline'>Edit</a> | ";
            echo "<a href='hapus-akun.php?user=$user&proto=$proto' class='text-red-400 hover:underline' onclick=\"return confirm('Yakin hapus akun $user?')\">Hapus</a> | ";
            if ($akunStatus === 'INACTIVE') {
                echo "<a href='?action=start&user=$user&proto=$proto' class='text-green-400 hover:underline'>Start</a>";
            } else {
                echo "<a href='?action=stop&user=$user&proto=$proto' class='text-red-400 hover:underline'>Stop</a>";
            }
            echo "</td></tr>";
            $no++;
        }
    }

    if ($no === 1) {
        echo "<tr><td colspan='5' class='text-center py-4 text-gray-400'>⚠ Tidak ditemukan akun dalam config.json.</td></tr>";
    }
} else {
    echo "<tr><td colspan='5' class='text-center py-4 text-red-400'>❌ config.json tidak ditemukan!</td></tr>";
}
?>
    </tbody>
  </table>
</div>


<?php include 'templates/footer.php'; ?>

