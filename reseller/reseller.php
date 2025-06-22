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

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$allowedPages = ['dashboard', 'ssh', 'vmess', 'vless', 'trojan', 'shadowsocks', 'topup', 'cek-server', 'grup-vip'];
if (!in_array($page, $allowedPages)) {
    $page = 'dashboard';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Reseller - Tokomard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' };
    </script>
</head>
<body class="bg-white text-gray-900 dark:bg-gray-900 dark:text-white min-h-screen transition-colors duration-300">
<header class="p-4 bg-gray-100 dark:bg-gray-800 shadow-md flex justify-between items-center">
    <h1 class="text-2xl font-bold">Dashboard Reseller</h1>
    <div class="flex items-center gap-3">
        <button onclick="toggleTheme()" class="text-xl p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition">🌙</button>
        <a href="logout.php" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-500">Logout</a>
    </div>
</header>

<main class="flex flex-col md:flex-row p-4 md:p-6 gap-6">
    <aside class="md:w-1/4 w-full md:static fixed top-0 left-0 h-full md:h-auto z-40 bg-gray-100 dark:bg-gray-800 p-4 rounded-xl shadow-lg">
        <div class="flex flex-col items-center">
            <img src="<?= $loggedInUser['avatar'] ?>" alt="Profile" class="w-24 h-24 rounded-full mb-3">
            <h2 class="text-lg font-semibold text-center">@<?= htmlspecialchars($loggedInUser['username']) ?></h2>
        </div>
        <nav class="mt-6 space-y-2">
            <?php foreach ($allowedPages as $p): ?>
                <a href="?page=<?= $p ?>" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">
                    <?= ucfirst(str_replace('-', ' ', $p)) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <section class="md:w-3/4 w-full">
        <?php include "page-loader.php"; ?>
    </section>
</main>

<script>
    function toggleTheme() {
        const html = document.documentElement;
        const isDark = html.classList.toggle('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
    }
    document.addEventListener('DOMContentLoaded', () => {
        const theme = localStorage.getItem('theme') || 'light';
        document.documentElement.classList.toggle('dark', theme === 'dark');
    });
</script>
</body>
</html>
