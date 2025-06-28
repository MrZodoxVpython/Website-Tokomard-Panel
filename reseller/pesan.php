<?php if (isset($_GET['sukses'])): ?>
    <div class="flex items-center p-4 mb-6 text-sm text-green-800 bg-green-50 border border-green-300 rounded-lg shadow-sm dark:bg-green-900 dark:text-green-300 dark:border-green-700" role="alert">
        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
        </svg>
        <span class="ml-3 font-medium">Notifikasi berhasil dikirim ke semua reseller.</span>
    </div>
<?php elseif (isset($_GET['error'])): ?>
    <div class="flex items-center p-4 mb-6 text-sm text-red-800 bg-red-50 border border-red-300 rounded-lg shadow-sm dark:bg-red-900 dark:text-red-300 dark:border-red-700" role="alert">
        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
        <span class="ml-3 font-medium"><?= htmlspecialchars($_GET['error']) ?></span>
    </div>
<?php endif; ?>

<div class="max-w-xl mx-auto bg-white dark:bg-gray-900 p-8 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
    <h2 class="text-2xl font-semibold mb-6 text-gray-900 dark:text-gray-100 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A2 2 0 0121 9.618v4.764a2 2 0 01-1.447 1.894L15 14M10 14V9m0 0L6 12m4-3l4 3" />
        </svg>
        Kirim Notifikasi ke Semua Reseller
    </h2>
    <form method="POST" action="kirim-notifikasi.php" class="space-y-5">
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
            class="w-full flex justify-center items-center gap-2 px-6 py-3 text-white bg-blue-600 rounded-lg shadow-md hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-800 transition duration-300 font-semibold">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
            🚀 Kirim Notifikasi
        </button>
    </form>
</div>

