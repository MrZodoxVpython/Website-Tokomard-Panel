<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'koneksi.php';
require_once 'google-config.php';
session_start();

$google_login_url = $client->createAuthUrl();

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
  <title>Login Tokomard</title>
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
</head>
<body class="bg-gray-900 flex items-center justify-center min-h-screen text-white px-4">
  <div class="bg-gray-800 p-8 rounded-xl shadow-lg w-full max-w-md">
  <div class="text-center mb-4">
  <img src="https://i.imgur.com/8IiXQqY.png" alt="Logo" class="mx-auto mb-4 w-15">
  <h2 class="text-3xl font-bold mt-2"></h2>
  </div>

    <p class="text-gray-400 text-center mb-6">Silakan login ke akun Anda</p>
    
    <form method="POST" action="auth.php" class="space-y-5">
      <div>
        <label for ="identifier" class="block text-sm mb-1">Username/Email</label>
        <input type="text" name="identifier" id="identifier" required placeholder="Masukkan username atau email"
               class="w-full px-4 py-2 rounded-md bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <div>
        <label for="password" class="block text-sm mb-1">Password</label>
        <input type="password" name="password" id="password" required placeholder="Masukkan password"
               class="w-full px-4 py-2 rounded-md bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <button type="submit"
              class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-md transition">
        Login
      </button>
    </form>

    <!-- Tambahan: link ke halaman register -->
    <p class="text-sm text-center text-gray-400 mt-6">
      Belum punya akun?
      <a href="register.php" class="text-blue-400 hover:underline font-semibold">Daftar di sini</a>
    </p>
  </div>

</body>
</html>

