<!-- Tombol toggle sidebar untuk mobile -->
<button id="toggleSidebar" class="md:hidden fixed top-4 left-4 z-50 p-2 bg-gray-200 dark:bg-gray-700 rounded-md shadow-md">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-800 dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
    </svg>
</button>

<!-- Sidebar -->
<aside id="sidebar" class="md:w-1/4 w-64 bg-gray-100 dark:bg-gray-800 p-4 rounded-xl shadow-lg fixed md:relative top-0 left-0 h-full md:h-auto z-40 transform -translate-x-full md:translate-x-0 transition-transform duration-300">
    <div class="flex flex-col items-center md:items-start">
        <img src="<?= $loggedInUser['avatar'] ?>" alt="Profile" class="w-24 h-24 rounded-full mb-3">
        <h2 class="text-lg font-semibold text-center md:text-left">@<?= htmlspecialchars($loggedInUser['username']) ?></h2>
    </div>

    <nav class="mt-6 w-full space-y-2">
        <a href="dashboard.php" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">📊 Dashboard</a>
        <a href="ssh.php" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">🔐 SSH</a>
        <a href="vmess.php" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">🌀 Vmess</a>
        <a href="vless.php" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">📡 Vless</a>
        <a href="trojan.php" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">⚔ Trojan</a>
        <a href="shadowsocks.php" class="block px-3 py-2 rounded hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600">🕶 Shadowsocks</a>

        <hr class="my-4 border-gray-400 dark:border-gray-600">
        <a href="topup.php" class="block px-3 py-2 rounded hover:bg-green-500 hover:text-white dark:hover:bg-green-600">💳 Top Up</a>
        <a href="cek-server.php" class="block px-3 py-2 rounded hover:bg-indigo-500 hover:text-white dark:hover:bg-indigo-600">🖥 Cek Online Server</a>
        <a href="grup-vip.php" class="block px-3 py-2 rounded hover:bg-yellow-500 hover:text-white dark:hover:bg-yellow-600">👑 Grup Customer VIP</a>
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

<script>
    const toggleBtn = document.getElementById('toggleSidebar');
    const sidebar = document.getElementById('sidebar');
    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('-translate-x-full');
    });
</script>

