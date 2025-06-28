<?php
session_start();

$uploadDir = 'uploads/avatars/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $tmpName = $_FILES['avatar']['tmp_name'];
    $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
    $ext = strtolower($ext);

    // Validasi ekstensi
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        die('❌ Format gambar tidak didukung.');
    }

    // Nama file unik berdasarkan username
    $username = $_SESSION['username'] ?? 'guest';
    $fileName = 'avatar-' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $username) . '.' . $ext;
    $destPath = $uploadDir . $fileName;

    // Simpan file
    if (move_uploaded_file($tmpName, $destPath)) {
    $_SESSION['avatar'] = $destPath;
    header("Location: reseller.php");
    exit;
} else {
    echo "❌ Gagal mengunggah file.<br>";
    echo "Tmp Name: $tmpName<br>";
    echo "Dest Path: $destPath<br>";
    echo "is_uploaded_file: " . (is_uploaded_file($tmpName) ? 'YES' : 'NO') . "<br>";
    echo "Upload Dir Writable: " . (is_writable($uploadDir) ? 'YES' : 'NO') . "<br>";
    error_log(print_r($_FILES, true));
}
} else {
    die('❌ Tidak ada file yang diunggah.');
}

