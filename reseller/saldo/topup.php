<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require '../../koneksi.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak");
}

// Ambil semua reseller
$resellers = [];
$result = $conn->query("SELECT username FROM users WHERE role = 'reseller'");
while ($row = $result->fetch_assoc()) {
    $resellers[] = $row['username'];
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Top Up Saldo</title>
</head>
<body>
  <h2>Top Up Saldo Reseller</h2>
  <form method="POST" action="proses_topup.php">
    <label>Username Reseller:</label>
    <select name="username">
      <?php foreach ($resellers as $reseller): ?>
        <option value="<?= htmlspecialchars($reseller) ?>"><?= htmlspecialchars($reseller) ?></option>
      <?php endforeach; ?>
    </select><br><br>

    <label>Jumlah Top Up:</label>
    <input type="number" name="jumlah" required><br><br>

    <button type="submit">Top Up</button>
  </form>
</body>
</html>

