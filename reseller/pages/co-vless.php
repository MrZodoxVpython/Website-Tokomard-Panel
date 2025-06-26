<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Server & Buat Akun</title>
    <!-- Tailwind CDN (dengan dark mode class) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-white min-h-screen p-6">

<?php
// Data server dummy (bisa dinamis dari parameter GET atau DB)
$server = [
    'name' => 'SGDO-2DEV',
    'country' => 'Singapore',
    'isp' => 'DigitalOcean, LLC',
    'rules' => [
        'NO TORRENT',
        'NO MULTY LOGIN',
        'SUPPORT ENHANCED HTTP CUSTOM',
        'Max Login 1 device'
    ],
    'price' => 20000
];

// Ambil protokol dari URL (default: ssh)
$protocol = $_GET['proto'] ?? 'vless';
$require_password = in_array($protocol, ['ssh', 'trojan', 'shadowsocks']);
$require_uuid = in_array($protocol, ['vmess', 'vless']);
?>

<div class="max-w-2xl mx-auto bg-white dark:bg-gray-900 shadow-md rounded-2xl p-6 space-y-6 border border-gray-200 dark:border-gray-700">
    <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-2">🛒 Detail Server</h2>

    <div class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
        <p><strong>Server Name</strong>: <?= htmlspecialchars($server['name']) ?></p>
        <p><strong>Country</strong>: <?= htmlspecialchars($server['country']) ?></p>
        <p><strong>ISP</strong>: <?= htmlspecialchars($server['isp']) ?></p>
        <?php foreach ($server['rules'] as $rule): ?>
            <p>🚫 <?= htmlspecialchars($rule) ?></p>
        <?php endforeach; ?>
    </div>

    <hr class="border-gray-300 dark:border-gray-600">

    <h3 class="text-xl font-semibold text-gray-800 dark:text-white">🧾 Buat Akun <?= strtoupper($protocol) ?></h3>

    <form action="/reseller/pages/api-akun/add-vless.php" method="POST" class="space-y-4">
        <input type="hidden" name="server" value="<?= htmlspecialchars($server['name']) ?>">
        <input type="hidden" name="protocol" value="<?= htmlspecialchars($protocol) ?>">

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">⏳ Expired (Hari)</label>
            <select name="expired" class="w-full rounded border border-gray-300 dark:border-gray-600 px-3 py-2 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white">
                <option value="3">3 Hari - Rp<?= number_format($server['price'] * 3 / 30, 0, ',', '.') ?></option>
                <option value="7">7 Hari - Rp<?= number_format($server['price'] * 7 / 30, 0, ',', '.') ?></option>
                <option value="30">30 Hari - Rp<?= number_format($server['price'], 0, ',', '.') ?></option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">👤 Username</label>
            <input type="text" name="username" required placeholder="Masukkan username" class="w-full px-3 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white">
        </div>

        <?php if ($require_password): ?>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">🔒 Password</label>
            <input type="text" name="password" required placeholder="Masukkan password" class="w-full px-3 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white">
        </div>
        <?php elseif ($require_uuid): ?>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">🧬 UUID</label>
            <input type="text" name="uuid" required placeholder="Masukkan UUID (jika tidak auto)" class="w-full px-3 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white">
        </div>
        <?php endif; ?>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">🎟 Coupon</label>
            <input type="text" name="coupon" placeholder="(Opsional)" class="w-full px-3 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white">
        </div>

        <div>
            <button type="submit" class="w-full bg-green-600 hover:bg-green-500 text-white py-2 rounded text-sm font-semibold shadow transition">
                ✅ Checkout & Buat Akun
            </button>
        </div>
    </form>

    <div class="text-center text-xs text-gray-500 dark:text-gray-500 mt-6">2025© TOKOMARD.CORP NETWORKING</div>
</div>

</body>
</html>

