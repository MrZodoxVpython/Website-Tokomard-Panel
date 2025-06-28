<?php if (isset($_GET['sukses'])): ?>
    <div class="bg-green-100 text-green-700 p-2 rounded mb-3">✅ Notifikasi berhasil dikirim ke semua reseller.</div>
<?php elseif (isset($_GET['error'])): ?>
    <div class="bg-red-100 text-red-700 p-2 rounded mb-3">❌ <?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<form method="POST" action="kirim-notifikasi.php" class="bg-white dark:bg-gray-800 p-4 rounded shadow space-y-4">
    <textarea name="pesan" rows="4" class="w-full p-2 border rounded dark:bg-gray-900 dark:text-white" placeholder="Tulis pesan notifikasi ke semua reseller..." required></textarea>
    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Kirim Notifikasi</button>
</form>

