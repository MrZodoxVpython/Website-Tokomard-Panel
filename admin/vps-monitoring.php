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
        'ssh_port' => 22,
        'ssh_pass' => 'your_password1'
    ],
    'SGDO-MARD1' => [
        'ip' => '143.198.202.86',
        'ssh_user' => 'root',
        'ssh_port' => 22,
        'ssh_pass' => 'your_password2'
    ],
    'SGDO-2DEV' => [
        'ip' => '203.194.113.140',
        'ssh_user' => 'root',
        'ssh_port' => 22,
        'ssh_pass' => 'your_password3'
    ]
];

?>
<!DOCTYPE html>
<html lang="en" class="bg-gray-900 text-white">
<head>
    <meta charset="UTF-8">
    <title>Monitoring 3 VPS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        pre code {
            white-space: pre;
        }
    </style>
</head>
<body class="p-6">
    <h1 class="text-3xl font-bold text-green-400 mb-6 text-center">✅ Monitoring 3 VPS</h1>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($servers as $name => $server):
            $conn = sshExec(
                $server['ip'], $server['ssh_port'], $server['ssh_user'], $server['ssh_pass'],
                "echo OK"
            );
            $isOnline = $conn === "OK";
            if ($isOnline) {
                $os = sshExec($server['ip'], $server['ssh_port'], $server['ssh_user'], $server['ssh_pass'], "grep PRETTY_NAME /etc/os-release | cut -d= -f2 | tr -d \"\"");
                $uptime = sshExec($server['ip'], $server['ssh_port'], $server['ssh_user'], $server['ssh_pass'], "uptime -p");
                $ip = sshExec($server['ip'], $server['ssh_port'], $server['ssh_user'], $server['ssh_pass'], "curl -s ifconfig.me");
                $country = sshExec($server['ip'], $server['ssh_port'], $server['ssh_user'], $server['ssh_pass'], "curl -s ipinfo.io/\$ip/country");
                $domain = sshExec($server['ip'], $server['ssh_port'], $server['ssh_user'], $server['ssh_pass'], "hostname -f");
                $domaincf = sshExec($server['ip'], $server['ssh_port'], $server['ssh_user'], $server['ssh_pass'], "cat /etc/xray/domain");
                $xrayStatus = checkXrayStatus($domaincf) ? '🟢 Online' : '🔴 Offline';
            }
        ?>
        <div class="bg-gray-800 p-4 rounded-lg shadow">
            <h2 class="text-xl font-semibold text-blue-300 mb-2 text-center"><?= $name ?></h2>
            <div class="text-sm font-mono bg-black rounded-lg p-4 overflow-x-auto text-green-400">
                <?php if ($isOnline): ?>
<pre><code>
Status        : 🟢 Online
OS            : <?= $os ?>
Uptime        : <?= $uptime ?>
Public IP     : <?= $ip ?>
Country       : <?= $country ?>
Domain VPS    : <?= $domain ?>
Domain Xray   : <?= $domaincf ?>
Xray Status   : <?= $xrayStatus ?>
</code></pre>
                <?php else: ?>
                    <p class="text-red-400 font-bold">❌ Autentikasi gagal.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>

