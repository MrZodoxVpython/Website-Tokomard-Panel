<?php
session_start();

// ✅ Tampilkan error saat development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Folder tujuan upload
$uploadDir = __DIR__ . '/uploads/avatars/';
$relativePath = 'uploads/avatars/';

// Cek dan buat folder jika belum ada
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Validasi file terunggah
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    tampilkanCyberpunkError("❌ Tidak ada file diunggah atau terjadi kesalahan saat upload.");
}

$tmpName = $_FILES['avatar']['tmp_name'];
$originalName = $_FILES['avatar']['name'];
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$size = $_FILES['avatar']['size'];

// 🔐 Hanya izinkan ekstensi tertentu
$allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
if (!in_array($ext, $allowedExts)) {
    tampilkanCyberpunkError("❌ Format gambar tidak didukung. Hanya JPG, JPEG, PNG, GIF, WEBP.");
}

// 🔐 Maksimum ukuran 5MB
$maxSize = 5 * 1024 * 1024; // 5 MB
if ($size > $maxSize) {
    tampilkanCyberpunkError("❌ Ukuran gambar melebihi batas 5MB.");
}

// 🔐 Validasi benar-benar gambar
$imageCheck = @getimagesize($tmpName);
if ($imageCheck === false) {
    tampilkanCyberpunkError("❌ File bukan gambar valid.");
}

// 🔐 Username sebagai nama file
$username = $_SESSION['username'] ?? 'guest';
$safeUsername = preg_replace('/[^a-zA-Z0-9_\-]/', '', $username);

// Tujuan nama file
$destFilename = "avatar-" . $safeUsername . ".png";
$destPath = $uploadDir . $destFilename;
$webPath = $relativePath . $destFilename;

// 🔄 Hapus avatar lama jika ada
if (file_exists($destPath)) {
    unlink($destPath);
}

// 🔄 Convert ke PNG jika bukan PNG
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
        tampilkanCyberpunkError("❌ Format gambar tidak didukung. Hanya JPG, JPEG, PNG, GIF, WEBP.");
}

// ✔️ Buat gambar truecolor dengan transparansi
$width = imagesx($srcImage);
$height = imagesy($srcImage);
$finalImage = imagecreatetruecolor($width, $height);

// Aktifkan transparansi
imagealphablending($finalImage, false);
imagesavealpha($finalImage, true);

// Isi latar belakang dengan transparan
$transparent = imagecolorallocatealpha($finalImage, 0, 0, 0, 127);
imagefilledrectangle($finalImage, 0, 0, $width, $height, $transparent);

// Salin gambar asli ke gambar final
imagecopy($finalImage, $srcImage, 0, 0, 0, 0, $width, $height);

// Simpan sebagai PNG, menimpa file lama
if (imagepng($finalImage, $destPath)) {
    imagedestroy($srcImage);
    imagedestroy($finalImage);
    // ✅ Simpan path avatar ke session
$_SESSION['avatar'] = $webPath;

// ✅ Simpan juga ke file avatar.json agar permanen
$avatarDataFile = __DIR__ . '/uploads/avatar.json';
$avatarData = [];

// Baca file JSON jika sudah ada
if (file_exists($avatarDataFile)) {
    $avatarData = json_decode(file_get_contents($avatarDataFile), true);
}

// Perbarui avatar user saat ini
$avatarData[$safeUsername] = $webPath;

// Simpan kembali ke file JSON
file_put_contents($avatarDataFile, json_encode($avatarData, JSON_PRETTY_PRINT));

// Redirect ke reseller.php
header("Location: reseller.php");
exit;

    } else {
    tampilkanCyberpunkError("❌ Gagal menyimpan gambar avatar.");
}

// 🧠 Fungsi Error Cyberpunk
function tampilkanCyberpunkError($pesan) {
    tampilCyberpunk("❌ ERROR", $pesan, "bg-red-900 border-red-600", "reseller.php");
}

// 🧠 Fungsi Sukses Cyberpunk
function tampilkanCyberpunkSukses($imgPath) {
    $msg = "✅ Avatar berhasil diunggah!\n\nKlik tombol untuk kembali ke dashboard.";
    tampilCyberpunk("✅ SUKSES", $msg, "bg-green-900 border-green-600", "reseller.php", $imgPath);
}

// 💫 Fungsi Tampilan Cyberpunk
function tampilCyberpunk($title, $pesan, $bgStyle, $backLink, $img = null) {
    echo <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>$title - Avatar Upload</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body {
      background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
      font-family: 'Courier New', monospace;
      color: #00ffff;
    }
    .cyber-border {
      border: 2px dashed #0ff;
      box-shadow: 0 0 20px #0ff, 0 0 60px #0ff inset;
    }
    .glow {
      animation: glow 2s infinite alternate;
    }
    @keyframes glow {
      from { text-shadow: 0 0 10px #0ff; }
      to { text-shadow: 0 0 20px #ff0, 0 0 30px #f0f; }
    }
  </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
  <div class="rounded-lg $bgStyle text-center p-6 cyber-border w-full max-w-md">
    <h1 class="text-2xl font-bold glow mb-4">$title</h1>
    <pre class="whitespace-pre-wrap text-sm leading-relaxed mb-4">$pesan</pre>
HTML;

    if ($img) {
        echo "<img src='$img' alt='Avatar' class='w-24 h-24 rounded-full mx-auto mb-4 border-2 border-white'>";
    }

    echo <<<HTML
    <a href="$backLink" class="inline-block px-5 py-2 bg-cyan-700 text-white font-semibold rounded hover:bg-cyan-900 transition">🔁 Kembali</a>
  </div>
</body>
</html>
HTML;
    exit;
}

