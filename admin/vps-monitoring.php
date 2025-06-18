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
        'ip' => '203.194.113.140',
        'ssh_user' => 'root',
        'ssh_port' => 22
    ]
];

$password = $_POST['password'] ?? null;
$submit = isset($_POST['submit']);
?>
<!DOCTYPE html>
<html lang="en" class="bg-gray-900 text-white">
<head>
    <meta charset="UTF-8">
    <title>Monitoring 3 VPS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="p-6">
    <h1 class="text-3xl font-bold text-green-400 mb-6 text-center">✅ Monitoring 3 VPS</h1>
    <form method="post" class="max-w-md mx-auto mb-8">
        <label class="block text-white mb-2 text-center">Masukkan Password root untuk semua VPS:</label>
        <input type="password" name="password" class="w-full mb-4 p-2 rounded bg-gray-700 text-white" required>
        <button type="submit" name="submit" class="bg-blue-500 px-4 py-2 rounded hover:bg-blue-600 w-full">Terapkan & Lihat Status Semua VPS</button>
    </form>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($servers as $name => $server): ?>
            <div class="bg-gray-800 p-4 rounded-lg shadow">
                <h2 class="text-xl font-semibold text-blue-300 mb-4 text-center"><?= $name ?></h2>
                <?php
                if ($submit) {
                    $conn = sshExec($server['ip'], $server['ssh_port'], $server['ssh_user'], $password, "echo OK");
                    $isOnline = $conn === "OK";
                    if ($isOnline) {
                        $os = sshExec($server['ip'], $server['ssh_port'], $server['ssh_user'], $password, "grep PRETTY_NAME /etc/os-release | cut -d= -f2 | tr -d \"\"");
                        $uptime = sshExec($server['ip'], $server['ssh_port'], $server['ssh_user'], $password, "uptime -p");
                        $ip = sshExec($server['ip'], $server['ssh_port'], $server['ssh_user'], $password, "curl -s ifconfig.me");
                        $country = sshExec($server['ip'], $server['ssh_port'], $server['ssh_user'], $password, "curl -s ipinfo.io/\$ip/country");
                        $domain = sshExec($server['ip'], $server['ssh_port'], $server['ssh_user'], $password, "hostname -f");
                        $domaincf = sshExec($server['ip'], $server['ssh_port'], $server['ssh_user'], $password, "cat /etc/xray/domain");
                        $xrayStatus = checkXrayStatus($domaincf) ? '🟢 Online' : '🔴 Offline';
                ?>
                        <div class="text-sm font-mono bg-black rounded-lg p-4 overflow-x-auto text-green-400">
<pre><code>
Status VPS   : 🟢 Online
OS           : <?= $os ?>
Uptime       : <?= $uptime ?>
Public IP    : <?= $ip ?>
Country      : <?= $country ?>
Domain VPS   : <?= $domain ?>
Domain Xray  : <?= $domaincf ?>
Xray Status  : <?= $xrayStatus ?>
</code></pre>
                        </div>
                <?php
                    } else {
                        echo '<p class="text-red-400 font-bold text-center">❌ Autentikasi gagal.</p>';
                    }
                }
                ?>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>

