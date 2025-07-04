<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'reseller') {
    echo "<script>window.location.href = '../index.php';</script>";
    exit;
}

require __DIR__ . '/../../koneksi.php';
$reseller = $_SESSION['username'];
$result = mysqli_query($conn, "SELECT saldo FROM users WHERE username = '$reseller'");
$row = mysqli_fetch_assoc($result);
$saldo = $row['saldo'];

$paymentMethods = [
    ['icon' => '💳', 'name' => 'Virtual Account BCA'],
    ['icon' => '💵', 'name' => 'Dana'],
    ['icon' => '💶', 'name' => 'OVO'],
    ['icon' => '🌐', 'name' => 'QRIS'],
];
?>

<div class="mb-6">
    <h2 class="text-2xl font-semibold mb-2">💰 Topup Saldo</h2>
    <p class="text-sm text-gray-600 dark:text-gray-400">Saldo saat ini: <span class="font-bold">Rp<?= number_format($saldo, 0, ',', '.') ?></span></p>
</div>

<form action="proses_topup.php" method="POST" class="space-y-4">
    <div>
        <label class="block text-sm font-medium mb-1">Nominal Topup (Rp)</label>
        <input type="number" name="jumlah" min="10000" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:border-blue-300">
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Pilih Metode Pembayaran</label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php foreach ($paymentMethods as $method): ?>
                <label class="flex items-center space-x-2 p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded shadow cursor-pointer">
                    <input type="radio" name="metode" value="<?= htmlspecialchars($method['name']) ?>" required>
                    <span><?= $method['icon'] ?> <?= htmlspecialchars($method['name']) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Catatan / Referensi</label>
        <input type="text" name="catatan" placeholder="Contoh: Topup dari Dana pribadi" class="w-full border p-2 rounded">
    </div>

    <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded shadow">Submit Topup</button>
</form>

<div class="mt-6">
    <a href="index.php" class="inline-block bg-gray-600 hover:bg-gray-500 text-white text-sm px-4 py-2 rounded shadow transition">
        ⬅️ Kembali
    </a>
</div>
