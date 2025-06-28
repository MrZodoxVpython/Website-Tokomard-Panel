<?php
session_start();

$uploadDir = __DIR__ . '/uploads/avatars/';
$relativePath = 'uploads/avatars/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Validasi apakah ada file diunggah
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    die("❌ Tidak ada file yang diunggah atau terjadi kesalahan upload.");
}

$tmpName = $_FILES['avatar']['tmp_name'];
$originalName = $_FILES['avatar']['name'];
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$size = $_FILES['avatar']['size'];

// 🔒 Validasi ekstensi file gambar
$allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
if (!in_array($ext, $allowedExts)) {
    die("❌ Format gambar tidak didukung. Hanya JPG, JPEG, PNG, GIF, WEBP.");
}

// 🔒 Validasi ukuran maksimal 5MB
$maxSize = 5 * 1024 * 1024; // 5 MB
if ($size > $maxSize) {
    die("❌ Ukuran gambar melebihi 5MB.");
}

// 🔒 Validasi benar-benar gambar
$imageCheck = @getimagesize($tmpName);
if ($imageCheck === false) {
    die("❌ File bukan gambar yang valid.");
}

// 🔒 Username dari session
$username = $_SESSION['username'] ?? 'guest';
$safeUsername = preg_replace('/[^a-zA-Z0-9_\-]/', '', $username);

// Simpan sebagai PNG dengan nama tetap
$destFilename = "avatar-" . $safeUsername . ".png";
$destPath = $uploadDir . $destFilename;
$webPath = $relativePath . $destFilename;

// 🧠 Convert ke PNG jika bukan PNG
switch ($ext) {
    case 'jpeg':
    case 'jpg':
        $srcImage = imagecreatefromjpeg($tmpName);
        break;
    case 'png':
        $srcImage = imagecreatefrompng($tmpName);
        break;
    case 'gif':
        $srcImage = imagecreatefromgif($tmpName);
        break;
    case 'webp':
        $srcImage = imagecreatefromwebp($tmpName);
        break;
    default:
        die("❌ Format gambar tidak didukung.");
}

// 🖼️ Simpan sebagai PNG, menimpa file lama
if ($srcImage && imagepng($srcImage, $destPath)) {
    imagedestroy($srcImage);
    $_SESSION['avatar'] = $webPath;
    header("Location: reseller.php");
    exit;
} else {
    die("❌ Gagal menyimpan gambar avatar.");
}
function tampilkanCyberpunkError($pesan) {
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Error - Cyberpunk Panel</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body {
      background: radial-gradient(ellipse at center, #0f0f0f 0%, #000000 100%);
      font-family: 'Courier New', monospace;
      color: #00ff99;
      animation: flicker 1.5s infinite alternate;
    }
    @keyframes flicker {
      from { opacity: 1; }
      to { opacity: 0.8; }
    }
    .neon-border {
      border: 2px solid #ff00ff;
      box-shadow: 0 0 10px #ff00ff, 0 0 40px #00ffff, 0 0 80px #00ffff;
    }
    .error-text {
      color: #ff0066;
      text-shadow: 0 0 5px #ff0066, 0 0 20px #ff00cc;
    }
  </style>
</head>
<body class="flex items-center justify-center min-h-screen">
  <div class="bg-black p-6 rounded-xl neon-border max-w-lg w-[90%] text-center">
    <h1 class="text-2xl font-bold mb-4 error-text">⚠️ SYSTEM ALERT</h1>
    <pre class="text-green-400 text-sm font-mono whitespace-pre-wrap leading-relaxed animate-pulse">$pesan</pre>
    <a href="reseller.php" class="inline-block mt-6 px-4 py-2 bg-pink-600 text-white rounded hover:bg-pink-800 transition">🔙 Kembali ke Dashboard</a>
  </div>
</body>
</html>
HTML;
    exit;
}

