<?php
session_start(); // WAJIB

$reseller = $_SESSION['reseller'] ?? 'unknown';
echo "SESSION: " . var_export($reseller, true) . "<br>";

$logFile = "/etc/xray/data-panel/akun-reseller/$reseller.txt";

if (!file_exists($logFile)) {
    echo "Belum ada akun.";
    return;
}

$lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

if (!$lines) {
    echo "File ditemukan, tapi kosong.";
    return;
}

echo "<pre class='text-green-400'>";
foreach ($lines as $line) {
    echo htmlspecialchars($line) . "\n";
}
echo "</pre>";

