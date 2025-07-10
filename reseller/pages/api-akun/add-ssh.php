<?php
require_once __DIR__ . '/lib-akun.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "❌ Akses tidak valid.";
    exit;
}

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

$expired = hitungTanggalExpired($expiredInput);

// Escape input shell
$eUsername = escapeshellarg($username);
$ePassword = escapeshellarg($password);
$eExpired = escapeshellarg($expired);

// Jalankan perintah membuat user SSH
$cmd = "sudo useradd -e $eExpired -s /bin/false -M $eUsername && echo $eUsername:$ePassword | sudo chpasswd";
shell_exec($cmd);

// Tampilkan hasil akun SSH
tampilkanSSH($username, $expired, $password);

