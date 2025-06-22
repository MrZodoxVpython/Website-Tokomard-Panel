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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Reseller - Tokomard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' };
    </script>
</head>
<body class="bg-white text-gray-900 dark:bg-gray-900 dark:text-white min-h-screen transition-colors duration-300">
<header class="p-4 bg-gray-100 dark:bg-gray-800 shadow-md flex justify-between items-center">
    <h1 class="text-2xl font-bold">Dashboard Reseller</h1>
    <div class="flex items-center gap-3">
        <button id="themeToggle" onclick="toggleTheme()" class="text-xl p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition">
            🌙
        </button>
        <a href="logout.php" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-500">Logout</a>
    </div>
</header>

<main class="flex flex-col md:flex-row p-4 md:p-6 gap-6">
    <!-- Sidebar -->
    <aside id="sidebar" class="md:w-1/4 w-64 bg-gray-100 dark:bg-gray-800 p-4 rounded-xl shadow-lg fixed md:relative top-0 left-0 h-full md:h-auto z-40 transform -translate-x-full md:translate-x-0 transition-transform duration-300">
        <div class="flex flex-col items-center text-center w-full">
            <img src="<?= $loggedInUser['avatar'] ?>" alt="Profile" class="w-24 h-24 rounded-full mb-3">
            <h2 class="text-lg font-semibold">@<?= htmlspecialchars($loggedInUser['username']) ?></h2>
        </div>

        <nav class="mt-6 w-full space-y-2">
            <a href="?page=dashboard" data-page="dashboard" onclick="loadPage(event)" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">📊 Dashboard</a>
            <a href="?page=ssh" data-page="ssh" onclick="loadPage(event)" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">🔐 SSH</a>
            <a href="?page=vmess" data-page="vmess" onclick="loadPage(event)" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">🌀 Vmess</a>
            <a href="?page=vless" data-page="vless" onclick="loadPage(event)" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">📡 Vless</a>
            <a href="?page=trojan" data-page="trojan" onclick="loadPage(event)" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">⚔ Trojan</a>
            <a href="?page=shadowsocks" data-page="shadowsocks" onclick="loadPage(event)" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">🕶 Shadowsocks</a>

            <hr class="my-4 border-gray-400 dark:border-gray-600">

            <a href="?page=topup" data-page="topup" onclick="loadPage(event)" class="block px-3 py-2 rounded hover:bg-green-500 hover:text-white dark:hover:bg-green-600">💳 Top Up</a>
            <a href="?page=cek-server" data-page="cek-server" onclick="loadPage(event)" class="block px-3 py-2 rounded hover:bg-indigo-500 hover:text-white dark:hover:bg-indigo-600">🖥 Cek Online Server</a>
            <a href="?page=grup-vip" data-page="grup-vip" onclick="loadPage(event)" class="block px-3 py-2 rounded hover:bg-yellow-500 hover:text-white dark:hover:bg-yellow-600">👑 Grup Customer VIP</a>
        </nav>

        <div class="mt-6 hidden md:block">
            <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">Service Tersedia</h3>
            <ul class="list-disc list-inside text-sm text-gray-800 dark:text-gray-300">
                <?php foreach ($loggedInUser['services'] as $service): ?>
                    <li><?= htmlspecialchars($service) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </aside>

    <!-- Konten kanan -->
    <section class="md:w-3/4 w-full md:ml-0 ml-64">
        <div id="content" class="space-y-4">Memuat halaman...</div>
    </section>
</main>

<!-- Script -->
<script>
function toggleTheme() {
    const html = document.documentElement;
    const isDark = html.classList.toggle('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    document.getElementById('themeToggle').textContent = isDark ? '☀️' : '🌙';
}

document.addEventListener('DOMContentLoaded', () => {
    const theme = localStorage.getItem('theme') || 'light';
    const html = document.documentElement;
    const isDark = theme === 'dark';
    html.classList.toggle('dark', isDark);
    document.getElementById('themeToggle').textContent = isDark ? '☀️' : '🌙';

    const params = new URLSearchParams(window.location.search);
    const page = params.get('page') || 'dashboard';
    loadPage(null, page);
});

function loadPage(event, customPage = null) {
    if (event) event.preventDefault();
    const page = customPage || event.currentTarget.getAttribute('data-page');

    fetch('page-loader.php?page=' + page)
        .then(res => {
            if (!res.ok) throw new Error('Gagal memuat halaman');
            return res.text();
        })
        .then(html => {
            document.getElementById('content').innerHTML = html;
            history.pushState(null, '', '?page=' + page);
        })
        .catch(() => {
            document.getElementById('content').innerHTML = '<div class="text-red-500">Halaman gagal dimuat.</div>';
        });
}
</script>
</body>
</html>

