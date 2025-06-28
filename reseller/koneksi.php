<?php
$servername = "127.0.0.1";
$username = "benjamin";
$password = "wickman";
$database = "xray_db";

// Buat koneksi
$conn = new mysqli($servername, $username, $password, $database);

// Cek koneksi
if ($conn->connect_error) {
    die("❌ Koneksi gagal: " . $conn->connect_error);
}

// Koneksi berhasil
// echo "✅ Koneksi berhasil!";
?>

