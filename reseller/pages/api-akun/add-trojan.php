<?php
session_start();
require_once __DIR__ . '/lib-akun.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "❌ Akses tidak valid.";
    exit;
}

// Ambil data dari form
$username = trim($_POST['username'] ?? '');
$expiredInput = trim($_POST['expired'] ?? '');
$password = trim($_POST['password'] ?? '');

// Validasi input
if (empty($username) || empty($expiredInput)) {
    echo "❌ Data tidak lengkap!";
    exit;
}

// Generate password jika kosong
if (empty($password)) {
    $password = generateUUID();
}

// Hitung tanggal expired
$expired = hitungTanggalExpired($expiredInput);

// Siapkan format untuk config Xray
$commentLine = "#! $username $expired";
$jsonLine = "},{\"password\": \"$password\", \"email\": \"$username\"";
$tags = ['trojanws', 'trojangrpc'];

// Proses penambahan akun Xray Trojan
$hasil = prosesXray('trojan', $tags, $commentLine, $jsonLine, $username, $expired, $password);

// Tampilkan hasil (jika ingin untuk remote SSH)
echo $hasil;

