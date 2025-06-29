<?php session_start(); ?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta property="og:title" content="Tokomard VPN Panel - Kelola Trojan & Xray dengan Mudah" />
  <meta property="og:description" content="Panel manajemen VPN premium (Xray, VMess, Trojan, VLESS, Shadowsocks, SSH). Cepat, stabil, dan powerfull." />
  <meta property="og:image" content="https://i.imgur.com/q3DzxiB.png" />
  <meta property="og:url" content="https://panel.tokomard.store/" />
  <meta name="theme-color" content="#0f172a" />
  <title>Tokomard VPN Panel</title>
  <link rel="shortcut icon" href="https://i.imgur.com/q3DzxiB.png" />
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <style>
    html, body {
      background-color: #0f172a !important;
      color: #e5e7eb !important;
    }
    *:focus {
      outline: none !important;
      box-shadow: none !important;
    }
    ::selection {
      background: #2563eb;
      color: white;
    }
    ::-webkit-scrollbar {
      width: 8px;
    }
    ::-webkit-scrollbar-track {
      background: #1e293b;
    }
    ::-webkit-scrollbar-thumb {
      background-color: #374151;
      border-radius: 4px;
    }
    img {
      background-color: #0f172a;
    }
    input::placeholder,
    textarea::placeholder {
      color: #6b7280;
      opacity: 1;
    }
    button, a {
      outline: none !important;
    }
  </style>
</head>
<body class="bg-gray-950 text-gray-200 font-sans">

  <!-- Header -->
  <header class="flex justify-between items-center px-6 py-4 bg-gray-900 border-b border-gray-800 shadow-md">
    <div class="flex items-center space-x-4">
      <img src="https://i.imgur.com/q3DzxiB.png" alt="Logo" class="w-10 h-10 rounded-full bg-gray-900" />
      <h1 class="text-2xl font-bold tracking-tight text-white">Tokomard VPN Panel</h1>
    </div>
    <a href="login.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-5 rounded-lg transition duration-200 shadow">Login</a>
  </header>

  <!-- Hero -->
  <section class="text-center px-4 py-16 max-w-4xl mx-auto">
    <h2 class="text-4xl md:text-5xl font-extrabold mb-4 text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-500">Kelola Xray, Trojan & SSH Super Mudah</h2>
    <p class="text-gray-400 text-lg mb-10">Tokomard VPN Panel memberikan kontrol penuh terhadap akun VMess, VLESS, Trojan, Shadowsocks, dan SSH dalam satu panel elegan.</p>

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
          setInterval(() => this.active = (this.active + 1) % this.images.length, 4000)
        }
      }"
      class="relative w-full overflow-hidden rounded-xl shadow-xl max-w-3xl mx-auto bg-gray-900"
    >
      <div class="flex transition-all duration-700 ease-in-out" :style="`transform: translateX(-${active * 100}%);`">
        <template x-for="img in images" :key="img">
          <img :src="img" class="w-full object-cover h-64 md:h-80 bg-gray-900" alt="Slide" />
        </template>
      </div>
      <div class="absolute inset-0 flex items-center justify-between px-4">
        <button @click="active = (active - 1 + images.length) % images.length" class="bg-gray-800 bg-opacity-60 hover:bg-opacity-80 text-white text-2xl p-2 rounded-full">&#10094;</button>
        <button @click="active = (active + 1) % images.length" class="bg-gray-800 bg-opacity-60 hover:bg-opacity-80 text-white text-2xl p-2 rounded-full">&#10095;</button>
      </div>
    </div>
  </section>

  <!-- Features -->
  <section class="py-16 px-6 bg-gray-900 border-t border-gray-800">
    <div class="max-w-6xl mx-auto grid md:grid-cols-3 gap-10 text-center">
      <div class="bg-gray-800 rounded-lg p-6 shadow hover:shadow-lg transition">
        <h3 class="text-xl font-semibold text-blue-400 mb-2">🔐 Dukungan Lengkap Protokol</h3>
        <p class="text-gray-400">VMess, VLESS, Trojan, Shadowsocks dan SSH dalam satu panel.</p>
      </div>
      <div class="bg-gray-800 rounded-lg p-6 shadow hover:shadow-lg transition">
        <h3 class="text-xl font-semibold text-green-400 mb-2">⚡ Kecepatan & Responsif</h3>
        <p class="text-gray-400">Desain ringan dan mobile-ready. Panel cepat dan efisien.</p>
      </div>
      <div class="bg-gray-800 rounded-lg p-6 shadow hover:shadow-lg transition">
        <h3 class="text-xl font-semibold text-purple-400 mb-2">📊 Statistik Real-time</h3>
        <p class="text-gray-400">Pantau akun, trafik, masa aktif dan status secara real-time.</p>
      </div>
      <div class="bg-gray-800 rounded-lg p-6 shadow hover:shadow-lg transition">
        <h3 class="text-xl font-semibold text-yellow-400 mb-2">🎯 Sistem Reseller Pro</h3>
        <p class="text-gray-400">Kelola banyak user sekaligus. Fitur saldo & kontrol penuh.</p>
      </div>
      <div class="bg-gray-800 rounded-lg p-6 shadow hover:shadow-lg transition">
        <h3 class="text-xl font-semibold text-pink-400 mb-2">🛡️ Keamanan Maksimal</h3>
        <p class="text-gray-400">Autentikasi aman, manajemen akun terenkripsi, tanpa celah.</p>
      </div>
      <div class="bg-gray-800 rounded-lg p-6 shadow hover:shadow-lg transition">
        <h3 class="text-xl font-semibold text-red-400 mb-2">👨‍💻 Admin & Support 24/7</h3>
        <p class="text-gray-400">Dukungan siap pakai via WhatsApp, Telegram, dan Email.</p>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="text-center text-gray-500 text-sm py-8 border-t border-gray-800 bg-gray-950">
    &copy; <?= date('Y') ?> Tokomard VPN Panel. Dibuat dengan ❤️ oleh Benjamin Wickman & MrZodoxVpython.
  </footer>

</body>
</html>

