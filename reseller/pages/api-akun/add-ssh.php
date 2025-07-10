<?php
require_once __DIR__ . '/lib-akun.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Deteksi apakah dipanggil dari HTTP POST atau dari include/shell_exec
$isWebRequest = isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST';
$isInternalCall = php_sapi_name() === 'cli' || basename(__FILE__) !== basename($_SERVER['SCRIPT_FILENAME']);

if (!$isWebRequest && !$isInternalCall) {
    echo "❌ Akses tidak valid.";
    exit;
}

// Ambil input (dari POST atau Fallback CLI)
$username = $_POST['username'] ?? $argv[1] ?? null;
$expiredInput = $_POST['expired'] ?? $argv[2] ?? null;
$password = $_POST['password'] ?? $argv[3] ?? null;

// Validasi input
if (!$username || !$expiredInput || !$password) {
    echo "❌ Data tidak lengkap!";
    exit;
}

// Validasi format
if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    echo "❌ Username hanya boleh huruf, angka, dan garis bawah (_)";
    exit;
}

if (strlen($password) < 6) {
    echo "❌ Password minimal 6 karakter!";
    exit;
}

// Cek apakah user sudah ada
$checkUser = trim(shell_exec("id -u " . escapeshellarg($username) . " 2>/dev/null"));
if ($checkUser !== '') {
    echo "❌ Username sudah terdaftar!";
    exit;
}

// Hitung tanggal expired
$expired = hitungTanggalExpired($expiredInput);

// Escape input
$eUsername = escapeshellarg($username);
$ePassword = escapeshellarg($password);
$eExpired  = escapeshellarg($expired);

// Buat user SSH
//$cmd = "sudo useradd -e $eExpired -s /bin/false -M $eUsername && echo $username:$password | sudo chpasswd";
$useradd = "/usr/sbin/useradd";
$chpasswd = "/usr/sbin/chpasswd";
//$cmd = "echo $username:$password | sudo $chpasswd && sudo $useradd -e $eExpired -s /bin/false -M $eUsername";
$cmd = "sudo $useradd -e $eExpired -s /bin/false -M $eUsername && echo '$username:$password' | sudo $chpasswd";


shell_exec($cmd);

// Tampilkan hasil akun
tampilkanSSH($username, $expired, $password);

