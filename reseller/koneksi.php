<?php
$conn = new mysqli('localhost', 'root', '', 'nama_database');
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>

