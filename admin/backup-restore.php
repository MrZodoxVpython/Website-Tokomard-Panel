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
$backupFile = '/root/backup-vpn.tar.gz';
$output = '';

// Tangani download file .tar.gz langsung di file ini
if (isset($_GET['download']) && preg_match('/^[\w\-]+\.tar\.gz$/', $_GET['download'])) {
    $filename = $_GET['download'];
    $filepath = __DIR__ . "/backup-from-remote/$filename";
    if (file_exists($filepath)) {
        // Tandai bahwa file ini sudah di-download (disembunyikan nanti)
        $_SESSION['downloaded'][$filename] = true;

        header('Content-Description: File Transfer');
        header('Content-Type: application/gzip');
        header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }
}

// Eksekusi backup/restore
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    $vpsIp  = $_POST['vps_ip'] ?? null;
    $token  = $_POST['token'] ?? '';
    if ($action && $vpsIp) {
        $isLocal = ($vpsIp === $localIp);
        $cmd = '';

        if ($action === 'backup') {
            if ($isLocal) {
                $cmd = "sudo /usr/bin/php /var/www/html/Website-Tokomard-Panel/admin/backup.php";
            } else {
                if (!empty($token)) {
                    file_put_contents('/tmp/tmp-token.json', $token);
                    shell_exec("scp -o StrictHostKeyChecking=no /tmp/tmp-token.json root@$vpsIp:/tmp/token.json");
                    unlink('/tmp/tmp-token.json');
                    $cmd = "ssh -o StrictHostKeyChecking=no root@$vpsIp 'bash /etc/xray/backup.sh'";
                } else {
                    $output = "❌ Token Google Drive belum dimasukkan!";
                }
            }
        } elseif ($action === 'restore') {
            $cmd = $isLocal
                ? "php /var/www/html/Website-Tokomard-Panel/admin/restore.php"
                : "ssh -o StrictHostKeyChecking=no root@$vpsIp 'php /var/www/html/Website-Tokomard-Panel/admin/auto-install-rclone.php'";
        }

        if (!empty($cmd)) {
            $output = shell_exec($cmd . " 2>&1");
        }

        // Reset status download agar tombol download muncul lagi
        if ($action === 'backup') {
            $basename = $vpsIp === $localIp ? 'backup-vpn.tar.gz' : ($vpsIp === '203.194.113.140' ? 'RW-MARD.tar.gz' : 'SGDO-MARD1.tar.gz');
            unset($_SESSION['downloaded'][$basename]);
        }
    }
    // ⛔ Redirect agar reload tidak mengulang backup/restore
    $_SESSION['last_output'] = $output;
    header("Location: backup-restore.php");
    exit;
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
  <h1 class="text-center text-3xl font-bold text-blue-400 mb-6">🧑💻 Backup & Restore VPS</h1>

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
      <input type="text" name="token" placeholder="Token Google Drive"
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
        🗃 Backup
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

<?php if (isset($_SESSION['last_output'])): ?>
  <div class="bg-gray-800 rounded p-4 mb-4">
    <h2 class="text-lg font-semibold text-green-400 mb-2">📄 Output dari VPS:</h2>
    <pre class="whitespace-pre-wrap text-sm text-gray-200"><?= htmlspecialchars($_SESSION['last_output']) ?></pre>
  </div>
  <?php unset($_SESSION['last_output']); ?>
<?php endif; ?>

<?php
$remoteDir = __DIR__ . '/backup-from-remote';
$remoteFiles = glob("$remoteDir/*.tar.gz");
?>

<?php if (file_exists($backupFile) || !empty($remoteFiles)): ?>
  <div class="bg-gray-800 rounded p-4 space-y-2">
    <?php if (file_exists($backupFile) && empty($_SESSION['downloaded']['backup-vpn.tar.gz'])): ?>
      <p class="text-green-300">✅ Backup lokal tersedia:</p>
      <a href="?download=backup-vpn.tar.gz" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">
        ⬇ Download backup-vpn.tar.gz
      </a>
    <?php endif; ?>

    <?php foreach ($remoteFiles as $filePath):
      $fileName = basename($filePath);
      if (!isset($_SESSION['downloaded'][$fileName])):
    ?>
      <p class="text-green-300">✅ Backup remote tersedia: <?= htmlspecialchars($fileName) ?></p>
      <a href="?download=<?= urlencode($fileName) ?>" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded shadow inline-block">
        ⬇ Download <?= htmlspecialchars($fileName) ?>
      </a>
    <?php endif; endforeach; ?>
  </div>
<?php endif; ?>

</div>

<script>
function setFormAction(ip, action) {
  const form = document.getElementById('form-' + ip);
  const localIp = '<?= $localIp ?>';
  form.action = (ip === localIp) ? (action === 'backup' ? 'backup.php' : 'restore.php') : 'backup-restore.php';
  return true;
}
</script>
</body>
</html>

