<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Fungsi mengambil data penggunaan CPU
function getCpuUsage() {
    $load = sys_getloadavg();
    return round($load[0], 2);
}

// Fungsi mengambil data penggunaan RAM
function getRamUsage() {
    $mem = explode("\n", trim(shell_exec("free -m")));
    $memInfo = preg_split('/\s+/', $mem[1]);
    $total = $memInfo[1];
    $used = $memInfo[2];
    return [$used, $total];
}

// Fungsi mengambil penggunaan disk
function getDiskUsage() {
    $disk = shell_exec("df -h / | awk 'NR==2{print $3, $2, $5}'");
    list($used, $total, $percent) = explode(" ", trim($disk));
    return [$used, $total, $percent];
}

// Fungsi mengambil uptime
function getUptime() {
    $uptime = shell_exec("uptime -p");
    return trim($uptime);
}

// Fungsi mengambil bandwidth (butuh vnstat)
function getBandwidth() {
    $output = shell_exec("vnstat --oneline");
    $parts = explode(";", $output);
    return [
        "rx" => $parts[9] ?? "N/A",
        "tx" => $parts[10] ?? "N/A",
        "total" => $parts[11] ?? "N/A"
    ];
}

$cpu = getCpuUsage();
list($ramUsed, $ramTotal) = getRamUsage();
list($diskUsed, $diskTotal, $diskPercent) = getDiskUsage();
$uptime = getUptime();
$bandwidth = getBandwidth();
?>

<!DOCTYPE html>
<html lang="en" class="bg-gray-900 text-white">
<head>
    <meta charset="UTF-8">
    <title>✅ VPS Monitoring</title>
    <script>
        setTimeout(() => location.reload(), 10000); // Auto-refresh 10 detik
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="p-8 font-mono">
    <div class="max-w-2xl mx-auto bg-gray-800 p-6 rounded-2xl shadow-xl">
        <h1 class="text-2xl font-bold text-green-400 mb-4">✅ VPS Monitoring</h1>

        <div class="space-y-4">
            <div class="bg-gray-700 p-4 rounded-lg">
                <h2 class="font-semibold text-lg text-blue-400">CPU Load</h2>
                <p><?= $cpu ?>%</p>
            </div>

            <div class="bg-gray-700 p-4 rounded-lg">
                <h2 class="font-semibold text-lg text-blue-400">RAM Usage</h2>
                <p><?= $ramUsed ?> MB / <?= $ramTotal ?> MB</p>
            </div>

            <div class="bg-gray-700 p-4 rounded-lg">
                <h2 class="font-semibold text-lg text-blue-400">Disk Usage</h2>
                <p><?= $diskUsed ?> / <?= $diskTotal ?> (<?= $diskPercent ?>)</p>
            </div>

            <div class="bg-gray-700 p-4 rounded-lg">
                <h2 class="font-semibold text-lg text-blue-400">Uptime</h2>
                <p><?= $uptime ?></p>
            </div>

            <div class="bg-gray-700 p-4 rounded-lg">
                <h2 class="font-semibold text-lg text-blue-400">Bandwidth (vnstat)</h2>
                <p>Download: <?= $bandwidth['rx'] ?> | Upload: <?= $bandwidth['tx'] ?> | Total: <?= $bandwidth['total'] ?></p>
            </div>
        </div>
    </div>
</body>
</html>

