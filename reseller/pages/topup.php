<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'reseller') {
    header("Location: ../index.php");
    exit;
}
?>
<div class="mb-6">
    <h2 class="text-2xl font-semibold mb-2">💸 Formulir Topup Saldo</h2>
    <p class="text-sm text-gray-600 dark:text-gray-400">Silakan isi form berikut untuk melakukan topup saldo akun Anda.</p>
</div>

<div class="overflow-x-auto">
    <form action="proses_topup.php" method="POST" class="w-full max-w-2xl bg-white dark:bg-gray-800 rounded-xl shadow p-6 border border-gray-200 dark:border-gray-700">
        <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300 mb-4">
            <tbody>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <td class="py-2 pr-4 font-medium w-1/3">Nominal Topup</td>
                    <td class="py-2">
                        <input type="number" name="nominal" min="1000" required
                            class="w-full p-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-black dark:text-white">
                    </td>
                </tr>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <td class="py-2 pr-4 font-medium">Metode Pembayaran</td>
                    <td class="py-2">
                        <select name="metode" required
                            class="w-full p-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-black dark:text-white">
                            <option value="">-- Pilih Metode --</option>
                            <option value="QRIS">🧾 QRIS</option>
                            <option value="Dana">📱 Dana</option>
                            <option value="OVO">📱 OVO</option>
                            <option value="Gopay">📱 GoPay</option>
                            <option value="Bank Transfer">🏦 Bank Transfer</option>
                        </select>
                    </td>
                </tr>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <td class="py-2 pr-4 font-medium">Catatan / Referensi</td>
                    <td class="py-2">
                        <textarea name="catatan" rows="3"
                            class="w-full p-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-black dark:text-white"
                            placeholder="Contoh: Transfer dari BCA a/n Andi, jam 10:15 WIB"></textarea>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="flex justify-end gap-2 mt-4">
            <a href="/reseller/pages/index.php"
                class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-500 transition">⬅️ Kembali</a>
            <button type="submit"
                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-500 transition">💳 Submit Topup</button>
        </div>
    </form>
</div>

