<?php
session_start();
require '../../koneksi.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $jumlah = intval($_POST['jumlah'] ?? 0);

    if (!$username || $jumlah <= 0) {
        echo "Data tidak valid!";
        exit;
    }

    // Update saldo reseller
    $update = $conn->prepare("UPDATE users SET saldo = saldo + ? WHERE username = ? AND role = 'reseller'");
    $update->bind_param("is", $jumlah, $username);
    if (!$update->execute()) {
        echo "Gagal update saldo: " . $update->error;
        exit;
    }

    // Simpan ke saldo_log (pastikan tabelnya sudah dibuat)
    $log = $conn->prepare("INSERT INTO saldo_log (username, jumlah, jenis, keterangan) VALUES (?, ?, 'tambah', 'Topup oleh admin')");
    $log->bind_param("si", $username, $jumlah);
    if (!$log->execute()) {
        echo "Gagal menyimpan log: " . $log->error;
        exit;
    }

    header("Location: topup.php?success=1");
    exit;
}

