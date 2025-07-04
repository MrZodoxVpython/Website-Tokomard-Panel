<?php
session_start();
require '../koneksi.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$result = $conn->query("SELECT * FROM saldo_log ORDER BY waktu DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Histori Transaksi Saldo</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-xl font-bold mb-4">Histori Transaksi Saldo</h1>
        <table class="min-w-full text-sm border">
            <thead class="bg-gray-200">
                <tr>
                    <th class="p-2 border">No</th>
                    <th class="p-2 border">Username</th>
                    <th class="p-2 border">Aksi</th>
                    <th class="p-2 border">Jumlah</th>
                    <th class="p-2 border">Waktu</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while($row = $result->fetch_assoc()): ?>
                <tr class="border-t">
                    <td class="p-2 border text-center"><?= $no++ ?></td>
                    <td class="p-2 border"><?= htmlspecialchars($row['username']) ?></td>
                    <td class="p-2 border"><?= $row['aksi'] === 'tambah' ? '➕ Tambah' : '➖ Kurangi' ?></td>
                    <td class="p-2 border"><?= number_format($row['jumlah']) ?></td>
                    <td class="p-2 border"><?= $row['waktu'] ?></td>
                </tr>
                <?php endwhile ?>
            </tbody>
        </table>
    </div>
</body>
</html>

