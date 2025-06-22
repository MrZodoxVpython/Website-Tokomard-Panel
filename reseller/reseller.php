<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

// Redirect ke dashboard jika tidak ada parameter page
if (!isset($_GET['page'])) {
    header("Location: ?page=dashboard");
    exit;
}

$loggedInUser = [
    'username' => $_SESSION['username'],
    'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['username']) . '&background=4F46E5&color=fff',
    'services' => ['Vmess', 'Vless', 'Trojan', 'Shadowsocks']
];

$page = basename($_GET['page']); // Aman dari injection
$allowedPages = ['dashboard', 'ssh', 'vmess', 'vless', 'trojan', 'shadowsocks', 'topup', 'cek-server', 'grup-vip'];
if (!in_array($page, $allowedPages)) {
    $page = 'dashboard';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
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
        <button id="themeToggleBtn" class="text-xl p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition">
            🌙
        </button>
        <a href="logout.php" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-500">Logout</a>
    </div>
</header>

<main class="flex flex-col md:flex-row p-4 md:p-6 gap-6">
    <aside class="md:w-1/4 w-64 bg-gray-100 dark:bg-gray-800 p-4 rounded-xl shadow-lg fixed md:relative top-0 left-0 h-full md:h-auto z-40 transform -translate-x-full md:translate-x-0 transition-transform duration-300">
        <div class="flex flex-col items-center text-center w-full">
            <img src="<?= $loggedInUser['avatar'] ?>" alt="Profile" class="w-24 h-24 rounded-full mb-3">
            <h2 class="text-lg font-semibold">@<?= htmlspecialchars($loggedInUser['username']) ?></h2>
        </div>

        <nav class="mt-6 w-full space-y-2">
            <a href="?page=dashboard" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">📊 Dashboard</a>
            <a href="?page=ssh" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">🔐 SSH</a>
            <a href="?page=vmess" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">🌀 Vmess</a>
            <a href="?page=vless" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">📡 Vless</a>
            <a href="?page=trojan" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">⚔ Trojan</a>
            <a href="?page=shadowsocks" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">🕶 Shadowsocks</a>

            <hr class="my-4 border-gray-400 dark:border-gray-600">

            <a href="?page=topup" class="block px-3 py-2 rounded hover:bg-green-500 hover:text-white dark:hover:bg-green-600">💳 Top Up</a>
            <a href="?page=cek-server" class="block px-3 py-2 rounded hover:bg-indigo-500 hover:text-white dark:hover:bg-indigo-600">🖥 Cek Online Server</a>
            <a href="?page=grup-vip" class="block px-3 py-2 rounded hover:bg-yellow-500 hover:text-white dark:hover:bg-yellow-600">👑 Grup Customer VIP</a>
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

    <section class="md:w-3/4 w-full ml-auto">
        <?php
        $targetFile = __DIR__ . '/pages/' . $page . '.php';
        if (file_exists($targetFile)) {
            include $targetFile;
        } else {
            echo "<div class='p-4 text-red-500 font-bold'>Halaman tidak ditemukan!</div>";
        }
        ?>
    </section>
</main>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const html = document.documentElement;
    const themeToggleBtn = document.getElementById('themeToggleBtn');

    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        html.classList.add('dark');
    } else {
        html.classList.remove('dark');
    }

    function updateThemeIcon() {
        themeToggleBtn.textContent = html.classList.contains('dark') ? '🌞' : '🌙';
    }

    updateThemeIcon();

    themeToggleBtn.addEventListener('click', function () {
        const isDark = html.classList.toggle('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        updateThemeIcon();
    });
});
</script>
</body>
</html>

