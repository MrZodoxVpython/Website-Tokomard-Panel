<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
    exit;
}
$backupFile = __DIR__ . '/backup-vpn.tar.gz';

// ✅ HANDLE DOWNLOAD
if (isset($_GET['download']) && file_exists($backupFile)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($backupFile) . '"');
    header('Content-Length: ' . filesize($backupFile));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    flush();
    readfile($backupFile);
    flush();
    unlink($backupFile); // Hapus file setelah download
    exit;
}

// ✅ Fungsi jalankan perintah
function execute($cmd) {
    ob_start();
    passthru($cmd);
    return ob_get_clean();
}

$output = '';

if (isset($_POST['token'])) {
    $token = trim($_POST['token']);
    $tmpTokenPath = "/tmp/token.json";
    file_put_contents($tmpTokenPath, $token);
    $backupScript = "/var/www/html/Website-Tokomard-Panel/admin/auto-backup-vpn.sh";

    if (!file_exists($backupScript)) {
        $scriptContent = <<<EOL
#!/bin/bash
TOKEN_FILE="/tmp/token.json"
if [ ! -f "\$TOKEN_FILE" ]; then
    echo "❌ Token file tidak ditemukan!"
    exit 1
fi
if ! jq .access_token "\$TOKEN_FILE" &>/dev/null; then
    echo "❌ Token JSON tidak valid atau rusak!"
    exit 1
fi
echo "📦 Menjalankan proses backup..."
if ! command -v rclone &>/dev/null; then
    echo "📥 Menginstall rclone..."
    curl https://rclone.org/install.sh | bash
    if [ \$? -ne 0 ]; then
        echo "❌ Gagal menginstal rclone!"
        exit 1
    fi
fi
RCLONE_CONF="/root/.config/rclone/rclone.conf"
mkdir -p "\$(dirname "\$RCLONE_CONF")"
cat > "\$RCLONE_CONF" <<EOF
[GDRIVE]
type = drive
scope = drive
token = $(cat "\$TOKEN_FILE")
team_drive =
EOF
BACKUP_DIR="/root/backup-vpn/etc"
RM="/root/backup-vpn"
BACKUP_FILE="/root/backup-vpn.tar.gz"
WEB_DEST="/var/www/html/Website-Tokomard-Panel/admin/backup-from-remote/backup-vpn.tar.gz"
mkdir -p "\$BACKUP_DIR"
cp -r /etc/xray "\$BACKUP_DIR/" 2>/dev/null || echo "⚠ /etc/xray tidak ditemukan"
cp -r /etc/v2ray "\$BACKUP_DIR/" 2>/dev/null || echo "⚠ /etc/v2ray tidak ditemukan"
cp -r /etc/passwd /etc/shadow /etc/group /etc/gshadow "\$BACKUP_DIR/" 2>/dev/null
cp -r /etc/cron.d "\$BACKUP_DIR/" 2>/dev/null
cp -r /etc/ssh "\$BACKUP_DIR/" 2>/dev/null
cp -r /etc/systemd/system "\$BACKUP_DIR/" 2>/dev/null
echo "🗜 Membuat arsip backup..."
tar -czf "\$BACKUP_FILE" -C /root backup-vpn
# 🔒 Ubah hak akses agar bisa didownload
chown www-data:www-data "\$BACKUP_FILE"
chmod 755 "\$BACKUP_FILE"
if [ ! -f "\$BACKUP_FILE" ]; then
    echo "❌ File backup gagal dibuat."
    ls -lah /root/backup-vpn
    exit 1
fi
echo "☁ Mengupload ke Google Drive..."
# ATUR PATH BACKUP DI AKUN GDRIVE
if ! rclone --config="\$RCLONE_CONF" copy "\$BACKUP_FILE" GDRIVE:/TOKOMARD/Backup-VPS/SGDO-2DEV --progress 2>&1; then
  echo "❌ Upload ke Google Drive gagal!"
else
  echo "✅ Upload ke Google Drive berhasil!"
fi
cp "\$BACKUP_FILE" "\$WEB_DEST"
chown www-data:www-data "\$WEB_DEST"
chmod 644 "\$WEB_DEST"
rm -rf "\$BACKUP_DIR"
rm -rf "\$BACKUP_FILE"
rm -rf "\$RM"
echo "✅ Backup berhasil! File tersedia untuk diunduh di web panel."
EOL;
        file_put_contents($backupScript, $scriptContent);
        chmod($backupScript, 0700);
    }

    $output = execute("sudo bash $backupScript 2>&1");

    // TAMPILAN TERMINAL RESPONSIF
    echo <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>🟢 Cyberpunk Terminal</title>
  <style>
    html, body {
      margin: 0;
      padding: 0;
      background-color: #000;
      color: #0f0;
      font-family: 'Courier New', monospace;
      height: 100%;
      width: 100%;
      overflow: hidden;
    }
    .terminal-wrapper {
      display: flex;
      flex-direction: column;
      height: 100%;
      padding: 1rem;
      box-sizing: border-box;
    }
    .terminal-box {
      flex: 1;
      overflow-y: auto;
      background: #000;
      border: 2px solid #0f0;
      border-radius: 10px;
      padding: 1rem;
      box-shadow: 0 0 20px #0f0, inset 0 0 15px #0f0;
      position: relative;
      white-space: pre-wrap;
      font-size: 0.9rem;
      line-height: 1.4;
    }
    .terminal-box::before {
      content: "";
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: repeating-linear-gradient(
        to bottom,
        rgba(0, 255, 0, 0.05),
        rgba(0, 255, 0, 0.05) 1px,
        transparent 1px,
        transparent 2px
      );
      pointer-events: none;
      z-index: 1;
      animation: scan 6s linear infinite;
    }
    @keyframes scan {
      from { background-position: 0 0; }
      to { background-position: 0 100%; }
    }
    .download-link {
      color: #0ff;
      text-decoration: underline;
      margin-top: 1rem;
      display: inline-block;
      z-index: 2;
    }
    @media (max-width: 768px) {
      .terminal-box {
        font-size: 0.8rem;
        padding: 0.75rem;
      }
    }
  </style>
</head>
<body>
  <div class="terminal-wrapper">
    <div class="terminal-box">
<pre>$output</pre>
HTML;

    if (file_exists("/var/www/html/Website-Tokomard-Panel/admin/backup-from-remote/backup-vpn.tar.gz")) {
        echo "<a class='download-link' href='?download=1'>📥 Download file backup dari server</a>";
    } else {
        echo "<p style='color:#f00;'>❌ File backup gagal dibuat atau tidak tersedia.</p>";
    }

    echo <<<HTML
    </div>
  </div>
</body>
</html>
HTML;
    exit;
}
?>

