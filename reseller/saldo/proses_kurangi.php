<?php
session_start();
require '../koneksi.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $jumlah = (int) $_POST['jumlah'];

    // Ambil saldo saat ini
    $stmt = $conn->prepare("SELECT saldo FROM reseller WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($saldoSekarang);
    if (!$stmt->fetch()) {
        echo "Reseller tidak ditemukan.";
        exit;
    }
    $stmt->close();

    // Cek cukup atau tidak
    if ($jumlah > $saldoSekarang) {
        echo "Saldo tidak mencukupi.";
        exit;
    }

    // Update saldo
    $saldoBaru = $saldoSekarang - $jumlah;
    $stmt = $conn->prepare("UPDATE reseller SET saldo = ? WHERE username = ?");
    $stmt->bind_param("is", $saldoBaru, $username);
    $stmt->execute();
    $stmt->close();

    // Catat ke log
    $stmt = $conn->prepare("INSERT INTO saldo_log (username, aksi, jumlah) VALUES (?, 'kurangi', ?)");
    $stmt->bind_param("si", $username, $jumlah);
    $stmt->execute();
    $stmt->close();

    header("Location: histori.php");
    exit;
}

