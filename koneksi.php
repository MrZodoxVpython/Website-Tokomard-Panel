<?php
$host = "localhost";
$user = "benjamin";           // ganti dengan user MySQL kamu
$pass = "wickman";       // ganti dengan password MySQL kamu
$dbname = "xray_db";      // pastikan database ini sudah dibuat

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Koneksi ke database gagal: " . $conn->connect_error);
}
?>

