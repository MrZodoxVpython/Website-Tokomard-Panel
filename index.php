<?php session_start(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta property="og:title" content="Tokomard Panel VPN - Kelola Trojan & Xray dengan Mudah">
  <meta property="og:description" content="Panel manajemen VPN premium (Xray, VMess, Trojan, VLESS, Shadowsocks, SSH). Cepat, stabil, dan powerfull.">
  <meta property="og:image" content="https://i.imgur.com/q3DzxiB.png">
  <meta property="og:url" content="https://panel.tokomard.store/">
  <meta name="theme-color" content="#1f2937">
  <title>Tokomard Panel VPN</title>
  <link rel="shortcut icon" href="https://i.imgur.com/q3DzxiB.png">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <style>
    ::selection {
      background: #2563eb;
      color: white;
    }
  </style>
</head>
<body class="bg-gray-950 text-white font-sans">

  <!-- Header -->
  <header class="flex justify-between items-center px-6 py-4 bg-gradient-to-r from-indigo-800 via-indigo-900 to-gray-900 shadow-xl">
    <div class="flex items-center space-x-4">
      <img src="https://i.imgur.com/q3DzxiB.png" alt="Logo" class="w-10 h-10 rounded-full shadow">
      <h1 class="text-2xl font-bold tracking-tight">Tokomard VPN Panel</h1>
    </div>
    <a href="login.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-5 rounded-lg transition shadow-md">Login</a>
  </header>

  <!-- Hero -->
  <section class="text-center px-4 py-14 max-w-4xl mx-auto">
    <h2 class="text-4xl md:text-5xl font-extrabold leading-tight mb-4 bg-gradient-to-r from-blue-400 to-purple-500 bg-clip-text text-transparent">Kelola Xray, Trojan & SSH Super Mudah</h2>
    <p class="text-gray-300 text-lg mb-10">Tokomard VPN Panel memberikan kontrol penuh terhadap akun VMess, VLESS, Trojan, Shadowsocks, dan SSH dalam satu panel elegan.</p>

    <!-- Auto-slider -->
    <div
      x-data="{
        active: 0,
        images: [
          'https://i.imgur.com/CX6v5kU.jpeg',
          'https://i.imgur.com/q3DzxiB.png',
          'https://i.imgur.com/8IiXQqY.png'
        ],
        init() {
          setInterval(() => {
            this.active = (this.active + 1) % this.images.length
          }, 4000)
        }
      }"
      class="relative w-full overflow-hidden rounded-xl shadow-2xl max-w-3xl mx-auto"
    >
      <div class="flex transition-all duration-700 ease-in-out" :style="`transform: translateX(-${active * 100}%);`">
        <template x-for="img in images" :key="img">
          <img :src="img" class="w-full object-cover h-64 md:h-80" alt="Slide">
        </template>
      </div>
      <div class="absolute inset-0 flex items-center justify-between px-4">
        <button @click="active = (active - 1 + images.length) % images.length" class="bg-black bg-opacity-40 hover:bg-opacity-60 text-white text-2xl p-2 rounded-full">&#10094;</button>
        <button @click="active = (active + 1) % images.length" class="bg-black bg-opacity-40 hover:bg-opacity-60 text-white text-2xl p-2 rounded-full">&#10095;</button>
      </div>
    </div>
  </section>

  <!-- Features -->
  <section class="bg-gradient-to-b from-gray-900 via-gray-800 to-gray-900 py-16 px-6">
    <div class="max-w-6xl mx-auto grid md:grid-cols-3 gap-10 text-center">
      <div class="bg-gray-800 rounded-lg p-6 shadow-md hover:shadow-lg transition">
        <h3 class="text-xl font-bold mb-2">🔐 Dukungan Lengkap Protokol</h3>
        <p class="text-gray-400">Xray lengkap: VMess, VLESS, Trojan, Shadowsocks + SSH dalam satu dashboard.</p>
      </div>
      <div class="bg-gray-800 rounded-lg p-6 shadow-md hover:shadow-lg transition">
        <h3 class="text-xl font-bold mb-2">⚡ Kecepatan & Responsif</h3>
        <p class="text-gray-400">Desain ringan dan mobile-ready. Panel siap pakai kapan saja.</p>
      </div>
      <div class="bg-gray-800 rounded-lg p-6 shadow-md hover:shadow-lg transition">
        <h3 class="text-xl font-bold mb-2">📊 Statistik Real-time</h3>
        <p class="text-gray-400">Pantau semua akun aktif, trafik, masa aktif, dan status koneksi live.</p>
      </div>
      <div class="bg-gray-800 rounded-lg p-6 shadow-md hover:shadow-lg transition">
        <h3 class="text-xl font-bold mb-2">🎯 Sistem Reseller Pro</h3>
        <p class="text-gray-400">Kelola banyak user sekaligus. Fitur saldo & manajemen terintegrasi.</p>
      </div>
      <div class="bg-gray-800 rounded-lg p-6 shadow-md hover:shadow-lg transition">
        <h3 class="text-xl font-bold mb-2">🛡️ Keamanan Maksimal</h3>
        <p class="text-gray-400">Autentikasi aman, tanpa celah. Bebas dari bug konfigurasi umum.</p>
      </div>
      <div class="bg-gray-800 rounded-lg p-6 shadow-md hover:shadow-lg transition">
        <h3 class="text-xl font-bold mb-2">👨‍💻 Admin & Support 24/7</h3>
        <p class="text-gray-400">Dukungan cepat via WA, Telegram, dan email. Siap bantu setup & restore.</p>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="text-center text-gray-400 text-sm py-8 bg-gray-900 border-t border-gray-700">
    &copy; <?= date('Y') ?> Tokomard VPN Panel. Dibuat dengan ❤️ oleh Benjamin Wickman & MrZodoxVpython.
  </footer>

</body>
</html>

