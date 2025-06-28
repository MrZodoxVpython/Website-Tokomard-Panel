<?php
session_start();
require '../koneksi.php'; // Pastikan ini mengarah ke file koneksi DB-mu

// Ambil semua notifikasi dari database
$result = $conn->query("SELECT * FROM notifikasi_admin ORDER BY waktu DESC");
$notifikasi = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $notifikasi[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pesan Notifikasi Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-white min-h-screen py-8 px-4">

<div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 p-6 rounded shadow">
    <h1 class="text-2xl font-bold mb-4">📢 Kirim Notifikasi Admin</h1>

    <!-- Form Kirim -->
    <form method="POST" action="kirim-notifikasi.php" class="space-y-3">
        <textarea name="pesan" placeholder="Tulis pesan notifikasi..." required class="w-full h-24 p-3 border border-gray-300 dark:border-gray-700 rounded bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">Kirim</button>
    </form>

    <?php if (isset($_GET['sukses'])): ?>
        <div class="mt-3 text-green-600">✅ Notifikasi berhasil dikirim!</div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="mt-3 text-red-600">❌ Terjadi kesalahan saat mengirim pesan.</div>
    <?php endif; ?>

    <hr class="my-6 border-gray-400 dark:border-gray-600">

    <!-- Daftar Notifikasi -->
    <h2 class="text-xl font-semibold mb-3">📃 Riwayat Notifikasi</h2>
    <?php if (!empty($notifikasi)): ?>
        <ul class="list-disc pl-6 space-y-2 text-sm">
            <?php foreach ($notifikasi as $item): ?>
                <li>
                    <?= htmlspecialchars($item['pesan']) ?>
                    <span class="text-xs text-gray-500 ml-2">(<?= $item['waktu'] ?>)</span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="text-gray-500">Belum ada notifikasi yang dikirim.</p>
    <?php endif; ?>
</div>

</body>
</html>

