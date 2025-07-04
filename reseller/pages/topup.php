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
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <?php
                    $metodes = [
                        ['qris', 'QRIS', 'https://img.icons8.com/ios-filled/100/000000/qr-code.png'],
                        ['dana', 'DANA', 'https://img.icons8.com/color/96/dana.png'],
                        ['bank', 'Bank', 'https://img.icons8.com/color/96/bank-building.png']
                    ];
                    foreach ($metodes as [$value, $label, $img]) {
                        echo <<<HTML
<label class="flex flex-col items-center bg-gray-50 dark:bg-gray-700 p-4 rounded-xl shadow cursor-pointer">
    <input type="radio" name="metode" value="$value" required class="hidden" onchange="showInfo('$value')">
    <img src="$img" class="w-10 h-10 mb-2" alt="$label"/>
    <span class="text-sm font-semibold text-gray-800 dark:text-white">$label</span>
</label>
HTML;
                    }
                    ?>
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

    <div class="bg-gray-100 dark:bg-gray-800 rounded-xl shadow p-6 border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">📌 Info Pembayaran</h3>
        <div id="info-qris" class="hidden text-center">
            <img src="/img/qr.png" alt="QRIS" class="mx-auto w-48 h-48 mb-4 rounded border border-gray-300">
            <p class="text-sm text-gray-700 dark:text-gray-300">Scan kode QR di atas untuk melakukan pembayaran via QRIS.</p>
        </div>
        <div id="info-dana" class="hidden">
            <ul class="text-sm space-y-2 text-gray-700 dark:text-gray-300">
                <li><strong>DANA:</strong> 0812-3456-7890 a.n. TOKOMARD</li>
                <li>📎 Gunakan nama akun yang sama untuk memudahkan verifikasi</li>
                <li>🔗 <a href="https://link.dana.id/minta" class="text-blue-500 underline" target="_blank">Link DANA</a></li>
            </ul>
        </div>
        <div id="info-bank" class="hidden">
            <ul class="text-sm space-y-2 text-gray-700 dark:text-gray-300">
                <li><strong>Bank:</strong> BCA 1234567890 a.n. TOKOMARD</li>
                <li><strong>Catatan:</strong> Transfer antar bank bisa delay hingga 5 menit.</li>
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

<script>
function showInfo(method) {
    const ids = ['qris', 'dana', 'bank'];
    ids.forEach(id => {
        document.getElementById('info-' + id).classList.add('hidden');
    });
    document.getElementById('info-' + method).classList.remove('hidden');
}
</script>

