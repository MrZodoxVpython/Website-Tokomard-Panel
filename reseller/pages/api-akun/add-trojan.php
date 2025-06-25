<?php
require_once '/lib-akun.php';

// Ambil data dari POST
$username = $_POST['username'] ?? null;
$expired_input = $_POST['expired'] ?? null;
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

