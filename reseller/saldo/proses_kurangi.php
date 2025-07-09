<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../../koneksi.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $jumlah = intval($_POST['jumlah']);

    if ($username === '' || $jumlah <= 0) {
        $pesan = "Input tidak valid!";
    } else {
        // Ambil saldo user
        $stmt = $conn->prepare("SELECT saldo FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($saldoSekarang);
        if (!$stmt->fetch()) {
            $pesan = "Username \"$username\" tidak ditemukan!";
        } else {
            if ($jumlah > $saldoSekarang) {
                $pesan = "Saldo tidak mencukupi!";
            } else {
                $stmt->close();

                // Kurangi saldo
                $stmt = $conn->prepare("UPDATE users SET saldo = saldo - ? WHERE username = ?");
                $stmt->bind_param("is", $jumlah, $username);
                if ($stmt->execute()) {
                    // Simpan ke log
                    $stmtLog = $conn->prepare("INSERT INTO saldo_log (username, aksi, jumlah, keterangan) VALUES (?, 'kurangi', ?, 'Pengurangan saldo oleh admin')");
                    $stmtLog->bind_param("si", $username, $jumlah);
                    $stmtLog->execute();
                    $stmtLog->close();
		    
		    // --- Tambahkan transaksi ke tabel transactions
		    $stmtUserId = $conn->prepare("SELECT id FROM users WHERE username = ?");
		    $stmtUserId->bind_param("s", $username);
		    $stmtUserId->execute();
		    $stmtUserId->bind_result($userId);
		    $stmtUserId->fetch();
		    $stmtUserId->close();

		    $type = 'manual';
		    $status = 'SUCCESS';
		    $amount = $jumlah;
		    $detail = 'Pengurangan saldo oleh admin';
		    $dateNow = date('Y-m-d H:i:s');

		    $stmtTrans = $conn->prepare("INSERT INTO transactions (user_id, type, status, amount, detail, date) VALUES (?, ?, ?, ?, ?, ?)");
		    $stmtTrans->bind_param("ississ", $userId, $type, $status, $amount, $detail, $dateNow);
		    $stmtTrans->execute();
		    $stmtTrans->close();
                    $pesan = "Saldo berhasil dikurangi sejumlah Rp" . number_format($jumlah, 0, ',', '.');
                } else {
                    $pesan = "Gagal mengurangi saldo.";
                }
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pengurangan Saldo</title>
    <meta charset="UTF-8">
    <link href="https://unpkg.com/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white shadow-md rounded-lg p-6 max-w-lg text-center">
        <h2 class="text-2xl font-bold mb-4">Hasil Pengurangan Saldo</h2>
        <div class="text-gray-700 text-lg mb-4">
            <?= htmlspecialchars($pesan) ?>
        </div>
        <a href="index.php" class="inline-block mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Kembali ke Saldo</a>
        <a href="histori.php" class="inline-block mt-4 ml-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Lihat Histori</a>
    </div>
</body>
</html>

