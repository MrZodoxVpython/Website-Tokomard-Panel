<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
echo "✅ Script dijalankan<br>";

session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

// Daftar IP VPS + user SSH
$vpsList = [
    'rw-mard'     => ['ip' => '203.194.113.140', 'user' => 'root'],
    'sgdo-mard1'  => ['ip' => '143.198.202.86', 'user' => 'root'],
    'sgdo-2dev'   => ['ip' => '178.128.60.185', 'user' => 'root'],
];

// Path config untuk masing-masing VPS
$vpsMap = [
    'rw-mard'     => '/etc/xray/config.json',
    'sgdo-mard1'  => '/etc/xray/config.json',
    'sgdo-2dev'   => '/etc/xray/config.json',
];

// Ambil input
$vps      = trim($_POST['vps'] ?? $_GET['vps'] ?? '');
$username = trim($_POST['username'] ?? '');
$expired  = trim($_POST['expired'] ?? '');
$protokol = trim($_POST['protokol'] ?? '');
$key      = trim($_POST['key'] ?? '');

// Tentukan path config
$configPath = $vpsMap[$vps] ?? '/etc/xray/config.json';

// Jika config tidak ada, hentikan
if (!file_exists($configPath)) {
    die("❌ Config file tidak ditemukan: $configPath");
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
if (isset($_GET['action'], $_GET['user'], $_GET['proto'])) {
    $action = $_GET['action'];
    $user   = $_GET['user'];
    $proto  = $_GET['proto'];

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
        file_put_contents($configPath, implode("\n", array_map('rtrim', $lines)) . "\n");
        shell_exec('sudo /usr/local/bin/restart-xray.sh');
    }

    header("Location: kelola-akun.php");
    exit;
}

// Generate UUID kalau kosong
if (!$key) {
    $key = generateUUID();
}
$expired = calculateExpiredDate($expired);

// Eksekusi tambah akun via SSH
if ($proses && isset($vpsList[$vps])) {
    $vpsData = $vpsList[$vps];
    $vpsIp   = $vpsData['ip'];
    $vpsUser = $vpsData['user'];

    $usernameSafe = escapeshellarg($username);
    $expiredSafe  = escapeshellarg($expired);
    $protokolSafe = escapeshellarg($protokol);
    $keySafe      = escapeshellarg($key);

    $sshCmd = "ssh -o StrictHostKeyChecking=no $vpsUser@$vpsIp 'php /root/tambah-akun.php $usernameSafe $expiredSafe $protokolSafe $keySafe'";
    $output = shell_exec($sshCmd);

    echo "<pre class='bg-gray-900 text-green-300 p-4 rounded'>$output</pre>";
    echo "<a href='kelola-akun.php' class='mt-4 inline-block bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded'>➕ Tambah Akun Lagi</a>";
    exit;
} elseif ($proses) {
    echo "<p class='text-red-400'>❌ VPS tidak dikenali.</p>";
    return;
}


$suksesSemua = true;

$tagMap = [
    'vmess' => ['vmess', 'vmessgrpc'],
    'vless' => ['vless', 'vlessgrpc'],
    'trojan' => ['trojanws', 'trojangrpc'],
    'shadowsocks' => ['ssws', 'ssgrpc']
];

$tags = $tagMap[$protokol] ?? [];

$commentPrefix = '';
$jsonEntry = '';

switch ($protokol) {
    case 'vmess':
        $commentPrefix = '###';
        $jsonEntry = "{\"id\": \"$key\", \"alterId\": 0, \"email\": \"$username\"}";
        break;
    case 'vless':
        $commentPrefix = '#&';
        $jsonEntry = "{\"id\": \"$key\", \"email\": \"$username\"}";
        break;
    case 'trojan':
        $commentPrefix = '#!';
        $jsonEntry = "{\"password\": \"$key\", \"email\": \"$username\"}";
        break;
    case 'shadowsocks':
        $commentPrefix = '#$';
        $jsonEntry = "{\"password\": \"$key\", \"method\": \"aes-128-gcm\", \"email\": \"$username\"}";
        break;
    default:
        echo "<p class='text-red-400'>❌ Protokol tidak dikenali.</p>";
        exit;
}

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
    echo "<p class='text-yellow-400'>⚠ Akun berhasil ditambahkan sebagian.</p>";
}

// Tabel daftar akun
echo '<hr class="my-6 border-gray-600">';
echo '<h3 class="text-xl font-semibold mb-4">Daftar Akun Terdaftar</h3>';
echo '<div class="overflow-x-auto">';
echo '<table class="min-w-full text-sm text-left text-white border border-gray-600">';
echo '<thead class="bg-gray-700">';
echo '<tr>';
echo '<th class="py-2 px-3">No</th>';
echo '<th class="py-2 px-3">Nama Akun</th>';
echo '<th class="py-2 px-3">Protokol</th>';
echo '<th class="py-2 px-3">Expired</th>';
echo '<th class="py-2 px-3">Aksi</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

if (file_exists($configPath)) {
    $lines = file($configPath);
    $no = 1;
    $akunTertampil = [];

    foreach ($lines as $i => $line) {
        if (preg_match('/^\s*(###|#&|#!|#\$)\s+(\S+)\s+(\d{4}-\d{2}-\d{2})/', $line, $match)) {
            $prefix = $match[1];
            $user = $match[2];
            $date = $match[3];
            $proto = match ($prefix) {
                '###' => 'vmess',
                '#&'  => 'vless',
                '#!'  => 'trojan',
                '#$'  => 'shadowsocks',
                default => 'unknown'
            };

            $akunStatus = 'ACTIVE';
            for ($j = $i + 1; $j < count($lines); $j++) {
                $nextLine = trim($lines[$j]);
                if (preg_match('/^\s*(###|#&|#!|#\$)\s+/', $nextLine)) break;

                if (in_array($proto, ['vmess', 'vless']) && str_contains($nextLine, '"id": "locked"')) {
                    $akunStatus = 'INACTIVE'; break;
                }
                if (in_array($proto, ['trojan', 'shadowsocks']) && str_contains($nextLine, '"password": "locked"')) {
                    $akunStatus = 'INACTIVE'; break;
                }
            }

            $uniqueKey = $proto . '|' . $user;
            if (isset($akunTertampil[$uniqueKey])) continue;
            $akunTertampil[$uniqueKey] = true;

            echo "<tr class='border-t border-gray-600'>";
            echo "<td class='py-2 px-3'>{$no}</td>";
            echo "<td class='py-2 px-3'>{$user}</td>";
            echo "<td class='py-2 px-3'>{$proto}</td>";
            echo "<td class='py-2 px-3'>{$date}</td>";
            echo "<td class='py-2 px-3'>";
            echo "<a href='edit-akun.php?user={$user}&proto={$proto}' class='text-yellow-400 hover:underline'>Edit</a> | ";
            echo "<a href='hapus-akun.php?user={$user}&proto={$proto}' class='text-red-400 hover:underline' onclick=\"return confirm('Yakin hapus akun $user?')\">Hapus</a> | ";
            echo $akunStatus === 'INACTIVE'
                ? "<a href='?action=start&user={$user}&proto={$proto}' class='text-green-400 hover:underline'>Start</a>"
                : "<a href='?action=stop&user={$user}&proto={$proto}' class='text-red-400 hover:underline'>Stop</a>";
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

