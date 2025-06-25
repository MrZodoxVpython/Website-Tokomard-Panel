<?php
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if (empty($username) || empty($email) || empty($password) || empty($confirm)) {
        die("❌ Semua field wajib diisi!");
    }

    if ($password !== $confirm) {
        die("❌ Password dan konfirmasi tidak cocok!");
    }

    // Validasi email domain
    if (strpos($email, '@tokomard.com') !== false) {
        $role = 'admin';
    } elseif (strpos($email, '@reseller.com') !== false) {
        $role = 'reseller';
    } else {
        die("❌ Email tidak valid. Gunakan domain @tokomard.com atau @reseller.com.");
    }

    $password_hashed = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        die("❌ Gagal mempersiapkan query: " . $conn->error);
    }

    $stmt->bind_param("ssss", $username, $email, $password_hashed, $role);

    if ($stmt->execute()) {
        header("Location: index.php?register=success");
        exit;
    } else {
        die("❌ Registrasi gagal: " . $stmt->error);
    }
}
?>

