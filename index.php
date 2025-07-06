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
  <title>Tokomard Panel</title>
  <link rel="shortcut icon" href="https://i.imgur.com/q3DzxiB.png" />
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <style>
    html, body {
      background-color: #0f172a;
      color: #e5e7eb;
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
    input::placeholder, textarea::placeholder {
      color: #6b7280;
      opacity: 1;
    }
    *:focus {
      outline: none;
    }
  </style>
</head>
<body class="bg-gray-950 text-gray-200 font-sans antialiased">

  <!-- Header -->
  <header class="flex justify-between items-center px-4 md:px-6 py-4 bg-gray-900 shadow-md border-b border-gray-800">
    <div class="flex items-center space-x-3">
      <img src="https://i.imgur.com/q3DzxiB.png" alt="Logo" class="w-10 h-10 rounded-full" />
      <h1 class="text-lg sm:text-xl md:text-2xl font-bold">Tokomard Panel</h1>
    </div>
    <a href="login.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm px-4 py-2 rounded shadow-md">Login</a>
  </header>

  <!-- Hero -->
  <section class="text-center px-4 py-12 sm:py-16 max-w-4xl mx-auto">
    <h2 class="text-2xl sm:text-3xl md:text-4xl font-extrabold mb-4 text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-500">
      Kelola VPN Xray & Trojan dengan Mudah
    </h2>
    <p class="text-gray-400 text-base sm:text-lg mb-8">
      Panel modern dan responsif untuk mengelola akun VPN berbasis protokol Xray: VMess, VLESS, Trojan, Shadowsocks, hingga SSH.
    </p>

    <!-- Slider -->
<section class="py-8">
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
          this.active = (this.active + 1) % this.images.length;
        }, 7000);
      }
    }"
    class="relative w-full max-w-6xl mx-auto overflow-hidden rounded-xl shadow-lg"
  >
    <!-- SLIDER TRACK -->
    <div
      class="flex transition-transform duration-700 ease-in-out"
      :style="`transform: translateX(-${active * 100}%); width: ${images.length * 100}%`"
    >
      <template x-for="img in images" :key="img">
        <!-- Per slide -->
        <div class="basis-full flex-shrink-0 flex justify-center items-center">
          <img
            :src="img"
            class="h-auto max-h-[80vh] w-auto max-w-full mx-auto"
            :alt="'Slide ' + img"
          />
        </div>
      </template>
    </div>

    <!-- PANAH -->
    <div class="absolute inset-0 flex items-center justify-between px-4">
      <button
        @click="active = (active - 1 + images.length) % images.length"
        class="text-white text-2xl bg-gray-700 bg-opacity-30 hover:bg-opacity-60 p-2 rounded-full"
      >
        &#10094;
      </button>
      <button
        @click="active = (active + 1) % images.length"
        class="text-white text-2xl bg-gray-700 bg-opacity-30 hover:bg-opacity-60 p-2 rounded-full"
      >
        &#10095;
      </button>
    </div>
  </div>
</section>

  <!-- Features -->
  <section class="bg-gray-900 py-12 px-4 sm:px-6 border-t border-gray-800">
    <div class="max-w-6xl mx-auto grid gap-8 md:grid-cols-2 lg:grid-cols-3 text-center">
      <div class="bg-gray-800 rounded-lg p-6 hover:shadow-lg transition">
        <h3 class="text-lg font-semibold text-blue-400 mb-2">🔐 Dukungan Protokol Lengkap</h3>
        <p class="text-gray-400">Kelola VMess, VLESS, Trojan, Shadowsocks & SSH dalam satu dashboard.</p>
      </div>
      <div class="bg-gray-800 rounded-lg p-6 hover:shadow-lg transition">
        <h3 class="text-lg font-semibold text-green-400 mb-2">⚡ Ringan & Responsif</h3>
        <p class="text-gray-400">Akses cepat dari mobile atau desktop, tidak perlu install aplikasi.</p>
      </div>
      <div class="bg-gray-800 rounded-lg p-6 hover:shadow-lg transition">
        <h3 class="text-lg font-semibold text-purple-400 mb-2">📊 Statistik Real-time</h3>
        <p class="text-gray-400">Pantau akun aktif, expired, trafik harian dan performa server.</p>
      </div>
      <div class="bg-gray-800 rounded-lg p-6 hover:shadow-lg transition">
        <h3 class="text-lg font-semibold text-yellow-400 mb-2">🎯 Sistem Reseller</h3>
        <p class="text-gray-400">Sistem saldomatic dan manajemen pelanggan terintegrasi penuh.</p>
      </div>
      <div class="bg-gray-800 rounded-lg p-6 hover:shadow-lg transition">
        <h3 class="text-lg font-semibold text-pink-400 mb-2">🛡️ Keamanan Optimal</h3>
        <p class="text-gray-400">Tidak ada log sembarangan. Konfigurasi aman, terenkripsi dan efisien.</p>
      </div>
      <div class="bg-gray-800 rounded-lg p-6 hover:shadow-lg transition">
        <h3 class="text-lg font-semibold text-red-400 mb-2">👨‍💻 Dukungan 24/7</h3>
        <p class="text-gray-400">Tim support cepat tanggap untuk bantu setup, restore, hingga migrasi.</p>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="text-center text-gray-500 py-6 text-sm border-t border-gray-800 bg-gray-950">
    &copy; <?= date('Y') ?> Tokomard Panel VPN. Dibuat oleh Benjamin Wickman & MrZodoxVpython.
  </footer>

</body>
</html>

