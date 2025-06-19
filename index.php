<?php session_start(); ?>
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
  <title>Tokomard</title>
  <link rel="SHORTCUT ICON" href="https://i.imgur.com/q3DzxiB.png">
  <!-- Tailwind CSS CDN -->
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
 
  <!-- Tailwind CSS -->
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

  <!-- AlpineJS for slider -->
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-900 text-white font-sans">

  <!-- Header -->
  <header class="flex items-center justify-between p-4 bg-gray-800 shadow-md">
    <div class="flex items-center space-x-3">
      <img src="https://i.imgur.com/q3DzxiB.png" class="w-10" alt="Logo">
      <h1 class="text-xl font-bold">Tokomard VPN Panel</h1>
    </div>
    <div>
      <a href="login.php" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded text-sm font-semibold">Login</a>
    </div>
  </header>

  <!-- Hero Section -->
  <section class="text-center py-12 px-4 max-w-4xl mx-auto">
    <h2 class="text-3xl md:text-4xl font-bold mb-4">Kelola Trojan & Xray dengan Mudah</h2>
    <p class="text-gray-300 text-lg mb-8">
      Tokomard VPN Panel adalah solusi manajemen layanan tunneling yang mendukung protokol Xray seperti VMess, VLESS, Trojan, Shadowsocks, dan SSH.
    </p>

<!-- Slider dengan auto-slide -->
<div
  x-data="{
    activeSlide: 0,
    slides: [
      'https://i.imgur.com/CX6v5kU.jpeg', 
      'https://i.imgur.com/q3DzxiB.ng', 
      'https://i.imgur.com/8IiXQqY.png'
    ],
    init() {
      if (this.slides.length >1) {
        setInterval(() => {
          this.activeSlide = (this.activeSlide + 1) % this.slides.length;
        }, 5000); // Ganti 5000 ke 3000 untuk 3 detik
      }
    }
  }"
  x-init="init"
  class="relative w-full overflow-hidden rounded-xl shadow-lg max-w-3xl mx-auto"
>
  <div class="flex transition-all duration-700" :style="`transform: translateX(-${activeSlide * 100}%);`">
    <template x-for="slide in slides" :key="slide">
      <img :src="slide" class="w-full object-cover" alt="Preview Gambar">
    </template>
  </div>
  <div class="absolute inset-0 flex items-center justify-between px-4">
    <button @click="activeSlide = (activeSlide - 1 + slides.length) % slides.length" class="text-white text-2xl bg-black bg-opacity-30 hover:bg-opacity-60 p-2 rounded-full">&#10094;</button>
    <button @click="activeSlide = (activeSlide + 1) % slides.length" class="text-white text-2xl bg-black bg-opacity-30 hover:bg-opacity-60 p-2 rounded-full">&#10095;</button>
  </div>
</div>


  <!-- Features -->
  <section class="bg-gray-800 py-12 px-4">
    <div class="max-w-6xl mx-auto grid md:grid-cols-3 gap-8 text-center">
      <div>
        <h3 class="text-xl font-semibold mb-2">🔐 Protokol Lengkap</h3>
        <p class="text-gray-400">Dukungan penuh untuk Xray (VMess, VLESS, Trojan), Shadowsocks dan SSH tunneling.</p>
      </div>
      <div>
        <h3 class="text-xl font-semibold mb-2">⚡ Panel Cepat</h3>
        <p class="text-gray-400">Desain ringan, responsif, dan cepat diakses dari semua perangkat.</p>
      </div>
      <div>
        <h3 class="text-xl font-semibold mb-2">📊 Monitoring Real-time</h3>
        <p class="text-gray-400">Lihat statistik pengguna, log koneksi, dan manajemen bandwidth dengan mudah.</p>
      </div>
      <div>
        <h3 class="text-xl font-semibold mb-2">⚡ Server TerMantap</h3>
        <p class="text-gray-400">Specs gahar, stabil, dan cepat diakses daremua perangkat.</p>
      </div>

    </div>
  </section>

  <!-- Footer -->
  <footer class="text-center text-gray-500 py-6 text-sm">
    &copy; <?= date('Y') ?> Tokomard Panel VPN. Developed by Benjamin Wickman & MrZodoxVpython.
  </footer>
</body>
</html>

