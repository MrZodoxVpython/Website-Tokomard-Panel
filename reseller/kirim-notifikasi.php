<?php
require '../koneksi.php'; // Pastikan koneksi $conn sudah benar

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pesan'])) {
    $pesan = trim($_POST['pesan']);
    if (!empty($pesan)) {
        $stmt = $conn->prepare("INSERT INTO notifikasi_admin (pesan) VALUES (?)");
        $stmt->bind_param("s", $pesan);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: pesan.php?sukses=1");
exit;

