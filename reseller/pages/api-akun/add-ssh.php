<?php
require_once __DIR__ . '/lib-akun.php';

// Mulai sesi jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validasi agar hanya dijalankan dari browser (bukan CLI)
if (php_sapi_name() === 'cli' || !isset($_SERVER['REQUEST_METHOD'])) {
    echo "❌ Tidak bisa diakses dari CLI atau mode langsung.";
    exit;
}

// Hanya izinkan metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "❌ Akses tidak valid.";
    exit;
}

// Ambil input POST
$username = $_POST['username'] ?? null;
$expiredInput = $_POST['expired'] ?? null;
$password = $_POST['password'] ?? null;

// Validasi input
if (!$username || !$expiredInput || !$password) {
    echo "❌ Data tidak lengkap!";
    exit;
}

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

// Hitung tanggal expired dari input
$expired = hitungTanggalExpired($expiredInput);

// Escape untuk shell agar aman
$eUsername = escapeshellarg($username);
$ePassword = escapeshellarg($password);
$eExpired  = escapeshellarg($expired);

// Jalankan perintah untuk membuat akun SSH
$cmd = "sudo useradd -e $eExpired -s /bin/false -M $eUsername && echo $username:$password | sudo chpasswd";
shell_exec($cmd);

// Tampilkan hasil akun SSH
tampilkanSSH($username, $expired, $password);

