<?php
session_start();
$__start_time = microtime(true);
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

// Langsung definisikan daftar VPS di sini
$listVPS = [
    'SGDO-2DEV' => '127.0.0.1',
    'SGDO-MARD1' => '178.128.60.185',
];

$selectedVps = $_GET['vps'] ?? array_key_first($listVPS);
$selectedIp = $listVPS[$selectedVps] ?? '127.0.0.1';
$isLocal = in_array($selectedIp, ['127.0.0.1', 'localhost']);

function ambilFileRemote($ip, $path) {
    return shell_exec("ssh -o StrictHostKeyChecking=no root@$ip 'cat $path'");
}

$configPath = "/etc/xray/config.json";
$logPath = "/var/log/xray/access.log";

$data = $isLocal ? @file_get_contents($configPath) : ambilFileRemote($selectedIp, $configPath);

if (!$data) {
    echo "<p style='color:red;'>❌ Gagal membaca file config.json dari VPS $selectedVps</p>";
    exit;
}

$lines = preg_grep('/^\s*#/', explode("\n", $data));
$protocolCounts = ['vmess' => 0, 'vless' => 0, 'trojan' => 0, 'ss' => 0];
$expiredUsers = [];
$expiringSoonUsers = [];
$seenUsers = [];
$usersByProtocol = ['vmess' => [], 'vless' => [], 'trojan' => [], 'ss' => []];
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
            case '#&':  $protocol = 'vless'; break;
            case '#!':  $protocol = 'trojan'; break;
            case '#$':  $protocol = 'ss'; break;
            default:    $protocol = 'unknown';
        }

        $protocolCounts[$protocol]++;
        $usersByProtocol[$protocol][] = ['username' => $username, 'expired' => $expDate];

        if ($expDate < $today) {
            $expiredUsers[] = ['username' => $username, 'protocol' => strtoupper($protocol), 'expired' => $expDate];
        } elseif ($expDate <= $sevenDaysLater) {
            $expiringSoonUsers[] = ['username' => $username, 'protocol' => strtoupper($protocol), 'expired' => $expDate];
        }
    }
}

$logContent = $isLocal
    ? shell_exec("tail -n 500 $logPath")
    : shell_exec("ssh -o StrictHostKeyChecking=no root@$selectedIp 'tail -n 500 $logPath'");

$logLines = explode("\n", $logContent);
$activeUsers = [];
$startTime = date('Y/m/d H:i:s', strtotime('-1 minute'));
$usernames = array_keys($seenUsers);

foreach ($logLines as $logLine) {
    if (preg_match('/^(\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2}).*email: (\S+)/', $logLine, $matches)) {
        $logTime = $matches[1];
        $logUser = $matches[2];
        if ($logTime > $startTime && in_array($logUser, $usernames)) {
            $activeUsers[$logUser] = true;
        }
    }
}

include 'templates/header.php';
include 'templates/statistik-tampilan.php';
include 'templates/footer.php';

