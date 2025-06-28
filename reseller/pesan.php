<?php
require '../koneksi.php';

// Kirim Notifikasi ke Semua Reseller
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['kirim_pesan'])) {
        $pesan = trim($_POST['pesan']);
        if (!empty($pesan)) {
            $result = $conn->query("SELECT username FROM users WHERE role = 'reseller'");
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $reseller = $row['username'];
                    $stmt = $conn->prepare("INSERT INTO notifikasi_reseller (username, pesan) VALUES (?, ?)");
                    $stmt->bind_param("ss", $reseller, $pesan);
                    $stmt->execute();
                    $stmt->close();
                }
                $sukses = true;
            } else {
                $error = "Tidak ada reseller ditemukan.";
            }
        } else {
            $error = "Isi pesan tidak boleh kosong.";
        }
    }

    // Hapus Pesan
    if (isset($_POST['hapus_pesan']) && isset($_POST['pesan_hapus'])) {
        $pesanHapus = $_POST['pesan_hapus'];
        $stmt = $conn->prepare("DELETE FROM notifikasi_reseller WHERE pesan = ?");
        $stmt->bind_param("s", $pesanHapus);
        $stmt->execute();
        $stmt->close();
    }
}

// Ambil daftar pesan terkirim
$query = "
    SELECT pesan, MAX(dibuat_pada) AS waktu_kirim, COUNT(*) AS jumlah_reseller 
    FROM notifikasi_reseller 
    GROUP BY pesan 
    ORDER BY waktu_kirim DESC
";
$hasil = $conn->query($query);
$daftarPesan = $hasil->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Kirim Notifikasi Reseller</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-xl">

    <?php if (!empty($sukses)): ?>
        <div class="flex items-center p-4 mb-6 text-sm text-green-800 bg-green-50 border border-green-300 rounded-lg shadow-sm dark:bg-green-900 dark:text-green-300 dark:border-green-700" role="alert">
            ✅ Notifikasi berhasil dikirim ke semua reseller.
        </div>
    <?php elseif (!empty($error)): ?>
        <div class="flex items-center p-4 mb-6 text-sm text-red-800 bg-red-50 border border-red-300 rounded-lg shadow-sm dark:bg-red-900 dark:text-red-300 dark:border-red-700" role="alert">
            ❌ <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="bg-white dark:bg-gray-900 p-8 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
        <h2 class="text-2xl font-semibold mb-6 text-gray-900 dark:text-gray-100 flex items-center gap-2">
            📨 Kirim Notifikasi ke Semua Reseller
        </h2>
        <form method="POST" class="space-y-5">
            <textarea 
                name="pesan" 
                rows="6" 
                class="w-full p-4 text-base border border-gray-300 rounded-lg shadow-sm focus:ring-3 focus:ring-blue-400 focus:border-blue-600 transition duration-300 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100 dark:placeholder-gray-400" 
                placeholder="Tulis pesan penting kepada semua reseller..." 
                required
                autofocus
            ></textarea>
            <button 
                type="submit" 
                name="kirim_pesan"
                class="w-full flex justify-center items-center gap-2 px-6 py-3 text-white bg-blue-600 rounded-lg shadow-md hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-800 transition duration-300 font-semibold">
                🚀 Kirim Notifikasi
            </button>
        </form>
    </div>

    <div class="w-full bg-white dark:bg-gray-800 p-6 rounded-xl shadow mt-8 border border-gray-200 dark:border-gray-700">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">📋 Daftar Pesan Terkirim</h3>
        <?php if (count($daftarPesan) > 0): ?>
            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($daftarPesan as $pesan): ?>
                    <li class="py-4 flex justify-between items-start">
                        <div>
                            <div class="text-sm text-gray-800 dark:text-gray-100 mb-1"><?= nl2br(htmlspecialchars($pesan['pesan'])) ?></div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">📅 <?= date('d M Y, H:i', strtotime($pesan['waktu_kirim'])) ?> • Dikirim ke <?= $pesan['jumlah_reseller'] ?> reseller</div>
                        </div>
                        <form method="POST" onsubmit="return confirm('Yakin ingin menghapus pesan ini dari semua reseller?')" class="ml-4">
                            <input type="hidden" name="pesan_hapus" value="<?= htmlspecialchars($pesan['pesan']) ?>">
                            <button name="hapus_pesan" class="text-red-600 dark:text-red-400 text-sm hover:underline">Hapus</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="text-sm italic text-gray-500">Belum ada pesan yang dikirim.</p>
        <?php endif; ?>
    </div>

</div>
</body>
</html>

