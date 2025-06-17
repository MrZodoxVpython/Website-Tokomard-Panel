<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Jalankan backup/restore jika ada aksi
$action = $_POST['action'] ?? null;
$output = '';
$backupFile = '/root/backup-vpn.tar.gz';

if ($action === 'backup') {
    $output = shell_exec("sudo /usr/bin/backup 2>&1");
} elseif ($action === 'restore') {
    $output = shell_exec("sudo /usr/bin/restore 2>&1");
}

include 'templates/header.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Backup & Restore - Panel Xray</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen">

<div class="max-w-4xl mx-auto p-6">
  <h1 class="text-3xl font-bold text-blue-400 mb-6">🧑💻 Backup & Restore Data VPN</h1>

  <!-- Menu Form -->
  <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
    <button type="submit" name="action" value="backup"
      class="bg-green-600 hover:bg-green-700 px-6 py-4 rounded-xl text-white text-xl shadow text-center">
      🗃 Backup Sekarang
    </button>

    <button type="submit" name="action" value="restore"
      class="bg-yellow-500 hover:bg-yellow-600 px-6 py-4 rounded-xl text-white text-xl shadow text-center">
      ♻️ Restore dari Backup
    </button>
    <h2 href="auto-install-rclone.php"
      class="bg-green-600 hover:bg-green-700 px-6 py-4 rounded-xl text-white text-xl shadow text-center">
      🗃 Install rclone
    </h2>

  </form>

  <!-- Hasil Eksekusi -->
  <?php if ($output): ?>
    <div class="bg-gray-800 rounded p-4 mb-4">
      <h2 class="text-lg font-semibold text-green-400 mb-2">📄 Output Terminal:</h2>
      <pre class="whitespace-pre-wrap text-sm text-gray-200"><?= htmlspecialchars($output) ?></pre>
    </div>
  <?php endif; ?>

  <!-- Link Download jika backup tersedia -->
  <?php if (file_exists($backupFile)): ?>
    <div class="bg-gray-800 rounded p-4">
      <p class="text-green-300 mb-2">✅ File backup tersedia untuk diunduh:</p>
      <a href="download-backup.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">
        ⬇️ Download backup-vpn.tar.gz
      </a>
    </div>
  <?php endif; ?>
</div>

<?php include 'templates/footer.php'; ?>
</body>
</html>

