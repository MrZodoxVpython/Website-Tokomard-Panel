<?php
session_start();
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Logout</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
  <style>
    body {
      background: linear-gradient(120deg, #0f0c29, #302b63, #24243e);
      color: #00ffcc;
      font-family: 'Courier New', monospace;
      overflow: hidden;
    }
    .glitch {
      font-size: 2.5rem;
      font-weight: bold;
      position: relative;
      animation: glitch 1s infinite;
    }

    @keyframes glitch {
      0% { text-shadow: 2px 2px #ff00c8, -2px -2px #00fff9; }
      20% { text-shadow: -2px 2px #ff00c8, 2px -2px #00fff9; }
      40% { text-shadow: 2px -2px #ff00c8, -2px 2px #00fff9; }
      60% { text-shadow: -2px -2px #ff00c8, 2px 2px #00fff9; }
      80% { text-shadow: 2px 2px #ff00c8, -2px -2px #00fff9; }
      100% { text-shadow: -2px 2px #ff00c8, 2px -2px #00fff9; }
    }
  </style>
</head>
<body class="flex items-center justify-center min-h-screen px-4">

  <div class="text-center space-y-6">
    <lottie-player
      src="https://assets10.lottiefiles.com/packages/lf20_hbr24n1e.json"
      background="transparent"
      speed="1"
      style="width: 200px; height: 200px; margin: auto;"
      loop
      autoplay>
    </lottie-player>

    <h1 class="glitch">Berhasil Logout</h1>
    <p class="text-gray-300">Terima kasih telah menggunakan <span class="text-pink-400 font-semibold">Tokomard-Xray-Panel</span>.</p>

    <a href="index.php"
       class="inline-block bg-pink-600 hover:bg-pink-700 text-white px-6 py-2 rounded-md transition font-semibold">
      🔁 Kembali ke Beranda
    </a>
  </div>

</body>
</html>

