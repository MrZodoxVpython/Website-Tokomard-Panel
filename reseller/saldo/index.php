<?php
session_start();
require '../../koneksi.php';

$reseller = $_SESSION['username'];
$result = mysqli_query($conn, "SELECT saldo FROM users WHERE username = '$reseller'");
$row = mysqli_fetch_assoc($result);
$saldo = $row['saldo'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Saldo Reseller</title>
</head>
<body>
  <h1>Saldo Anda: Rp<?= number_format($saldo, 0, ',', '.') ?></h1>
  <a href="tambah.php">Tambah Saldo</a> | <a href="kurangi.php">Kurangi Saldo</a>
</body>
</html>

