<?php
require_once __DIR__ . '/lib-akun.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "❌ Akses tidak valid.";
    exit;
}

$username = $_POST['username'] ?? null;
$expiredInput = $_POST['expired'] ?? null;
$password = $_POST['password'] ?? null;

// Validasi
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

$expired = hitungTanggalExpired($expiredInput);

$eUsername = escapeshellarg($username);
$ePassword = escapeshellarg($password);
$eExpired  = escapeshellarg($expired);

// Path lengkap
$useradd = "/usr/sbin/useradd";
$chpasswd = "/usr/sbin/chpasswd";

// Jalankan sudo tanpa password
$cmd = "sudo $useradd -e $eExpired -s /bin/false -M $eUsername && echo $username:$password | sudo $chpasswd";
shell_exec($cmd);

// Output akun SSH
tampilkanSSH($username, $expired, $password);

