<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta property="og:title" content="Tokomard Panel VPN - Kelola Trojan & Xray dengan Mudah">
  <meta property="og:description" content="Panel untuk manajemen SSH, Xray (VLESS, VMess, Trojan, Shadowsocks).">
  <meta property="og:image" content="https://i.imgur.com/q3DzxiB.png">
  <meta property="og:url" content="https://panel.tokomard.store/">
  <meta property="og:type" content="website">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tokomard</title>
  <link rel="SHORTCUT ICON" href="https://i.imgur.com/q3DzxiB.png">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "Tokomard VPN Panel",
  "operatingSystem": "Linux",
  "applicationCategory": "DeveloperApplication",
  "description": "Panel web untuk mengelola akun VPN berbasis Xray.",
  "url": "https://tokomard.com/",
  "author": {
    "@type": "Person",
    "name": "Benjamin Wickman"
  }
}
  </script>
</head>
<body class="bg-gray-900 text-white min-h-screen flex flex-col">

<!-- NAVBAR -->
<header class="bg-gray-800 shadow-md">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-16">
      <!-- Logo / Judul -->
      <div class="flex-shrink-0 flex items-center space-x-3">
  	<img src="https://i.imgur.com/q3DzxiB.png" class="w-10 h-10" alt="Logo">
  	<a href="dashboard.php" class="text-white text-xl font-bold tracking-tight hover:text-blue-400 transition">
    	Panel Tokomard
 	</a>
      </div>

      <!-- Menu Desktop -->
      <nav class="hidden md:flex items-center space-x-6">
        <a href="/dashboard.php" class="text-gray-300 hover:text-white transition">Beranda</a>
        <a href="/reseller/pesan.php" class="text-gray-300 hover:text-white transition">Broadcast</a>
        <a href="/reseller/saldo/topup.php" class="text-gray-300 hover:text-white transition">Topup</a>
        <a href="/reseller/reseller.php" class="text-gray-300 hover:text-white transition">Reseller</a>
        <span class="text-sm text-gray-400">👤 <?php echo htmlspecialchars($_SESSION['username']); ?></span>
        <a href="/logout.php" class="text-red-400 hover:underline font-semibold">🔓 Logout</a>
      </nav>

      <!-- Menu Mobile Toggle -->
      <div class="md:hidden">
        <button id="menu-toggle" class="text-gray-300 hover:text-white focus:outline-none">
          <!-- Hamburger Icon -->
          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
               viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>
      </div>
    </div>
  </div>

  <!-- Menu Mobile -->
  <div id="mobile-menu" class="md:hidden hidden px-4 pb-4 space-y-2">
    <a href="/dashboard.php" class="block text-gray-300 hover:text-white">Beranda</a>
    <a href="/#.php" class="block text-gray-300 hover:text-white">xXx</a>
    <a href="/reseller.php" class="block text-gray-300 hover:text-white">Reseller</a>
    <a href="/logout.php" class="block text-red-400 hover:text-red-600">Logout</a>
  </div>
</header>

<!-- Main Start -->
<main class="flex-grow container mx-auto px-4 py-6">

