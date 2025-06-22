<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

$loggedInUser = [
    'username' => $_SESSION['username'],
    'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['username']) . '&background=4F46E5&color=fff',
    'services' => ['Vmess', 'Vless', 'Trojan', 'Shadowsocks']
];

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

$accountStats = [
    'ssh' => 5,
    'trojan' => 1,
    'vmess' => 1,
    'vless' => 1,
    'shadowsocks' => 2,
    'orders' => 12
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Dashboard Reseller - Tokomard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = { darkMode: 'class' };
        document.addEventListener('DOMContentLoaded', () => {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.classList.toggle('dark', theme === 'dark');
        });
        function toggleTheme() {
            const html = document.documentElement;
            const isDark = html.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        }
    </script>
</head>
<body class="bg-white text-gray-900 dark:bg-gray-900 dark:text-white min-h-screen transition-colors duration-300">
<header class="p-4 bg-gray-100 dark:bg-gray-800 shadow-md flex justify-between items-center">
    <h1 class="text-2xl font-bold">Dashboard Reseller</h1>
    <div class="flex items-center gap-3">
        <button onclick="toggleTheme()" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-500 text-sm">Ganti Tema</button>
        <a href="logout.php" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-500">Logout</a>
    </div>
</header>

<main class="flex flex-col md:flex-row p-4 md:p-6 gap-6">
    <?php include 'sidebar.php'; ?>

    <section class="md:w-3/4 space-y-6">
        <h2 class="text-xl font-semibold">Statistik Akun</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($accountStats as $key => $val): ?>
                <div class="bg-gray-100 dark:bg-gray-800 p-4 rounded-lg shadow-md">
                    <h3 class="text-lg font-bold capitalize">Total <?= ucfirst($key) ?> Account</h3>
                    <p class="text-2xl mt-2 font-semibold"><?= $val ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-md">
            <h3 class="text-lg font-bold mb-4">Grafik Order Per Bulan</h3>
            <canvas id="orderChart" height="120"></canvas>
            <script>
                new Chart(document.getElementById('orderChart'), {
                    type: 'bar',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                        datasets: [{
                            label: 'Order',
                            data: [2, 3, 4, 2, 5, 6],
                            backgroundColor: 'rgba(79, 70, 229, 0.7)'
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            </script>
        </div>

        <div class="bg-yellow-100 dark:bg-yellow-800 p-4 rounded-lg">
            <h4 class="font-semibold">📢 Notification Admin</h4>
            <p class="text-sm mt-2">Silakan hubungi admin untuk update layanan atau kendala teknis lainnya.</p>
        </div>

        <div class="space-y-4">
            <h2 class="text-xl font-semibold">Daftar VPS & Akun</h2>
            <?php foreach ($vpsList as $vps): ?>
                <div class="bg-gray-100 dark:bg-gray-800 p-5 rounded-xl shadow-lg">
                    <div class="mb-3">
                        <h3 class="text-lg font-bold">🖥 <?= htmlspecialchars($vps['name']) ?></h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">IP: <?= htmlspecialchars($vps['ip']) ?></p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-200 dark:bg-gray-700">
                                    <th class="text-left p-2">Username</th>
                                    <th class="text-left p-2">Tipe</th>
                                    <th class="text-left p-2">Expired</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vps['accounts'] as $acc): ?>
                                    <tr class="border-b border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <td class="p-2"><?= htmlspecialchars($acc['username']) ?></td>
                                        <td class="p-2"><?= strtoupper(htmlspecialchars($acc['type'])) ?></td>
                                        <td class="p-2 text-red-600 dark:text-red-400"><?= htmlspecialchars($acc['expired']) ?></td>
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

<footer class="p-4 text-center text-sm text-gray-500 dark:text-gray-400">
    &copy; <?= date('Y') ?> Tokomard Xray Panel
</footer>
</body>
</html>

