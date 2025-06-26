<?php
$reseller = $_SESSION['reseller'] ?? 'unknown';
$logFile = "/etc/xray/data-panel/akun-reseller/$reseller.txt";

if (!file_exists($logFile)) {
    echo "Belum ada akun.";
    return;
}

$lines = file($logFile);
echo "<pre class='text-green-400'>";
foreach ($lines as $line) {
    echo htmlspecialchars($line);
}
echo "</pre>";

