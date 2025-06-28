<?php
require '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pesan'])) {
    $pesan = trim($_POST['pesan']);

    if (!empty($pesan)) {
        // Ambil semua user dengan role reseller
        $result = $conn->query("SELECT username FROM users WHERE role = 'reseller'");

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $reseller = $row['username'];

                // Simpan pesan ke tabel notifikasi_reseller
                $stmt = $conn->prepare("INSERT INTO notifikasi_reseller (username, pesan) VALUES (?, ?)");
                $stmt->bind_param("ss", $reseller, $pesan);
                $stmt->execute();
                $stmt->close();
            }

            header("Location: pesan.php?sukses=1");
            exit;
        } else {
            // Tidak ada reseller ditemukan
            header("Location: pesan.php?error=Tidak ada reseller");
            exit;
        }
    }
}

header("Location: pesan.php?error=Isi pesan tidak boleh kosong");
exit;

