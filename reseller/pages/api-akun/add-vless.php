<?php
if (php_sapi_name() !== 'cli') session_start();
require_once __DIR__ . '/lib-akun.php';

// === Mode CLI ===
if (php_sapi_name() === 'cli') {
    if ($argc < 5) {
        echo "❌ Parameter tidak lengkap!\n";
        echo "Gunakan: php add-vless.php username expired password reseller\n";
        exit(1);
    }

    $username = trim($argv[1]);
    $expiredInput = trim($argv[2]);
    $uuid = trim($argv[3]);
    $reseller = trim($argv[4]);
}
// === Mode Web (POST) ===
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $expiredInput = trim($_POST['expired'] ?? '');
    $uuid = trim($_POST['uuid'] ?? '');

    if (empty($username) || empty($expiredInput)) {
        echo "❌ Data tidak lengkap!";
        exit;
    }

    if (empty($uuid)) {
        $uuid = generateUUID();
    }

    $reseller = $_SESSION['username'] ?? 'admin';
}
// === Invalid akses ===
else {
    echo "❌ Akses tidak valid.";
    exit;
}

// Hitung tanggal expired
$expired = hitungTanggalExpired($expiredInput);

// Format baris untuk config Xray
$commentLine = "### $username $expired";
$jsonLine = "},{\"id\": \"$uuid\", \"email\": \"$username\"";
$tags = ['vmess', 'vmessgrpc'];

// Proses penambahan akun
$hasil = prosesXray('vmess', $tags, $commentLine, $jsonLine, $username, $expired, $uuid, $reseller);

