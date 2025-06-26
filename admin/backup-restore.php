<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

// Daftar VPS (contoh, bisa diambil dari database juga)
$vpsList = [
    ['ip' => '178.128.60.185', 'country' => 'Singapore-SGDO-2DEV'],
    ['ip' => '152.42.182.187', 'country' => 'Singapore-SGDO-MARD1'],
    ['ip' => '203.194.113.140',  'country' => 'Indonesia-RW-MARD']
];

$output = '';
$backupFile = '/root/backup-vpn.tar.gz';

// Cek jika ada form yang disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    $vpsIp = $_POST['vps_ip'] ?? null;
    $password = $_POST['password'] ?? '';

    if ($action && $vpsIp) {
    $isLocal = ($vpsIp === '178.128.60.185');

    if ($action === 'backup') {
        if ($isLocal) {
	    $cmd = "sudo /usr/bin/php /var/www/html/Website-Tokomard-Panel/admin/backup.php";
        } elseif (!empty($password)) {
            $cmd = "sshpass -p '$password' ssh -o StrictHostKeyChecking=no root@$vpsIp 'sudo /usr/bin/backup'";
        } else {
            $cmd = "ssh -o StrictHostKeyChecking=no root@$vpsIp 'sudo /usr/bin/backup'";
        }
    } elseif ($action === 'restore') {
        if ($isLocal) {
            $cmd = "php /var/www/html/Website-Tokomard-Panel/admin/restore.php";
        } elseif (!empty($password)) {
            $cmd = "sshpass -p '$password' ssh -o StrictHostKeyChecking=no root@$vpsIp 'php /var/www/html/Website-Tokomard-Panel/admin/auto-install-rclone.php'";
        } else {
            $cmd = "ssh -o StrictHostKeyChecking=no root@$vpsIp 'php /var/www/html/Website-Tokomard-Panel/admin/auto-install-rclone.php'";
        }
    }

    $output = shell_exec($cmd . " 2>&1");
    }

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

<div class="max-w-6xl mx-auto p-6">
  <h1 class="text-center text-3xl font-bold text-blue-400 mb-6">🧑‍💻 Backup & Restore Tiap VPS</h1>

  <table class="min-w-full text-sm text-left text-white border border-gray-700 mb-8">
    <thead class="bg-gray-800 text-gray-300">
      <tr>
        <th class="px-4 py-3">IP VPS</th>
        <th class="px-4 py-3">Country</th>
        <th class="px-4 py-3">Password (opsional)</th>
        <th class="px-4 py-3 text-center">Aksi</th>
      </tr>
    </thead>
    <tbody>
<?php foreach ($vpsList as $vps): ?>
<tr class="border-t border-gray-700">
  <form method="POST" id="form-<?= $vps['ip'] ?>">
    <td class="px-4 py-3 font-mono"><?= $vps['ip'] ?></td>
    <td class="px-4 py-3"><?= $vps['country'] ?></td>
    <td class="px-4 py-3">
      <input type="password" name="password" placeholder="Password VPS (jika perlu)"
             class="bg-gray-800 border border-gray-600 rounded px-3 py-1 w-full text-sm">
    </td>
    <td class="px-4 py-3 flex gap-2 justify-center">
      <input type="hidden" name="vps_ip" value="<?= $vps['ip'] ?>">
      <button type="submit" name="action" value="backup"
              onclick="setFormAction('<?= $vps['ip'] ?>', 'backup')"
              class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
        🗃 Backup
      </button>
      <button type="submit" name="action" value="restore"
              onclick="setFormAction('<?= $vps['ip'] ?>', 'restore')"
              class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded shadow">
        ♻ Restore
      </button>
    </td>
  </form>
</tr>
<?php endforeach; ?>
    </tbody>
  </table>

  <?php if ($output): ?>
    <div class="bg-gray-800 rounded p-4 mb-4">
      <h2 class="text-lg font-semibold text-green-400 mb-2">📄 Output dari VPS:</h2>
      <pre class="whitespace-pre-wrap text-sm text-gray-200"><?= htmlspecialchars($output) ?></pre>
    </div>
  <?php endif; ?>

  <?php if (file_exists($backupFile)): ?>
    <div class="bg-gray-800 rounded p-4">
      <p class="text-green-300 mb-2">✅ File backup tersedia untuk diunduh:</p>
      <a href="download-backup.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">
        ⬇ Download backup-vpn.tar.gz
      </a>
    </div>
  <?php endif; ?>
</div>

<?php include 'templates/footer.php'; ?>
<script>
function setFormAction(ip, action) {
  const form = document.getElementById('form-' + ip);
  if (ip === '178.128.60.185') {
    form.action = action === 'backup' ? 'backup.php' : 'restore.php';
  } else {
    form.action = 'backup-restore.php';
  }
}
</script>
</body>
</html>

