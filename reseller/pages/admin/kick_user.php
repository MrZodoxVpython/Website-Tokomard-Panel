<?php
session_start();

// Validasi hanya admin yang bisa kick
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak.");
}

// Ambil username yang mau di-kick dari URL atau POST
$usernameToKick = $_GET['username'] ?? null;

if (!$usernameToKick) {
    die("Username tidak valid.");
}

$userFile = __DIR__ . '/../data/reseller_users.json';

// Baca data JSON
if (!file_exists($userFile)) {
    die("File user tidak ditemukan.");
}

$users = json_decode(file_get_contents($userFile), true) ?? [];

// Cari user dan ubah status ke `pending`
$found = false;
foreach ($users as &$user) {
    if (strtolower(trim($user['username'])) === strtolower(trim($usernameToKick))) {
        $user['status'] = 'pending';
        $found = true;
        break;
    }
}

if ($found) {
    // Simpan kembali ke file
    file_put_contents($userFile, json_encode($users, JSON_PRETTY_PRINT));
    echo "User <b>$usernameToKick</b> berhasil dikeluarkan dari grup dan harus daftar ulang.";
} else {
    echo "User tidak ditemukan.";
}
?>

