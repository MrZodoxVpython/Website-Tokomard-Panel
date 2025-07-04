<?php
session_start();
require '../koneksi.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $jumlah = (int) $_POST['jumlah'];

    if ($username === '' || $jumlah <= 0) {
        echo "Data tidak valid.";
        exit;
    }

    $update = $conn->prepare("UPDATE users SET saldo = saldo + ? WHERE username = ?");
    $update->bind_param("is", $jumlah, $username);
    $update->execute();
    $update->close();

    // Simpan log
    $log = $conn->prepare("INSERT INTO saldo_log (username, aksi, jumlah) VALUES (?, 'tambah', ?)");
    $log->bind_param("si", $username, $jumlah);
    $log->execute();
    $log->close();

    header("Location: histori.php");
    exit;
}

