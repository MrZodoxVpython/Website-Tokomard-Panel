<?php
require '../koneksi.php';
$pesan = trim($_POST['pesan']);
if (!empty($pesan)) {
    $stmt = $conn->prepare("INSERT INTO notifikasi_admin (pesan) VALUES (?)");
    $stmt->bind_param("s", $pesan);
    $stmt->execute();
}
header("Location: halaman-admin.php");

