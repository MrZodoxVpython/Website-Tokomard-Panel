<?php
session_start();
require_once __DIR__ . '/../lib-akun.php'; // pastikan path relatif benar

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "❌ Akses tidak valid.";
    exit;
}

$username      = $_POST['username'] ?? null;
$expiredInput  = $_POST['expired'] ?? null;
$passwordInput = $_POST['password'] ?? '';

if (!$username || !$expiredInput) {
    echo "❌ Data tidak lengkap!";
    exit;
}

$password = trim($passwordInput) !== '' ? $passwordInput : generateUUID();
$expired  = hitungTanggalExpired($expiredInput);

$commentLine = "#! $username $expired";
$jsonLine    = "},{\"password\": \"$password\", \"email\": \"$username\"";
$tags        = ['trojanws', 'trojangrpc'];

prosesXray('trojan', $tags, $commentLine, $jsonLine, $username, $expired, $password);

