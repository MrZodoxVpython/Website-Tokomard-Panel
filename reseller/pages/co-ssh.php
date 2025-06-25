<?php
// Data server dummy, bisa disesuaikan dengan URL parameter nanti (misal pakai ?id=1)
$server = [
    'name' => 'DO-3',
    'country' => 'Singapore',
    'isp' => 'DigitalOcean, LLC',
    'rules' => [
        'NO TORRENT',
        'NO MULTY LOGIN',
        'SUPPORT ENHANCED HTTP CUSTOM',
        'Max Login 1 device'
    ],
    'price' => 15000
];
?>

<div class="max-w-2xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 space-y-6">
    <h2 class="text-2xl font-bold mb-2">🛒 Detail Server</h2>

    <div class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
        <p><strong>Server Name</strong> : <?= htmlspecialchars($server['name']) ?></p>
        <p><strong>Country</strong> : <?= htmlspecialchars($server['country']) ?></p>
        <p><strong>ISP</strong> : <?= htmlspecialchars($server['isp']) ?></p>
        <?php foreach ($server['rules'] as $rule): ?>
            <p>🚫 <?= htmlspecialchars($rule) ?></p>
        <?php endforeach; ?>
    </div>

    <hr class="border-gray-300 dark:border-gray-600">

    <h3 class="text-xl font-semibold">🧾 Create Account</h3>

    <form action="proses-order.php" method="POST" class="space-y-4">
        <input type="hidden" name="server" value="<?= htmlspecialchars($server['name']) ?>">

        <div>
            <label class="block text-sm font-medium mb-1">⏳ Expired (Hari)</label>
            <select name="expired" class="w-full rounded border border-gray-300 dark:border-gray-600 px-3 py-2 bg-white dark:bg-gray-700 text-sm">
                <option value="3">3 Hari - Rp<?= number_format($server['price'] * 3 / 30, 0, ',', '.') ?></option>
                <option value="7">7 Hari - Rp<?= number_format($server['price'] * 7 / 30, 0, ',', '.') ?></option>
                <option value="30">30 Hari - Rp<?= number_format($server['price'], 0, ',', '.') ?></option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">👤 Username</label>
            <input type="text" name="username" required placeholder="Masukkan username" class="w-full px-3 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">🔒 Password</label>
            <input type="text" name="password" required placeholder="Masukkan password" class="w-full px-3 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">🎟️ Coupon</label>
            <input type="text" name="coupon" placeholder="(Opsional)" class="w-full px-3 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm">
        </div>

        <div>
            <button type="submit" class="w-full bg-green-600 hover:bg-green-500 text-white py-2 rounded text-sm font-semibold shadow">
                ✅ Checkout & Buat Akun
            </button>
        </div>
    </form>

    <div class="text-center text-xs text-gray-500 mt-6">2025© VPN DAN PAKET DATA XL AXIS</div>
</div>

