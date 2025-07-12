<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'koneksi.php';
require_once 'google-config.php';
session_start();
$flash_error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);

$client->setState('register');
$google_login_url = $client->createAuthUrl();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // pastikan sudah install phpmailer

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && empty($_POST['kode_otp'])) {
    $email = $_POST['email'];

    // Generate kode OTP dan simpan ke session
    $otp = rand(100000, 999999);
    $_SESSION['otp_email'] = $email;
    $_SESSION['otp_code'] = $otp;
    $_SESSION['otp_expire'] = time() + 300; // 5 menit valid

    // Kirim email OTP
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Ganti sesuai SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'akunemailkamu@gmail.com'; // Ganti
        $mail->Password = 'passwordaplikasi';         // Ganti
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('akunemailkamu@gmail.com', 'Tokomard Panel');
        $mail->addAddress($email);

        $mail->Subject = 'Kode OTP Pendaftaran';
        $mail->Body    = "Kode OTP Anda: $otp (berlaku 5 menit)";

        $mail->send();
        $_SESSION['flash_error'] = "Kode OTP telah dikirim ke email Anda.";
        header("Location: register.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['flash_error'] = "Gagal mengirim kode OTP: {$mail->ErrorInfo}";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email    = $_POST['email'];
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $error = "Password dan konfirmasi tidak cocok.";
    } else {
        if (strpos($email, '@tokomard.com') !== false) {
            $role = 'admin';
        } elseif (strpos($email, '@reseller.com') !== false) {
            $role = 'reseller';
        } else {
            $error = "Email tidak valid. Gunakan @reseller.com.";
        }

        if (!isset($error)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $hashed, $role);

            if ($stmt->execute()) {
                header("Location: index.php?success=1");
                exit;
            } else {
                $error = "Registrasi gagal: " . $stmt->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register - X-Panel</title>
  <!-- Tailwind CSS CDN -->
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 flex items-center justify-center min-h-screen text-white px-4">

  <div class="bg-gray-800 p-8 rounded-xl shadow-lg w-full max-w-md">
    <h2 class="text-3xl font-bold mb-2 text-center">Buat Akun Baru</h2>
    <p class="text-gray-400 text-center mb-6">Silakan isi data untuk mendaftar</p>

<?php if ($flash_error): ?>
  <div class="bg-yellow-600 text-white p-3 mb-4 rounded-md text-center">
    <?= htmlspecialchars($flash_error) ?>
  </div>
<?php endif; ?>

    <form method="POST" action="" class="space-y-5">
      <div>
        <label for="username" class="block text-sm mb-1">Username</label>
        <input type="text" id="username" name="username" required
               class="w-full px-4 py-2 rounded-md bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <div>
        <label for="email" class="block text-sm mb-1">Email</label>
        <input type="email" id="email" name="email" required
               class="w-full px-4 py-2 rounded-md bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <div>
        <label for="password" class="block text-sm mb-1">Password</label>
        <input type="password" id="password" name="password" required
               class="w-full px-4 py-2 rounded-md bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <div>
        <label for="confirm_password" class="block text-sm mb-1">Konfirmasi Password</label>
        <input type="password" id="confirm_password" name="confirm_password" required
               class="w-full px-4 py-2 rounded-md bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <label for="kode_otp" class="block text-sm mb-1">Masukkan Kode OTP yang dikirim ke email</label>
        <input type="text" id="kode_otp" name="kode_otp" required
         class="w-full px-4 py-2 rounded-md bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      <button type="submit"
              class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 rounded-md transition">
        Daftar
      </button>
    </form>

    <!-- Link kembali ke login -->
    <p class="text-sm text-center text-gray-400 mt-6">
      Sudah punya akun?
      <a href="login.php" class="text-blue-400 hover:underline font-semibold">Login di sini</a>
    </p>
    <div class="mt-6 text-center">
      <p class="text-sm mb-2">Atau daftar dengan</p>
      <a href="google-login.php?mode=register" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md">
      <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/c1/Google_%22G%22_logo.svg/120px-Google_%22G%22_logo.svg.png" class="w-5 h-5 mr-2" alt="Google logo">
    Sign up with Google
      </a>
    </div>

  </div>

</body>
</html>

