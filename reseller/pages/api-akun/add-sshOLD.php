<?php
require_once __DIR__ . '/lib-akun.php'; // path disesuaikan

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "❌ Akses tidak valid.";
    exit;
}

$username = $_POST['username'] ?? null;
$expiredInput = $_POST['expired'] ?? null;
$password = $_POST['password'] ?? null;

if (!$username || !$expiredInput || !$password) {
    echo "❌ Data tidak lengkap!";
    exit;
}

$expired = hitungTanggalExpired($expiredInput);

// Jalankan perintah menambahkan user
$cmd = "sudo useradd -e $expired -s /bin/false -M $username && echo \"$username:$password\" | sudo chpasswd";
shell_exec($cmd);

// Tampilkan hasil akun
tampilkanSSH($username, $expired, $password);

