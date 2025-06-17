<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

// CPU usage
function getCpuUsage() {
    $load = sys_getloadavg();
    return round($load[0], 2);
}

// RAM
function getRamUsage() {
    $mem = explode("\n", trim(shell_exec("free -m")));
    $memInfo = preg_split('/\s+/', $mem[1]);
    return [$memInfo[2], $memInfo[1]];
}

// Disk
function getDiskUsage() {
    $disk = shell_exec("df -h / | awk 'NR==2{print $3, $2, $5}'");
    return explode(" ", trim($disk));
}

// Uptime
function getUptime() {
    return trim(shell_exec("uptime -p"));
}

// Bandwidth
function getBandwidth() {
    $output = shell_exec("vnstat --oneline");
    $parts = explode(";", $output);
    return [
        "rx" => $parts[9] ?? "N/A",
        "tx" => $parts[10] ?? "N/A",
        "total" => $parts[11] ?? "N/A"
    ];
}

// OS Info
function getOSInfo() {
    return trim(shell_exec("grep PRETTY_NAME /etc/os-release | cut -d '=' -f2 | tr -d '\"'"));
}

// Public IP
function getPublicIP() {
    return trim(shell_exec("curl -s ifconfig.me"));
}

// Country Info via IP (requires geoiplookup or fallback)
function getCountry() {
    $ip = getPublicIP();
    $country = trim(shell_exec("curl -s ipinfo.io/$ip/country"));
    return $country ?: "Unknown";
}

// Domain (domain vps)
function getDomain() {
    $domain = trim(shell_exec("hostname -f"));
    return $domain ?: "Unavailable";
}

// Domain (domain xray cloudflare)
function getDomaincf() {
    $domain = trim(shell_exec("cat /etc/xray/domain"));
    return $domain ?: "Unavailable";
}

// Date & Time
function getDateTimeNow() {
    return date("D, d M Y H:i:s");
}

// Ambil semua data
$cpu = getCpuUsage();
[$ramUsed, $ramTotal] = getRamUsage();
[$diskUsed, $diskTotal, $diskPercent] = getDiskUsage();
$uptime = getUptime();
$bandwidth = getBandwidth();
$os = getOSInfo();
$ip = getPublicIP();
$country = getCountry();
$domain = getDomain();
$domaincf = getDomaincf();
$datetime = getDateTimeNow();
?>

<!DOCTYPE html>
<html lang="en" class="bg-gray-900 text-white">
<head>
    <meta charset="UTF-8">
    <title>✅ VPS Monitoring</title>
    <script>
        setTimeout(() => location.reload(), 10000); // auto-refresh
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="p-8 font-mono">
    <div class="max-w-2xl mx-auto bg-gray-800 p-6 rounded-2xl shadow-xl">
        <h1 class="text-2xl font-bold text-green-400 mb-4">✅ VPS Monitoring</h1>

        <div class="space-y-4">
            <div class="bg-gray-700 p-4 rounded-lg">
                <h2 class="text-blue-400 font-semibold">CPU Load</h2>
                <p><?= $cpu ?>%</p>
            </div>

            <div class="bg-gray-700 p-4 rounded-lg">
                <h2 class="text-blue-400 font-semibold">RAM Usage</h2>
                <p><?= $ramUsed ?> MB / <?= $ramTotal ?> MB</p>
            </div>

            <div class="bg-gray-700 p-4 rounded-lg">
                <h2 class="text-blue-400 font-semibold">Disk Usage</h2>
                <p><?= $diskUsed ?> / <?= $diskTotal ?> (<?= $diskPercent ?>)</p>
            </div>

            <div class="bg-gray-700 p-4 rounded-lg">
                <h2 class="text-blue-400 font-semibold">Uptime</h2>
                <p><?= $uptime ?></p>
            </div>

            <div class="bg-gray-700 p-4 rounded-lg">
                <h2 class="text-blue-400 font-semibold">Bandwidth (vnstat)</h2>
                <p>Download: <?= $bandwidth['rx'] ?> | Upload: <?= $bandwidth['tx'] ?> | Total: <?= $bandwidth['total'] ?></p>
            </div>
        </div>

        <!-- Informasi Tambahan -->
        <div class="bg-gray-900 text-sm mt-8 border-t border-gray-700 pt-4">
<pre class="bg-black rounded-xl p-4 text-green-400 overflow-x-auto">
┌────────────────────────────────────────────────────────┐
│  OS          : <?= $os ?>

│  UPTIME      : <?= $uptime ?>

│  PUBLIC IP   : <?= $ip ?>

│  COUNTRY     : <?= $country ?>

│  DOMAIN VPS  : <?= $domain ?>

│  DOMAIN XRAY : <?= $domaincf ?>

│  DATE & TIME : <?= $datetime ?>

└────────────────────────────────────────────────────────┘
</pre>
        </div>
    </div>
</body>
</html>

