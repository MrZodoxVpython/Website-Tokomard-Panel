<?php
session_start();
require '../koneksi.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$saldoAwal = 0;
$saldoAkhir = 0;
$username = '';
$jumlah = 0;
$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $jumlah = (int) $_POST['jumlah'];

    // Ambil saldo saat ini
    $stmt = $conn->prepare("SELECT saldo FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($saldoAwal);
    if (!$stmt->fetch()) {
        $pesan = "❌ User <strong>$username</strong> tidak ditemukan.";
        $stmt->close();
    } else {
        $stmt->close();

        if ($jumlah > $saldoAwal) {
            $pesan = "❌ Saldo user <strong>$username</strong> tidak mencukupi untuk pengurangan Rp" . number_format($jumlah);
        } else {
            // Kurangi saldo
            $saldoAkhir = $saldoAwal - $jumlah;
            $stmt = $conn->prepare("UPDATE users SET saldo = ? WHERE username = ?");
            $stmt->bind_param("is", $saldoAkhir, $username);
            $stmt->execute();
            $stmt->close();

            // Simpan log
            $stmt = $conn->prepare("INSERT INTO saldo_log (username, jumlah, jenis, keterangan) VALUES (?, ?, 'kurangi', 'Dikurangi oleh admin')");
            $stmt->bind_param("si", $username, $jumlah);
            $stmt->execute();
            $stmt->close();

            $pesan = "✅ Saldo user <strong>$username</strong> berhasil dikurangi.<br>💰 <strong>Saldo Awal:</strong> Rp" . number_format($saldoAwal) . "<br>💸 <strong>Dikurangi:</strong> Rp" . number_format($jumlah) . "<br>🧾 <strong>Saldo Akhir:</strong> Rp" . number_format($saldoAkhir);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Saldo Dikurangi</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white shadow-md rounded-lg p-6 max-w-lg text-center">
        <h2 class="text-2xl font-bold mb-4">Hasil Pengurangan Saldo</h2>
        <div class="text-gray-700 text-lg mb-4">
            <?= $pesan ?>
        </div>
        <a href="index.php" class="inline-block mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Kembali ke Saldo</a>
        <a href="histori.php" class="inline-block mt-4 ml-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Lihat Histori</a>
    </div>
</body>
</html>

