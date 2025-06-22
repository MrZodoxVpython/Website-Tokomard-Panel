<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$loggedInUser = [
    'username' => $_SESSION['username'],
    'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['username']) . '&background=4F46E5&color=fff'
];

$page = isset($_GET['page']) ? basename($_GET['page']) : 'dashboard';
$pagePath = "pages/{$page}.php";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Panel Reseller - Tokomard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' };
    </script>
    <script>
        function updateThemeIcon() {
            const html = document.documentElement;
            const isDark = html.classList.contains('dark');
            const btn = document.getElementById('themeToggleBtn');
            if (btn) {
                btn.textContent = isDark ? '🌞' : '🌙';
            }
        }

        function toggleTheme() {
            const html = document.documentElement;
            const isDark = html.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            updateThemeIcon();
        }

        document.addEventListener('DOMContentLoaded', () => {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.classList.toggle('dark', theme === 'dark');
            updateThemeIcon();
        });
    </script>
</head>
<body class="bg-white text-gray-900 dark:bg-gray-900 dark:text-white transition-colors duration-300 min-h-screen">
    <!-- Header -->
    <header class="p-4 bg-gray-100 dark:bg-gray-800 shadow-md flex justify-between items-center sticky top-0 z-50">
        <h1 class="text-xl font-bold">Tokomard Reseller Panel</h1>
        <div class="flex items-center gap-4">
            <button id="themeToggleBtn" onclick="toggleTheme()" class="text-xl p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                🌙
            </button>
            <a href="../logout.php" class="px-3 py-2 bg-red-600 text-white rounded hover:bg-red-500 text-sm">Logout</a>
        </div>
    </header>

    <!-- Mobile Sidebar Toggle -->
    <button id="toggleSidebar" class="md:hidden fixed top-4 left-4 z-50 p-2 bg-gray-200 dark:bg-gray-700 rounded-md shadow-md">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-800 dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>

    <!-- Main Layout -->
    <main class="flex flex-col md:flex-row w-full px-4 md:px-8 py-6 gap-6">
        <!-- Sidebar -->
        <aside id="sidebar" class="md:w-1/5 w-full md:max-w-xs bg-gray-100 dark:bg-gray-800 p-4 shadow-lg rounded-lg transition-transform duration-300 -translate-x-full md:translate-x-0 z-40">
            <div class="flex flex-col items-center text-center mb-6">
                <img src="<?= $loggedInUser['avatar'] ?>" alt="Profile" class="w-20 h-20 rounded-full mb-2">
                <h2 class="text-base font-semibold">@<?= htmlspecialchars($loggedInUser['username']) ?></h2>
            </div>

            <nav class="space-y-2 text-sm">
                <a href="?page=dashboard" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">📊 Dashboard</a>
                <a href="?page=ssh" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">🔐 SSH</a>
                <a href="?page=vmess" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">🌀 Vmess</a>
                <a href="?page=vless" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">📡 Vless</a>
                <a href="?page=trojan" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">⚔ Trojan</a>
                <a href="?page=shadowsocks" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">🕶 Shadowsocks</a>

                <hr class="my-4 border-gray-400 dark:border-gray-600">

                <a href="?page=topup" class="block px-3 py-2 rounded hover:bg-green-500 hover:text-white dark:hover:bg-green-600">💳 Top Up</a>
                <a href="?page=cek-server" class="block px-3 py-2 rounded hover:bg-indigo-500 hover:text-white dark:hover:bg-indigo-600">🖥 Cek Server</a>
                <a href="?page=vip" class="block px-3 py-2 rounded hover:bg-yellow-500 hover:text-white dark:hover:bg-yellow-600">👑 Grup VIP</a>
            </nav>
        </aside>

        <!-- Konten Utama -->
        <section class="flex-1 p-6 bg-white dark:bg-gray-900 rounded-xl shadow-md">
            <?php
            if (file_exists($pagePath)) {
                include $pagePath;
            } else {
                echo "<div class='text-center text-red-500'>Halaman tidak ditemukan: {$page}.php</div>";
            }
            ?>
        </section>
    </main>

    <script>
        const toggleBtn = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebar');
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
        });
    </script>
</body>
</html>

