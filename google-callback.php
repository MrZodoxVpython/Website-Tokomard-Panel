<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'koneksi.php';
require_once 'google-config.php';

if (isset($_GET['code'])) {
    $mode = $_GET['state'] ?? 'login'; // Ambil mode dari query (login/register)

    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

        if (isset($token['error'])) {
            throw new Exception("Google Auth Error: " . $token['error']);
        }

        $client->setAccessToken($token['access_token']);

        $oauth = new Google_Service_Oauth2($client);
        $userInfo = $oauth->userinfo->get();

        $email = $userInfo->email;
        $name  = $userInfo->name;

        // Role berdasarkan domain
        if (strpos($email, '@tokomard.com') !== false) {
            $role = 'admin';
        } else {
            $role = 'reseller';
        }

        // Cek apakah user sudah terdaftar
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

	if ($result->num_rows === 0) {
	    if ($mode === 'register') {
	        // Insert user
	        $username = explode('@', $email)[0];
	        $dummyPass = password_hash(uniqid(), PASSWORD_DEFAULT);
	        $insert = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
	        $insert->bind_param("ssss", $username, $email, $dummyPass, $role);
	        $insert->execute();

	        // Fetch kembali user yang baru dibuat
	        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
	        $stmt->bind_param("s", $email);
	        $stmt->execute();
	        $result = $stmt->get_result();
	    } else {
	        // ✅ Redirect user ke halaman register jika belum terdaftar
		$_SESSION['flash_error'] = "Akun Google Anda belum terdaftar. Silakan daftar terlebih dahulu.";
		header("Location: register.php?google_email=" . urlencode($email) . "&mode=register");
		exit;
	    }
	}

	// Set session
	$user = $result->fetch_assoc();
	$username = $user['username'];

	$_SESSION['login'] = true;
	$_SESSION['username'] = $username;
	$_SESSION['email'] = $email;
	$_SESSION['role'] = $role;

	if ($mode === 'register') {
	    $_SESSION['flash_success'] = "Berhasil mendaftarkan akun Google Anda. Silakan login menggunakan Google.";
	    header("Location: login.php");
	} else {
	    header("Location: reseller/reseller.php");
	}
        exit;

    } catch (Exception $e) {
        echo "<h2>Google Login Gagal</h2>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    }

} else {
    echo "Akses tidak sah.";
    exit;
}

