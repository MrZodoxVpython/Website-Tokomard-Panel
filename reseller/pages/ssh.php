<?php
// Contoh data dummy SSH (nanti bisa diganti dengan data real dari database atau API)
$sshAccounts = [
    ['username' => 'user1', 'server' => 'sg.tokomard.store', 'expired' => '2025-07-01'],
    ['username' => 'user2', 'server' => 'us.tokomard.store', 'expired' => '2025-07-05'],
];
?>

<div class="mb-6">
    <h2 class="text-2xl font-semibold mb-2">🔐 Daftar Akun SSH</h2>
    <p class="text-sm text-gray-600 dark:text-gray-400">Berikut adalah akun SSH aktif yang telah dibuat.</p>
</div>

<div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-100 dark:bg-gray-700 text-left text-sm font-medium text-gray-700 dark:text-gray-200">
            <tr>
                <th class="px-4 py-3">Username</th>
                <th class="px-4 py-3">Server</th>
                <th class="px-4 py-3">Expired</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
            <?php foreach ($sshAccounts as $acc): ?>
                <tr>
                    <td class="px-4 py-2"><?= htmlspecialchars($acc['username']) ?></td>
                    <td class="px-4 py-2"><?= htmlspecialchars($acc['server']) ?></td>
                    <td class="px-4 py-2"><?= htmlspecialchars($acc['expired']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="mt-6">
    <a href="#" class="inline-block bg-blue-600 hover:bg-blue-500 text-white text-sm px-4 py-2 rounded shadow transition">
        ➕ Buat Akun SSH Baru
    </a>
</div>

