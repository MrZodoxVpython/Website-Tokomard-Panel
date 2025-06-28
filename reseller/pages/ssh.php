<?php
$sshProducts = [
    [
        'name' => 'SGDO-2DEV',
        'flag' => '🇸🇬',
        'price' => 20000,
        'country' => 'Singapura',
        'isp' => 'DigitalOcean',
        'available' => true,
        'stock' => 12
    ],
    [
        'name' => 'RW-MARD',
        'flag' => '🇮🇩',
        'price' => 20000,
        'country' => 'Indonesia',
        'isp' => 'FCCDN',
        'available' => false,
        'stock' => 0
    ],
    [
        'name' => 'SGDO-MARD',
        'flag' => '🇸🇬',
        'price' => 15000,
        'country' => 'Indonesia',
        'isp' => 'DigitalOcean',
        'available' => false,
        'stock' => 0
    ],
];
?>

<div class="mb-6">
    <h2 class="text-2xl font-semibold mb-2">🌐 Daftar Produk SSH</h2>
    <p class="text-sm text-gray-600 dark:text-gray-400">Pilih produk SSH yang tersedia sesuai lokasi dan kebutuhan Anda.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($sshProducts as $product): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold mb-1"><?= htmlspecialchars($product['name']) ?></h3>
            <div class="text-3xl"><?= $product['flag'] ?></div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">💳 Harga: <strong>Rp<?= number_format($product['price'], 0, ',', '.') ?>/bulan</strong></p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">🌍 Negara: <?= htmlspecialchars($product['country']) ?></p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">🏢 ISP: <?= htmlspecialchars($product['isp']) ?></p>
            <p class="text-sm mb-1 <?= $product['available'] ? 'text-blue-600' : 'text-red-500' ?>">
                <?= $product['available'] ? '✅ Tersedia' : '❌ Tidak Tersedia' ?>
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">📦 Stok: <?= $product['stock'] ?></p>
            
            <div class="flex gap-2">
                <a href="#" class="px-3 py-1 bg-blue-600 hover:bg-blue-500 text-white text-xs rounded shadow">🔍 Lihat Detail</a>
                <a href="/reseller/pages/co-ssh.php" class="px-3 py-1 bg-green-600 hover:bg-green-500 text-white text-xs rounded shadow <?= $product['available'] ? '' : 'opacity-50 pointer-events-none' ?>">🛒 Keranjang</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="mt-6">
    <a href="index.php" class="inline-block bg-indigo-600 hover:bg-indigo-500 text-white text-sm px-4 py-2 rounded shadow transition">
        ➕  Register
    </a>
</div>

