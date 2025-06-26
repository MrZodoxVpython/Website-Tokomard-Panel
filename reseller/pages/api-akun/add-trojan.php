<?php
session_start();
require_once __DIR__ . '/../lib-akun.php'; // sesuaikan path jika perlu

// Fungsi generate UUID jika password kosong
function generateUUID() {
    return trim(shell_exec('cat /proc/sys/kernel/random/uuid'));
}

// Validasi metode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "❌ Akses tidak valid.";
    exit;
}

// Ambil data dari POST
$username      = $_POST['username'] ?? null;
$expiredInput  = $_POST['expired'] ?? null;
$passwordInput = $_POST['password'] ?? '';

// Validasi minimal
if (!$username || !$expiredInput) {
    echo "❌ Data tidak lengkap!";
    exit;
}

// Generate password jika kosong
$password = trim($passwordInput) !== '' ? $passwordInput : generateUUID();

// Hitung expired dalam format tanggal
$expired = hitungTanggalExpired($expiredInput);

// Format baris komentar dan JSON untuk Xray
$commentLine = "#! $username $expired";
$jsonLine = "},{\"password\": \"$password\", \"email\": \"$username\"";

// Tag protokol Trojan (ws dan grpc)
$tags = ['trojanws', 'trojangrpc'];

// Eksekusi fungsi penambahan akun Trojan
prosesXray('trojan', $tags, $commentLine, $jsonLine, $username, $expired, $password);