<!-- FORM INPUT TETAP DENGAN TAILWIND -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Auto Install Rclone & Backup</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen">
<div class="max-w-xl mx-auto p-6">
    <h1 class="text-2xl font-bold text-blue-400 mb-4">🔄 Auto Install Rclone & Backup</h1>

    <div class="mb-6 bg-gray-800 p-4 rounded-xl shadow">
        <h2 class="text-lg font-semibold text-yellow-300 mb-2">Langkah-langkah Manual (1x saja):</h2>
        <pre class="text-sm text-gray-300 bg-gray-700 p-3 rounded overflow-x-auto">
curl https://rclone.org/install.sh | bash
rclone config

> n (new remote)
> name: GDRIVE
> storage: 22 (Google Drive)
> client_id: [kosongkan]
> client_secret: [kosongkan]
> scope: 1 (full access)
> service_account_file: [kosongkan]
> y/n: [kosongkan]
> y/n: n
> Dapatkan link & login akun Google kamu
> Paste > rclone authorize "drive" < di OS utama yang terinstall rclone. "eyJzY29wZSI6ImRyaXZlIn0" 
> config_token: [paste token]
> y/n: [kosongkan]
> y/e/d: [kosongkan]
> Copy seluruh JSON access token yang muncul setelah login berhasil
        </pre>
        <p class="text-green-300 mt-2">👉 Tempel token JSON dari Google Drive di bawah ini:</p>
    </div>

    <form method="POST">
        <label for="token" class="block mb-2 font-semibold">Token JSON Google Drive</label>
        <textarea name="token" id="token" rows="6" required class="w-full p-3 rounded bg-gray-800 text-white border border-gray-600"></textarea>
        <button type="submit" class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl shadow">🚀 Install & Backup Sekarang</button>
    </form>

    <?php if (!empty($output)): ?>
        <div class="terminal-wrapper">
            <div class="terminal-box">
                <pre><?= htmlspecialchars($output) ?></pre>
                <?php if (file_exists($backupFile)): ?>
                    <a class="download-link" href="?download=1">📥 Download file backup dari server</a>
                <?php else: ?>
                    <p style="color:#f00;">❌ File backup gagal dibuat atau tidak tersedia.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
