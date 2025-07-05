<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $data = [
        'username' => $username,
        'password' => password_hash($password, PASSWORD_BCRYPT),
        'status' => 'pending',
        'role' => 'reseller',
        'time' => time()
    ];

    $pending = file_exists('pending_reseller.json') ? json_decode(file_get_contents('pending_reseller.json'), true) : [];
    $pending[] = $data;
    file_put_contents('pending_reseller.json', json_encode($pending, JSON_PRETTY_PRINT));

    echo "Registrasi berhasil. Tunggu persetujuan admin.";
    exit;
}
?>
<form method="POST">
  <h2>Daftar Reseller</h2>
  Username: <input type="text" name="username" required><br>
  Password: <input type="password" name="password" required><br>
  <button type="submit">Daftar</button>
</form>

