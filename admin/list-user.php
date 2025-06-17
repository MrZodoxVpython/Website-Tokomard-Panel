<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require '../koneksi.php';

// Ambil semua akun dari tabel users
$result = mysqli_query($conn, "SELECT id, username, role FROM users ORDER BY role DESC, username ASC");

include '../templates/header.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Daftar Akun Pengguna</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen">

<div class="max-w-5xl mx-auto p-4">
  <h1 class="text-3xl font-bold text-blue-400 mb-6">📋 Daftar Akun Terdaftar</h1>

  <!-- Tombol Tambah -->
  <div class="mb-4">
    <a href="tambah-user.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
      ➕ Tambah Akun Baru
    </a>
  </div>

  <!-- Tabel Akun -->
  <div class="overflow-x-auto">
    <table class="min-w-full bg-gray-800 rounded shadow-lg">
      <thead class="bg-gray-700 text-gray-300">
        <tr>
          <th class="p-3 text-left">ID</th>
          <th class="p-3 text-left">Username</th>
          <th class="p-3 text-left">Role</th>
          <th class="p-3 text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = mysqli_fetch_assoc($result)) : ?>
          <tr class="border-b border-gray-700 hover:bg-gray-700">
            <td class="p-3"><?= htmlspecialchars($row['id']) ?></td>
            <td class="p-3"><?= htmlspecialchars($row['username']) ?></td>
            <td class="p-3 capitalize"><?= htmlspecialchars($row['role']) ?></td>
            <td class="p-3 text-center space-x-2">
              <a href="edit-user.php?id=<?= $row['id'] ?>" class="bg-yellow-500 hover:bg-yellow-600 px-3 py-1 rounded">Edit</a>
              <a href="hapus-user.php?id=<?= $row['id'] ?>" onclick="return confirm('Yakin ingin menghapus akun ini?')" class="bg-red-500 hover:bg-red-600 px-3 py-1 rounded">Hapus</a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../templates/footer.php'; ?>
</body>
</html>

