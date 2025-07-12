<?php
session_start();
require_once 'koneksi.php';
require_once 'google-config.php';

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (!isset($token['error'])) {
        $client->setAccessToken($token['access_token']);

        $oauth = new Google_Service_Oauth2($client);
        $userInfo = $oauth->userinfo->get();

        $email = $userInfo->email;
        $name = $userInfo->name;

        // Tentukan role
        if (strpos($email, '@tokomard.com') !== false) {
            $role = 'admin';
        } else {
            $role = 'reseller'; // semua domain lain jadi reseller
        }

        // Cek apakah user sudah ada
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            // User belum ada → insert
            $username = explode('@', $email)[0];
            $defaultPass = password_hash(uniqid(), PASSWORD_DEFAULT);
            $stmtInsert = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmtInsert->bind_param("ssss", $username, $email, $defaultPass, $role);
            $stmtInsert->execute();
        } else {
            $user = $result->fetch_assoc();
            $username = $user['username'];
        }

        // Simpan sesi login
        $_SESSION['login'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['role'] = $role;

        header("Location: reseller/reseller.php");
        exit;
    } else {
        echo "Terjadi kesalahan saat otentikasi: " . htmlspecialchars($token['error']);
    }
} else {
    header("Location: index.php");
    exit;
}

