<?php
$conn = new mysqli('benjamin', 'wickman', '', 'xray_db');
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>

