<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'koneksi.php';
require_once 'google-config.php';
require 'vendor/autoload.php';

use GuzzleHttp\Client;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$flash_error = null;
if (!isset($_POST['email']) || isset($_POST['kode_otp'])) {
    $flash_error = $_SESSION['flash_error'] ?? null;
    unset($_SESSION['flash_error']);
}

$clientGoogle = $client; // Rename biar nggak bentrok
$clientGoogle->setState('register');
$google_login_url = $clientGoogle->createAuthUrl();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && empty($_POST['kode_otp'])) {
    $email = $_POST['email'];

    // ⛔ Validasi domain email HARUS dilakukan SEBELUM OTP dibuat!
    if (strpos($email, '@gmail.com') === false && strpos($email, '@tokomard.com') === false) {
        $_SESSION['flash_error'] = "Email tidak valid. Gunakan @gmail.com atau @tokomard.com!";
        header("Location: register.php");
        exit;
    }

    $otp = rand(100000, 999999);

    // Kirim via API Resend
    $_SESSION['otp_email'] = $email;
    $_SESSION['otp_code'] = $otp;
    $_SESSION['otp_expire'] = time() + 300;

    // Kirim via API Resend
    $client = new Client([
        'base_uri' => 'https://api.resend.com/',
        'headers' => [
            'Authorization' => 're_AwrPwQ6f_Jq7UhMrkmBdSAFSLMHu2r9Ai', // GANTI API KEY MU
            'Content-Type'  => 'application/json',
        ]
    ]);

    try {
        $client->post('emails', [
            'json' => [
                'from' => 'Tokomard Panel <noreply@tokomard.store>', // HARUS verified
                'to' => [$email],
                'subject' => 'Kode OTP Pendaftaran',
                'html' => "<h3>Kode OTP Anda: <strong>$otp</strong></h3><p>Jangan bagikan ke siapa pun. Berlaku 5 menit.</p>",
            ]
        ]);

        echo "OTP sent.";
        exit;
    } catch (Exception $e) {
        echo "Gagal mengirim OTP: " . $e->getMessage();
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Jika hanya kirim email untuk OTP (tanpa kode_otp)
    if (isset($_POST['email']) && empty($_POST['kode_otp'])) {
        $email = $_POST['email'];

        // Validasi domain email
        if (strpos($email, '@gmail.com') === false && strpos($email, '@tokomard.com') === false) {
            $_SESSION['flash_error'] = "Email tidak valid. Gunakan @gmail.com atau @tokomard.com!";
            header("Location: register.php");
            exit;
        }

        $otp = rand(100000, 999999);
        $_SESSION['otp_email'] = $email;
        $_SESSION['otp_code'] = $otp;
        $_SESSION['otp_expire'] = time() + 300;

        // Kirim email via Resend API
        $client = new \GuzzleHttp\Client([
            'base_uri' => 'https://api.resend.com/',
            'headers' => [
                'Authorization' => 're_AwrPwQ6f_Jq7UhMrkmBdSAFSLMHu2r9Ai',
                'Content-Type'  => 'application/json',
            ]
        ]);

        try {
            $client->post('emails', [
                'json' => [
                    'from' => 'Tokomard Panel <noreply@tokomard.store>',
                    'to' => [$email],
                    'subject' => 'Kode OTP Pendaftaran',
                    'html' => "<h3>Kode OTP Anda: <strong>$otp</strong></h3><p>Jangan bagikan ke siapa pun. Berlaku 5 menit.</p>",
                ]
            ]);

            echo "OTP sent.";
            exit;
        } catch (Exception $e) {
            echo "Gagal mengirim OTP: " . $e->getMessage();
            exit;
        }
    }

    // Proses lengkap register setelah user input OTP
    $username = $_POST['username'];
    $email    = $_POST['email'];
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];
    $kode_otp = $_POST['kode_otp'] ?? '';

    if (!preg_match('/@(gmail\.com|tokomard\.com)$/', $email)) {
    	$error = "Email tidak valid. Gunakan hanya @gmail.com atau @tokomard.com!";
    } elseif ($password !== $confirm) {
    	$error = "Password dan konfirmasi tidak cocok.";
    } elseif (strpos($email, '@tokomard.com') !== false) {
        $role = 'admin';
    } else {
        $role = 'reseller';
    }

    // Langsung lempar error jika validasi awal gagal
    if (isset($error)) {
        $_SESSION['flash_error'] = $error;
        header("Location: register.php");
        exit;
    }

    // Validasi OTP
    if (!isset($_SESSION['otp_code'], $_SESSION['otp_email'], $_SESSION['otp_expire']) ||
        $_SESSION['otp_email'] !== $email ||
        $_SESSION['otp_code'] != $kode_otp ||
        time() > $_SESSION['otp_expire']
    ) {
        $error = "Kode OTP salah atau sudah kedaluwarsa.";
        $_SESSION['flash_error'] = $error;
        header("Location: register.php");
        exit;
    }

    // Eksekusi query insert
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $hashed, $role);

    if ($stmt->execute()) {
        unset($_SESSION['otp_code'], $_SESSION['otp_email'], $_SESSION['otp_expire']);
        $_SESSION['flash_success'] = "Berhasil mendaftarkan akun. Silakan login.";
        header("Location: login.php");
        exit;
    } else {
        $_SESSION['flash_error'] = "Registrasi gagal: " . $stmt->error;
        header("Location: register.php");
        exit;
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

<form method="POST" action="" class="space-y-5" id="registerForm">
  <!-- Step 1 -->
  <div id="step1">
    <div>
      <label for="username" class="block text-sm mb-1 mt-2">Username</label>
      <input type="text" id="username" name="username" required
             class="w-full px-4 py-2 rounded-md bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <div>
      <label for="email" class="block text-sm mb-1 mt-2">Email</label>
      <input type="email" id="email" name="email" required
             class="w-full px-4 py-2 rounded-md bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <div>
      <label for="password" class="block text-sm mb-1 mt-2">Password</label>
      <input type="password" id="password" name="password" required
             class="w-full px-4 py-2 rounded-md bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <div>
      <label for="confirm_password" class="block text-sm mb-1 mt-2">Konfirmasi Password</label>
      <input type="password" id="confirm_password" name="confirm_password" required
             class="w-full px-4 py-2 rounded-md bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <button type="button" onclick="kirimOTP()" class="mt-4 w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-md transition">
      Kirim OTP ke Email
    </button>
  </div>

  <!-- Step 2: OTP input -->
  <div id="step2" class="hidden">
    <div>
      <label for="kode_otp" class="block text-sm mb-1">Masukkan Kode OTP yang dikirim ke email</label>
      <input type="text" id="kode_otp" name="kode_otp" required
       class="w-full px-4 py-2 rounded-md bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <button type="submit"
            class="mt-4 w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 rounded-md transition">
      Daftar
    </button>
  </div>
</form>

    <!-- Link kembali ke login -->
    <p class="text-sm text-center text-gray-400 mt-6">
      Sudah punya akun?
      <a href="login.php" class="text-blue-400 hover:underline font-semibold">Login di sini</a>
    </p>
    <div class="mt-6 text-center">
<div class="flex items-center my-4">
  <hr class="flex-grow border-gray-600">
  <span class="px-3 text-sm text-gray-400">Atau</span>
  <hr class="flex-grow border-gray-600">
</div>
      <a href="google-login.php?mode=register" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md">
      <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/c1/Google_%22G%22_logo.svg/120px-Google_%22G%22_logo.svg.png" class="w-5 h-5 mr-2" alt="Google logo">
    Sign up with Google
      </a>
    </div>

  </div>
<script>
function kirimOTP() {
  const username = document.getElementById('username').value.trim();
  const email = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value;
  const confirm = document.getElementById('confirm_password').value;

  if (!username || !email || !password || !confirm) {
    alert("Semua kolom harus diisi!");
    return;
  }

  if (password !== confirm) {
    alert("Password dan konfirmasi tidak cocok.");
    return;
  }

  const formData = new FormData();
  formData.append("email", email);

  fetch("", {
    method: "POST",
    body: formData
  }).then(response => response.text())
    .then(() => {
      // Tampilkan form OTP
      document.getElementById('step1').classList.add('hidden');
      document.getElementById('step2').classList.remove('hidden');

      const notif = document.querySelector('.bg-yellow-600');
      if (notif) notif.remove();
    }).catch(err => {
      alert("Gagal mengirim OTP");
    });
}
</script>

</body>
</html>

