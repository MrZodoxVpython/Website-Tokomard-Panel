<?php if (isset($_GET['sukses'])): ?>
    <div class="flex items-center p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg dark:bg-green-200 dark:text-green-800" role="alert">
        ✅ <span class="ml-2">Notifikasi berhasil dikirim ke semua reseller.</span>
    </div>
<?php elseif (isset($_GET['error'])): ?>
    <div class="flex items-center p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg dark:bg-red-200 dark:text-red-800" role="alert">
        ❌ <span class="ml-2"><?= htmlspecialchars($_GET['error']) ?></span>
    </div>
<?php endif; ?>

<div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
    <h2 class="text-xl font-semibold mb-4 text-gray-800 dark:text-white">📝 Kirim Notifikasi ke Semua Reseller</h2>
    <form method="POST" action="kirim-notifikasi.php" class="space-y-4">
        <textarea 
            name="pesan" 
            rows="5" 
            class="w-full p-3 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none dark:bg-gray-900 dark:border-gray-700 dark:text-white" 
            placeholder="Tulis pesan penting kepada semua reseller..." 
            required></textarea>
        <button 
            type="submit" 
            class="inline-flex items-center px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:focus:ring-blue-800 transition">
            🚀 Kirim Notifikasi
        </button>
    </form>
</div>

