<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
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

function checkXrayStatus($host, $port = 443) {
    $ch = curl_init("https://$host:$port");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $httpCode > 0;
}

$servers = [
    'RW-MARD1' => [
        'ip' => '203.194.113.140',
        'ssh_user' => 'root',
        'ssh_port' => 22
    ],
    'SGDO-MARD1' => [
        'ip' => '143.198.202.86',
        'ssh_user' => 'root',
        'ssh_port' => 22
    ],
    'SGDO-2DEV' => [
        'ip' => '178.128.60.185',
        'ssh_user' => 'root',
        'ssh_port' => 22
    ]
];

$password = $_POST['password'] ?? null;
?>
<!DOCTYPE html>
<html lang="en" class="bg-gray-900 text-white">
<head>
    <meta charset="UTF-8">
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
$ok = sshExec($srv['ip'], $srv['ssh_port'], $srv['ssh_user'], $password, "echo OK");
if ($ok !== "OK") {
    echo "Status VPS    : ❌ Autentikasi gagal.\n";
    continue;
}

$os        = sshExec($srv['ip'], $srv['ssh_port'], $srv['ssh_user'], $password, "grep PRETTY_NAME /etc/os-release | cut -d= -f2 | tr -d '\"'");
$uptime    = sshExec($srv['ip'], $srv['ssh_port'], $srv['ssh_user'], $password, "uptime -p");
$ip        = sshExec($srv['ip'], $srv['ssh_port'], $srv['ssh_user'], $password, "curl -s ifconfig.me");
$country   = sshExec($srv['ip'], $srv['ssh_port'], $srv['ssh_user'], $password, "curl -s ipinfo.io/\$ip/country");
$domain    = sshExec($srv['ip'], $srv['ssh_port'], $srv['ssh_user'], $password, "hostname -f");
$domaincf  = sshExec($srv['ip'], $srv['ssh_port'], $srv['ssh_user'], $password, "cat /etc/xray/domain");
$xrayStat  = checkXrayStatus($domaincf) ? '🟢 Online' : '🔴 Offline';

$labels = [
    "Status VPS"   => "🟢 Online",
    "OS"           => $os,
    "Uptime"       => $uptime,
    "Public IP"    => $ip,
    "Country"      => $country,
    "Domain VPS"   => $domain,
    "Domain Xray"  => $domaincf,
    "Xray Status"  => $xrayStat
];

// Format agar titik dua lurus
$maxLen = max(array_map('strlen', array_keys($labels)));
foreach ($labels as $key => $value) {
    printf("%-{$maxLen}s : %s\n", $key, $value);
}
?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</body>
</html>

