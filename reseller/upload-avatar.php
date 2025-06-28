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

