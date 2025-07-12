<?php
session_start();
require_once 'google-config.php';
include 'koneksi.php';

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (!isset($token['error'])) {
        $client->setAccessToken($token['access_token']);
        $oauth = new Google_Service_Oauth2($client);
        $user = $oauth->userinfo->get();

        $email = $user->email;
        $username = explode('@', $email)[0];

        // Cek apakah user sudah terdaftar
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            // Belum ada, insert user
            $role = (strpos($email, '@tokomard.com') !== false) ? 'admin' : 'reseller';
            $password = password_hash(uniqid(), PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $password, $role);
            $stmt->execute();
        }

        // Set session
        $_SESSION['email'] = $email;
        $_SESSION['username'] = $username;

        // Arahkan ke dashboard
        header("Location: reseller/reseller.php");
        exit;
    } else {
        echo "Login Google gagal: " . htmlspecialchars($token['error']);
    }
} else {
    echo "Tidak ada kode Google OAuth diterima.";
}

