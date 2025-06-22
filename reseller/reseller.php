<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

// Dummy user login (gunakan dari session/database)
$loggedInUser = [
    'username' => $_SESSION['username'],
    'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['username']) . '&background=4F46E5&color=fff',
    'services' => ['Vmess', 'Vless', 'Trojan', 'Shadowsocks']
];

// Dummy data VPS dan akun
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
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Dashboard Reseller - Tokomard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        };
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const theme = localStorage.getItem('theme') || 'light';
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
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
            <button onclick="toggleTheme()" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-500 text-sm">
                Ganti Tema
            </button>
            <a href="logout.php" class="px-4 py-2 bg-red-600 rounded hover:bg-red-500">
                Logout
            </a>
        </div>
    </header>

    <main class="flex flex-col md:flex-row p-4 md:p-6 gap-6">
        <!-- Sidebar kiri -->
        <aside class="md:w-1/4 bg-gray-100 dark:bg-gray-800 p-4 rounded-xl shadow-lg">
            <div class="flex flex-col items-center">
                <img src="<?= $loggedInUser['avatar'] ?>" alt="Profile" class="w-24 h-24 rounded-full mb-3">
                <h2 class="text-lg font-semibold">@<?= htmlspecialchars($loggedInUser['username']) ?></h2>
            </div>
            <div class="mt-6">
                <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">Service Tersedia</h3>
                <ul class="list-disc list-inside text-sm text-gray-800 dark:text-gray-300">
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
                    <div class="bg-gray-100 dark:bg-gray-800 p-5 rounded-xl shadow-lg">
                        <div class="mb-3">
                            <h3 class="text-lg font-bold">🖥️ <?= htmlspecialchars($vps['name']) ?></h3>
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

