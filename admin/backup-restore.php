<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

$vpsList = [
    ['ip' => '178.128.60.185', 'country' => 'Singapore-SGDO-2DEV'],
    ['ip' => '152.42.182.187', 'country' => 'Singapore-SGDO-MARD1'],
    ['ip' => '203.194.113.140', 'country' => 'Indonesia-RW-MARD']
];

$localIp = '178.128.60.185';
$output = '';
$backupFile = '/root/backup-vpn.tar.gz';

// Eksekusi backup atau restore
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    $vpsIp = $_POST['vps_ip'] ?? null;
    $token = $_POST['token'] ?? '';

    if ($action && $vpsIp) {
        $isLocal = ($vpsIp === $localIp);

        if ($action === 'backup') {
            if ($isLocal) {
                $cmd = "sudo /usr/bin/php /var/www/html/Website-Tokomard-Panel/admin/backup.php";
            } else {
                if (!empty($token)) {
                    // Simpan token sementara
                    file_put_contents('/tmp/tmp-token.json', $token);

                    // Kirim token ke VPS remote
                    $scpCmd = "scp -o StrictHostKeyChecking=no /tmp/tmp-token.json root@$vpsIp:/tmp/token.json";
                    shell_exec($scpCmd);
                    unlink('/tmp/tmp-token.json');

                    // Jalankan backup di VPS remote
                    $cmd = "ssh -o StrictHostKeyChecking=no root@$vpsIp 'bash /etc/xray/backup.sh'";
                } else {
                    $output = "❌ Token Google Drive belum dimasukkan!";
                }
            }
        } elseif ($action === 'restore') {
            if ($isLocal) {
                $cmd = "php /var/www/html/Website-Tokomard-Panel/admin/restore.php";
            } else {
                $cmd = "ssh -o StrictHostKeyChecking=no root@$vpsIp 'php /var/www/html/Website-Tokomard-Panel/admin/auto-install-rclone.php'";
            }
        }

        if (!empty($cmd)) {
            $output = shell_exec($cmd . " 2>&1");
        }
    }
}
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
  <h1 class="text-center text-3xl font-bold text-blue-400 mb-6">🧑💻 Backup & Restore Tiap VPS</h1>

  <table class="min-w-full text-sm text-left text-white border border-gray-700 mb-8">
    <thead class="bg-gray-800 text-gray-300">
      <tr>
        <th class="px-4 py-3">IP VPS</th>
        <th class="px-4 py-3">Country</th>
        <th class="px-4 py-3">Token Google Drive</th>
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
      <?php if ($vps['ip'] !== $localIp): ?>
      <input type="text" name="token" placeholder="Token Google Drive untuk VPS remote"
             class="bg-gray-800 border border-gray-600 rounded px-3 py-1 w-full text-sm">
      <?php else: ?>
      <input type="text" disabled value="Tidak diperlukan"
             class="bg-gray-700 border border-gray-600 rounded px-3 py-1 w-full text-sm text-gray-400">
      <?php endif; ?>
    </td>
    <td class="px-4 py-3 flex gap-2 justify-center">
      <input type="hidden" name="vps_ip" value="<?= $vps['ip'] ?>">
      <button type="submit" name="action" value="backup"
              onclick="return setFormAction('<?= $vps['ip'] ?>', 'backup')"
              class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
        🗃  Backup
      </button>
      <button type="submit" name="action" value="restore"
              onclick="return setFormAction('<?= $vps['ip'] ?>', 'restore')"
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

<?php
// Deteksi semua file backup dari remote (jika ada)
$remoteDir = __DIR__ . '/backup-from-remote';
$remoteFiles = glob("$remoteDir/*.tar.gz");
?>

<?php if (file_exists($backupFile) || !empty($remoteFiles)): ?>
  <div class="bg-gray-800 rounded p-4 space-y-2">
    <?php if (file_exists($backupFile)): ?>
      <p class="text-green-300">✅ Backup lokal tersedia:</p>
      <a href="download-backup.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">
        ⬇ Download backup-vpn.tar.gz
      </a>
    <?php endif; ?>

    <?php foreach ($remoteFiles as $filePath):
      $fileName = basename($filePath);
      $fileUrl = "backup-from-remote/$fileName";
    ?>
      <p class="text-green-300">✅ Backup remote tersedia: <?= htmlspecialchars($fileName) ?></p>
      <a href="<?= $fileUrl ?>" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded shadow inline-block">
        ⬇ Download <?= htmlspecialchars($fileName) ?>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

</div>

<script>
function setFormAction(ip, action) {
  const form = document.getElementById('form-' + ip);
  const localIp = '178.128.60.185';

  if (ip === localIp) {
    form.action = action === 'backup' ? 'backup.php' : 'restore.php';
  } else {
    form.action = 'backup-restore.php';
  }

  return true;
}
</script>
</body>
</html>

