<?php
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email    = $_POST['email'];
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if ($password !== $confirm) {
        die("❌ Password dan konfirmasi tidak sama!");
    }

    $password_hashed = password_hash($password, PASSWORD_DEFAULT);
    $role = (strpos($email, '@tokomard.com') !== false) ? 'admin' : 'user';

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        die("Query error: " . $conn->error);
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

