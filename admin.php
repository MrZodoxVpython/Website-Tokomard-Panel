<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

require 'koneksi.php'; // Koneksi ke database

// Ambil semua akun dengan info server
$query = "
SELECT x.id, s.name AS server_name, x.username, x.protocol, x.uuid_or_pass, x.expired_date, x.status
FROM xray_accounts x
JOIN servers s ON x.server_id = s.id
ORDER BY s.name ASC, x.protocol ASC
";
$result = mysqli_query($conn, $query);

include 'templates/header.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
   <title>Admin Panel - Xray Multi-VPS</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen">
<div class="max-w-7xl mx-auto p-4">
  <h1 class="text-center text-3xl font-bold mb-6 text-blue-400">Administrator kontrol</h1>

  <!-- Menu Admin -->
  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mb-8">
    <a href="admin/shell-access.php" class="bg-fuchsia-600 hover:bg-fuchsia-700 text-white px-4 py-3 rounded-xl text-center shadow">
      ⏳ Shell Access M
    </a>
    <a href="admin/vps-monitoring.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-xl text-center shadow">
      🖥️ VPS Monitoring M
    </a>
    <a href="/cek-status-server-tokomard.php" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-3 rounded-xl text-center shadow">
      🧠 Cek Server M
    </a>
    <a href="admin/backup-restore.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-xl text-center shadow">
      🧑‍💻 Backup & Restore M
    </a>
    <a href="admin/statistik-vps.php" class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-3 rounded-xl text-center shadow">
      📊 Statistik Bandwith M
    </a>
    <a href="admin/statistik.php" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-3 rounded-xl text-center shadow">
      📈 Full Cek User Xray  M
    </a>
      <a href="admin/log-akses.php" class="bg-rose-600 hover:bg-rose-700 text-white px-4 py-3 rounded-xl text-center shadow">
      🧾 Log Akses User
    </a>
    <a href="admin/aktifitas-admin.php" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-3 rounded-xl text-center shadow">
      ⚙️ Aktivitas Admin
    </a>
    <a href="admin/error-report.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-3 rounded-xl text-center shadow">
      ❗ Laporan Error
    </a>
    <a href="admin/statistik-vps.php" class="bg-zinc-700 hover:bg-gray-800 text-white px-4 py-3 rounded-xl text-center shadow">
      📊 Statistik Bandwith M
    </a>
    <a href="admin/pengaturan.php" class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-3 rounded-xl text-center shadow">
      🔧 Pengaturan Sistem
    <a href="admin/list-user.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-3 rounded-xl text-center shadow">
      📋 Registered Users
    </a>
  </div>

  <!-- Tabel Akun Xray -->
  <div class="overflow-x-auto">
    <table class="min-w-full bg-gray-800 rounded-lg shadow-lg">
      <thead class="bg-gray-700 text-gray-300">
        <tr>
          <th class="p-3 text-left">Server</th>
          <th class="p-3 text-left">Username</th>
          <th class="p-3 text-left">Protocol</th>
          <th class="p-3 text-left">UUID / Password</th>
          <th class="p-3 text-left">Expired</th>
          <th class="p-3 text-left">Status</th>
          <th class="p-3 text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php while($row = mysqli_fetch_assoc($result)): ?>
        <tr class="border-b border-gray-700 hover:bg-gray-700">
          <td class="p-3"><?= htmlspecialchars($row['server_name']) ?></td>
          <td class="p-3"><?= htmlspecialchars($row['username']) ?></td>
          <td class="p-3 uppercase"><?= htmlspecialchars($row['protocol']) ?></td>
          <td class="p-3 text-xs break-all"><?= htmlspecialchars($row['uuid_or_pass']) ?></td>
          <td class="p-3"><?= htmlspecialchars($row['expired_date']) ?></td>
          <td class="p-3">
            <span class="px-2 py-1 rounded text-sm <?= $row['status'] === 'active' ? 'bg-green-600' : 'bg-red-600' ?>">
              <?= ucfirst($row['status']) ?>
            </span>
          </td>
          <td class="p-3 text-center space-x-2">
            <a href="edit-akun.php?id=<?= $row['id'] ?>" class="bg-yellow-500 px-3 py-1 rounded hover:bg-yellow-600">Edit</a>
            <a href="hapus-akun.php?id=<?= $row['id'] ?>" onclick="return confirm('Hapus akun ini?')" class="bg-red-500 px-3 py-1 rounded hover:bg-red-600">Hapus</a>
            <?php if ($row['status'] === 'active'): ?>
              <a href="lock-akun.php?id=<?= $row['id'] ?>" class="bg-gray-500 px-3 py-1 rounded hover:bg-gray-600">Lock</a>
            <?php else: ?>
              <a href="unlock-akun.php?id=<?= $row['id'] ?>" class="bg-blue-500 px-3 py-1 rounded hover:bg-blue-600">Unlock</a>
            <?php endif; ?>
          </td>
       </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include 'templates/footer.php'; ?>
</body>
</html>

