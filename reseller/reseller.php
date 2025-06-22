<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

// Dummy user login
$loggedInUser = [
    'username' => 'reseller123',
    'avatar' => 'https://ui-avatars.com/api/?name=Reseller+123&background=4F46E5&color=fff',
    'services' => ['Vmess', 'Vless', 'Trojan', 'Shadowsocks']
];

// Data VPS dan akun dummy (ganti dengan database/API)
$vpsList = [
    [
        'name' => 'SGP VPS 1',
        'ip' => '192.168.1.101',
        'accounts' => [
            ['username' => 'user1', 'type' => 'vmess', 'expired' => '2025-07-01'],
            ['username' => 'user2', 'type' => 'vless', 'expired' => '2025-06-28'],
        ]
    ],
    [
        'name' => 'IDN VPS 2',
        'ip' => '192.168.1.102',
        'accounts' => [
            ['username' => 'idnuser1', 'type' => 'trojan', 'expired' => '2025-07-03'],
        ]
    ]
];
?>

<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Reseller - Tokomard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <header class="p-4 bg-gray-800 shadow-md flex justify-between items-center">
        <h1 class="text-2xl font-bold">Dashboard Reseller</h1>
        <a href="logout.php" class="px-4 py-2 bg-red-600 rounded hover:bg-red-500">
            Logout
        </a>
    </header>

    <main class="flex flex-col md:flex-row p-6 gap-6">
        <!-- Sidebar kiri -->
        <aside class="md:w-1/4 bg-gray-800 p-4 rounded-xl shadow-lg">
            <div class="flex flex-col items-center">
                <img src="<?= $loggedInUser['avatar'] ?>" alt="Profile" class="w-24 h-24 rounded-full mb-3">
                <h2 class="text-lg font-semibold">@<?= htmlspecialchars($loggedInUser['username']) ?></h2>
            </div>
            <div class="mt-6">
                <h3 class="text-sm font-semibold text-gray-400 mb-2">Service Tersedia</h3>
                <ul class="list-disc list-inside text-sm text-gray-300">
                    <?php foreach ($loggedInUser['services'] as $service): ?>
                        <li><?= htmlspecialchars($service) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>

        <!-- Konten utama -->
        <section class="md:w-3/4">
            <h2 class="text-xl font-semibold mb-6">Daftar VPS & Akun</h2>
            <div class="grid grid-cols-1 gap-6">
                <?php foreach ($vpsList as $vps): ?>
                    <div class="bg-gray-800 p-5 rounded-xl shadow-lg">
                        <div class="mb-3">
                            <h3 class="text-lg font-bold">🖥️ <?= htmlspecialchars($vps['name']) ?></h3>
                            <p class="text-sm text-gray-400">IP: <?= htmlspecialchars($vps['ip']) ?></p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="bg-gray-700">
                                        <th class="text-left p-2">Username</th>
                                        <th class="text-left p-2">Tipe</th>
                                        <th class="text-left p-2">Expired</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vps['accounts'] as $acc): ?>
                                        <tr class="border-b border-gray-600 hover:bg-gray-700">
                                            <td class="p-2"><?= htmlspecialchars($acc['username']) ?></td>
                                            <td class="p-2"><?= strtoupper(htmlspecialchars($acc['type'])) ?></td>
                                            <td class="p-2 text-red-400"><?= htmlspecialchars($acc['expired']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <footer class="p-4 text-center text-sm text-gray-400">
        &copy; <?= date('Y') ?> Tokomard Xray Panel
    </footer>
</body>
</html>
