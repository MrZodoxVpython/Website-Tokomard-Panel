<?php
require_once __DIR__ . '/lib-akun.php'; // path disesuaikan

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "❌ Akses tidak valid.";
    exit;
}

$username = $_POST['username'] ?? null;
$expiredInput = $_POST['expired'] ?? null;
$password = $_POST['password'] ?? null;

if (!$username || !$expired_input || !$password) {
    echo "❌ Data tidak lengkap!";
    exit;
}

$expired = hitungTanggalExpired($expired_input);

$commentLine = "#! $username $expired";
$jsonLine = "},{\"password\": \"$password\", \"email\": \"$username\"";
$tags = ['trojanws', 'trojangrpc'];

prosesXray('trojan', $tags, $commentLine, $jsonLine, $username, $expired, $password);

