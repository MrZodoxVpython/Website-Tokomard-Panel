<?php
include 'koneksi.php';
session_start();

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
            $error = "Email tidak valid. Gunakan @tokomard.com atau @reseller.com.";
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

    <?php if (isset($error)): ?>
      <div class="bg-red-600 text-white p-3 mb-4 rounded-md text-center">
        <?= htmlspecialchars($error) ?>
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
      <button type="submit"
              class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 rounded-md transition">
        Daftar
      </button>
    </form>

    <!-- Link kembali ke login -->
    <p class="text-sm text-center text-gray-400 mt-6">
      Sudah punya akun?
      <a href="indox.php" class="text-blue-400 hover:underline font-semibold">Login di sini</a>
    </p>
  </div>

</body>
</html>

