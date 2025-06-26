<?php
session_start();
// Cek role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

function sshExec($host, $port, $user, $password, $command) {
    if (!function_exists("ssh2_connect")) return false;
    $conn = @ssh2_connect($host, $port);
    if (!$conn || !@ssh2_auth_password($conn, $user, $password)) return false;
    $stream = ssh2_exec($conn, $command);
    stream_set_blocking($stream, true);
    return trim(stream_get_contents($stream));
}

function checkXrayWebSocket($host, $port = 443, $path = '/trojan-ws') {
    $host = str_replace(["https://", "http://"], "", $host);
    $fp = @fsockopen("ssl://$host", $port, $errno, $errstr, 3);
    if (!$fp) return false;
    $key = base64_encode(random_bytes(16));
    $headers = "GET $path HTTP/1.1\r\n"
             . "Host: $host\r\n"
             . "Upgrade: websocket\r\n"
             . "Connection: Upgrade\r\n"
             . "Sec-WebSocket-Key: $key\r\n"
             . "Sec-WebSocket-Version: 13\r\n\r\n";
    fwrite($fp, $headers);
    $response = fread($fp, 1500);
    fclose($fp);
    return strpos($response, "101 Switching Protocols") !== false;
}

function getPingMs($host) {
    $ping = shell_exec("ping -c1 -W1 $host 2>/dev/null");
    if (preg_match('/time=([0-9.]+) ms/', $ping, $match)) {
        return (float) $match[1];
    }
    return false;
}

$servers = [
    'RW-MARD'     => ['ip' => '203.194.113.140', 'ssh_user' => 'root', 'ssh_port' => 22],
    'SGDO-MARD1'   => ['ip' => '152.42.182.187',  'ssh_user' => 'root', 'ssh_port' => 22],
    'SGDO-2DEV'    => ['ip' => '178.128.60.185',  'ssh_user' => 'root', 'ssh_port' => 22]
];

if (isset($_POST['password'])) {
    $_SESSION['vps_pass'] = $_POST['password'];
}

$password = $_SESSION['vps_pass'] ?? null;
?>
<?php include '../templates/header.php'; ?>
<!DOCTYPE html>
<html lang="en" class="bg-gray-900 text-white">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="5">
    <title>Monitoring VPS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="p-6 min-h-screen">
    <h1 class="text-3xl font-bold text-green-400 mb-6 text-center">✅ Monitoring 3 VPS</h1>

    <?php if (!$password): ?>
        <form method="post" class="max-w-md mx-auto bg-gray-800 p-6 rounded-lg shadow-lg">
            <label class="block text-white mb-2">Masukkan Password VPS:</label>
            <input type="password" name="password" class="w-full mb-4 p-2 rounded bg-gray-700 text-white" required>
            <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 px-4 py-2 rounded">Lanjut</button>
        </form>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($servers as $name => $srv): ?>
                <div class="bg-gray-800 rounded-xl p-4 shadow">
                    <h2 class="text-xl font-semibold text-blue-400 text-center mb-4"><?= $name ?></h2>
                    <div class="text-sm font-mono bg-black text-green-400 p-4 rounded-lg whitespace-pre-wrap">
<?php
$pingMs = getPingMs("google.com");

if ($pingMs === false) {
    echo "Status VPS      : 🔴 Offline\n";
    continue;
}

if ($pingMs < 150) {
    $pingColor = 'green-400';
} elseif ($pingMs < 1000) {
    $pingColor = 'yellow-300';
} else {
    $pingColor = 'red-500';
}
$pingText = "{$pingMs}ms";

$ok = sshExec($srv['ip'], $srv['ssh_port'], $srv['ssh_user'], $password, "echo OK");
if ($ok !== "OK") {
    echo "Status VPS      : 🔴 Autentikasi gagal\n";
    continue;
}

$os        = sshExec($srv['ip'], $srv['ssh_port'], $srv['ssh_user'], $password, "grep PRETTY_NAME /etc/os-release | cut -d= -f2 | tr -d '\"'");
$uptime    = sshExec($srv['ip'], $srv['ssh_port'], $srv['ssh_user'], $password, "uptime -p");
$ip        = sshExec($srv['ip'], $srv['ssh_port'], $srv['ssh_user'], $password, "curl -s ifconfig.me");
$country   = sshExec($srv['ip'], $srv['ssh_port'], $srv['ssh_user'], $password, "curl -s ipinfo.io/\$ip/country");
$domain    = sshExec($srv['ip'], $srv['ssh_port'], $srv['ssh_user'], $password, "hostname -f");
$domaincf  = sshExec($srv['ip'], $srv['ssh_port'], $srv['ssh_user'], $password, "cat /etc/xray/domain");
$cpu       = sshExec($srv['ip'], $srv['ssh_port'], $srv['ssh_user'], $password, "cat /proc/loadavg | awk '{print \$1\", \"\$2\", \"\$3}'");
$ram       = sshExec($srv['ip'], $srv['ssh_port'], $srv['ssh_user'], $password, "free -m | awk '/Mem/ {printf \"%dMB / %dMB\", \$3, \$2}'");
$disk      = sshExec($srv['ip'], $srv['ssh_port'], $srv['ssh_user'], $password, "df -h / | awk 'NR==2 {print \$3\" / \"\$2\" (\"\$5\")\"}'");
$bandwidth = sshExec($srv['ip'], $srv['ssh_port'], $srv['ssh_user'], $password, "vnstat --oneline | awk -F';' '{printf \"DL: %s UL: %s TOTAL: %s/s\", \$10, \$11, \$9}'");

$xrayStatus = checkXrayWebSocket($domaincf) ? "🟢 Online" : "🔴 Offline";

$labels = [
    "Status VPS"   => "🟢 Online ($pingText)",
    "OS"           => $os,
    "Uptime"       => $uptime,
    "Public IP"    => $ip,
    "Country"      => $country,
    "Domain VPS"   => $domain,
    "Domain Xray"  => $domaincf,
    "Xray Status"  => $xrayStatus,
    "CPU"          => $cpu,
    "RAM"          => $ram,
    "Disk"         => $disk,
    "Bandwidth"    => $bandwidth
];

$maxLen = max(array_map('strlen', array_keys($labels)));
foreach ($labels as $k => $v) {
    echo str_pad($k, $maxLen) . " : " . $v . "\n";
}
?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</body>
</html>
<?php include '../templates/footer.php'; ?>
