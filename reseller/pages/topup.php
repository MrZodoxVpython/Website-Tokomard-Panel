<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . '/../../koneksi.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'reseller') {
    header("Location: ../index.php");
    exit;
}

$reseller = $_SESSION['username'];
?>

<div class="mb-6">
    <h2 class="text-2xl font-semibold mb-1 text-gray-800 dark:text-white">💰 Topup Saldo</h2>
    <p class="text-sm text-gray-600 dark:text-gray-300">Isi form untuk menambah saldo akun Anda.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 border border-gray-200 dark:border-gray-700">
        <form action="proses_topup.php" method="POST" class="space-y-5">
            <div>
                <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">👤 Username</label>
                <input type="text" readonly value="<?= htmlspecialchars($reseller) ?>"
                    class="w-full px-4 py-2 rounded border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-black dark:text-white">
            </div>

            <div>
                <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">💵 Nominal Topup</label>
                <input type="number" name="nominal" min="1000" step="500" required placeholder="Contoh: 10000"
                    class="w-full px-4 py-2 border rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-black dark:text-white">
            </div>

            <div>
                <label class="block font-semibold mb-2 text-gray-700 dark:text-gray-300">💳 Metode Pembayaran</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4" id="metode-container">
                    <label id="label-qris" class="method-label flex flex-col items-center bg-gray-50 dark:bg-gray-700 p-4 rounded-xl shadow cursor-pointer transition-all duration-200 border-2 border-transparent">
                        <input type="radio" name="metode" value="qris" required class="hidden" onchange="showInfo('qris')">
                        <img src="https://img.icons8.com/ios-filled/100/000000/qr-code.png" class="w-10 h-10 mb-2 transition-all duration-200" id="img-qris" alt="QRIS"/>
                        <span class="text-sm font-semibold text-gray-800 dark:text-white">QRIS</span>
                    </label>
                    <label id="label-dana" class="method-label flex flex-col items-center bg-gray-50 dark:bg-gray-700 p-4 rounded-xl shadow cursor-pointer transition-all duration-200 border-2 border-transparent">
                        <input type="radio" name="metode" value="dana" required class="hidden" onchange="showInfo('dana')">
                        <img src="https://img.icons8.com/color/96/dana.png" class="w-10 h-10 mb-2 transition-all duration-200" id="img-dana" alt="Dana"/>
                        <span class="text-sm font-semibold text-gray-800 dark:text-white">DANA</span>
                    </label>
                    <label id="label-bank" class="method-label flex flex-col items-center bg-gray-50 dark:bg-gray-700 p-4 rounded-xl shadow cursor-pointer transition-all duration-200 border-2 border-transparent">
                        <input type="radio" name="metode" value="bank" required class="hidden" onchange="showInfo('bank')">
                        <img src="https://img.icons8.com/color/96/bank-building.png" class="w-10 h-10 mb-2 transition-all duration-200" id="img-bank" alt="Bank"/>
                        <span class="text-sm font-semibold text-gray-800 dark:text-white">Bank</span>
                    </label>
                </div>
            </div>

            <div>
                <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">📝 Catatan (opsional)</label>
                <textarea name="catatan" rows="3"
                    class="w-full px-4 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-black dark:text-white"
                    placeholder="Contoh: Transfer dari BCA a.n. Andi, jam 10:15"></textarea>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                    class="px-6 py-3 bg-green-600 hover:bg-green-500 text-white font-bold rounded-xl transition">
                    🚀 Konfirmasi Topup
                </button>
            </div>
        </form>
    </div>

    <!-- Info Pembayaran -->
    <div class="bg-gray-100 dark:bg-gray-800 rounded-xl shadow p-6 border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">📌 Info Pembayaran</h3>

        <!-- QRIS -->
        <div id="info-qris" class="hidden">
            <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">Silakan scan QR di bawah ini untuk pembayaran QRIS:</p>
            <img src="https://i.imgur.com/LrhI27t.jpeg" alt="QRIS" class="w-full max-w-xs rounded-xl border mx-auto mb-2">
        </div>

        <!-- DANA -->
        <div id="info-dana" class="hidden">
            <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">Kirim ke akun DANA berikut:</p>
            <ul class="text-sm text-gray-800 dark:text-gray-100 list-disc list-inside">
                <li>Nomor: 0812-3456-7890</li>
                <li>Nama: TOKOMARD</li>
                <li>Link API: <a href="https://link.dana.id/minta/1234567890" target="_blank" class="text-blue-600 underline">Klik untuk bayar otomatis</a></li>
            </ul>
        </div>

        <!-- Bank -->
        <div id="info-bank" class="hidden">
            <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">Transfer ke rekening bank berikut:</p>
            <ul class="text-sm text-gray-800 dark:text-gray-100 list-disc list-inside">
                <li>Bank: BCA</li>
                <li>No Rekening: 1234567890</li>
                <li>Nama: TOKOMARD</li>
            </ul>
        </div>

        <div class="mt-4">
            <a href="index.php"
               class="inline-block bg-indigo-600 hover:bg-indigo-500 text-white text-sm px-4 py-2 rounded shadow transition">
                ⬅ Kembali ke Dashboard
            </a>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
function showInfo(method) {
    const ids = ['qris', 'dana', 'bank'];
    ids.forEach(id => {
        document.getElementById('info-' + id).classList.add('hidden');
        document.getElementById('label-' + id).classList.remove('ring-2', 'ring-blue-500', 'bg-blue-50', 'dark:bg-blue-900', 'border-blue-500');
        document.getElementById('img-' + id).classList.remove('scale-110', 'drop-shadow-lg');
    });

    document.getElementById('info-' + method).classList.remove('hidden');
    document.getElementById('label-' + method).classList.add('ring-2', 'ring-blue-500', 'bg-blue-50', 'dark:bg-blue-900', 'border-blue-500');
    document.getElementById('img-' + method).classList.add('scale-110', 'drop-shadow-lg');
}
</script>

