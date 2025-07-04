<?php
session_start();
require '../../koneksi.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak");
}

$username = $_POST['username'];  // reseller target dari form
$jumlah = intval($_POST['jumlah']);
$admin = $_SESSION['username'];  // admin yang melakukan topup

// Cek apakah reseller valid
$stmt = $conn->prepare("SELECT saldo FROM users WHERE username = ? AND role = 'reseller'");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Reseller tidak ditemukan.");
}

$row = $result->fetch_assoc();
$saldoBaru = $row['saldo'] + $jumlah;

// Update saldo reseller
$update = $conn->prepare("UPDATE users SET saldo = ? WHERE username = ?");
$update->bind_param("is", $saldoBaru, $username);
$update->execute();

// (Opsional) Simpan histori
$log = $conn->prepare("INSERT INTO saldo_log (username, aksi, jumlah, oleh) VALUES (?, 'tambah', ?, ?)");
$log->bind_param("sis", $username, $jumlah, $admin);
$log->execute();

header("Location: index.php?sukses=1");
exit;

