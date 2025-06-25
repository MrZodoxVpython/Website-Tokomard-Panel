<?php
session_start();
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier']);
    $password = $_POST['password'];

    // Cek apakah input berupa email atau username
    if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
        $query = "SELECT * FROM users WHERE email = ?";
    } else {
        $query = "SELECT * FROM users WHERE username = ?";
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $identifier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role']; // tambahkan role ke session

            // redirect berdasarkan role
	    if ($user['role'] === 'admin') {
                header("Location: admin.php");
            } elseif ($user['role'] === 'reseller') {
                header("Location: reseller.php");
            } else {
                $error = "Role tidak dikenal.";
            }
            exit;
        } else {
            $error = "⚠ Password salah!";
        }
    } else {
        $error = "❌ Username atau email tidak ditemukan!";
    }
}
?>

<?php if (isset($error)): ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Login Gagal</title>
  <meta http-equiv="refresh" content="3;url=login.php">
  <style>
    body {
      background: radial-gradient(ellipse at center, #0f0c29, #302b63, #24243e);
      color: #ff3cac;
      font-family: 'Courier New', monospace;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
      overflow: hidden;
    }
    .error-box {
      text-align: center;
      padding: 40px;
      border: 2px solid #ff3cac;
      border-radius: 15px;
      box-shadow: 0 0 15px #ff3cac;
      animation: flicker 1.5s infinite;
    }
    .error-box h1 {
      font-size: 28px;
      margin-bottom: 10px;
    }
    .error-box p {
      font-size: 16px;
    }
    @keyframes flicker {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.5; }
    }
  </style>
</head>
<body>
  <div class="error-box">
    <h1><?= htmlspecialchars($error) ?></h1>
    <p>Mengalihkan kembali ke halaman login dalam 3 detik...</p>
  </div>
</body>
</html>
<?php endif; ?>

