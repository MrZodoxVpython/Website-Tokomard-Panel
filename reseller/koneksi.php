<?php
$conn = new mysqli('127.0.0.1', 'benjamin', '', 'xray_db');
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>

