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
        <button onclick="toggleTheme()" class="text-xl p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition">🌙</button>
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
            <?php
            $pages = [
                "dashboard" => "📊 Dashboard",
                "ssh" => "🔐 SSH",
                "vmess" => "🌀 Vmess",
                "vless" => "📡 Vless",
                "trojan" => "⚔ Trojan",
                "shadowsocks" => "🕶 Shadowsocks",
                "topup" => "💳 Top Up",
                "cek-server" => "🖥 Cek Online Server",
                "grup-vip" => "👑 Grup Customer VIP"
            ];
            foreach ($pages as $key => $label): ?>
                <a href="?page=<?= $key ?>" data-page="<?= $key ?>" onclick="loadPage(event)" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600"><?= $label ?></a>
            <?php endforeach; ?>
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

<!-- JavaScript -->
<script>
function toggleTheme() {
    const html = document.documentElement;
    const isDark = html.classList.toggle('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
}

document.addEventListener('DOMContentLoaded', () => {
    const theme = localStorage.getItem('theme') || 'light';
    document.documentElement.classList.toggle('dark', theme === 'dark');

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
        .catch(err => {
            document.getElementById('content').innerHTML = '<div class="text-red-500">Halaman gagal dimuat.</div>';
        });
}
</script>
</body>
</html>

