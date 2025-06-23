<?php
session_start();
// Cek role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

function execute($cmd) {
    ob_start();
    passthru($cmd);
    return ob_get_clean();
}

if (isset($_POST['token'])) {
    $token = trim($_POST['token']);

    // Simpan token ke file sementara
    $tmpTokenPath = "/tmp/token.json";
    file_put_contents($tmpTokenPath, $token);

    // Path ke script bash backup
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

BACKUP_DIR="/root/backup-vpn"
BACKUP_FILE="/root/backup-vpn.tar.gz"
WEB_DEST="/var/www/html/Website-Tokomard-Panel/admin/backup-vpn.tar.gz"

rm -rf "\$BACKUP_DIR"
mkdir -p "\$BACKUP_DIR"

cp -r /etc/xray "\$BACKUP_DIR/" 2>/dev/null || echo "⚠ /etc/xray tidak ditemukan"
cp -r /etc/v2ray "\$BACKUP_DIR/" 2>/dev/null || echo "⚠ /etc/v2ray tidak ditemukan"
cp -r /etc/passwd /etc/shadow /etc/group /etc/gshadow "\$BACKUP_DIR/" 2>/dev/null
cp -r /etc/cron.d "\$BACKUP_DIR/" 2>/dev/null
cp -r /etc/ssh "\$BACKUP_DIR/" 2>/dev/null
cp -r /etc/systemd/system "\$BACKUP_DIR/" 2>/dev/null

echo "🗜 Membuat arsip backup..."
tar -czf "\$BACKUP_FILE" -C /root backup-vpn
if [ ! -f "\$BACKUP_FILE" ]; then
    echo "❌ File backup gagal dibuat."
    ls -lah /root/backup-vpn
    exit 1
fi

echo "☁ Mengupload ke Google Drive..."
if ! rclone --config="\$RCLONE_CONF" copy "\$BACKUP_FILE" GDRIVE:/TOKOMARD/Backup-VPS/SGDO-2DEV --progress 2>&1; then
  echo "❌ Upload ke Google Drive gagal!"
else
  echo "✅ Upload ke Google Drive berhasil!"
fi

cp "\$BACKUP_FILE" "\$WEB_DEST"
chmod 644 "\$WEB_DEST"

echo "✅ Backup berhasil! File tersedia untuk diunduh di web panel."
EOL;
        file_put_contents($backupScript, $scriptContent);
        chmod($backupScript, 0700);
    }

    // Jalankan skrip
    $output = execute("sudo bash $backupScript");

    // Tampilan hacker cyberpunk
    echo <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>🟢 Cyberpunk Backup Console</title>
  <style>
    body {
      background-color: #000;
      margin: 0;
      padding: 0;
      font-family: 'Courier New', Courier, monospace;
      color: #0f0;
      position: relative;
    }
    .terminal-container {
      padding: 30px;
      height: 100vh;
      overflow-y: auto;
    }
    .terminal {
      white-space: pre-wrap;
      background: rgba(0, 0, 0, 0.95);
      padding: 25px;
      border: 2px solid #0f0;
      border-radius: 10px;
      box-shadow: 0 0 15px #0f0, inset 0 0 20px #0f0;
      animation: flicker 2.5s infinite alternate;
      font-size: 15px;
      line-height: 1.4;
      position: relative;
      z-index: 1;
    }
    @keyframes flicker {
      0% { text-shadow: 0 0 2px #0f0; }
      100% { text-shadow: 0 0 10px #0f0, 0 0 20px #0f0; }
    }
    .scanline::before {
      content: "";
      position: absolute;
      width: 100%;
      height: 100%;
      top: 0;
      left: 0;
      pointer-events: none;
      background: repeating-linear-gradient(
        to bottom,
        rgba(0, 255, 0, 0.05),
        rgba(0, 255, 0, 0.05) 1px,
        transparent 1px,
        transparent 2px
      );
      animation: scan 5s linear infinite;
      z-index: 0;
    }
    @keyframes scan {
      from { background-position: 0 0; }
      to { background-position: 0 100%; }
    }
    .dl-link {
      color: #0ff;
      display: inline-block;
      margin-top: 20px;
      text-decoration: underline;
    }
  </style>
</head>
<body class="scanline">
  <div class="terminal-container">
    <div class="terminal">
<pre>$output</pre>
HTML;

    if (file_exists("/var/www/html/Website-Tokomard-Panel/admin/backup-vpn.tar.gz")) {
        echo "<a class='dl-link' href='backup-vpn.tar.gz' download>📥 Download file backup dari server</a>";
    } else {
        echo "<p style='color:#f00;margin-top:20px;'>❌ File backup gagal dibuat atau tidak tersedia.</p>";
    }

    echo "</div></div></body></html>";
    exit;
}
?>

<!-- Form UI Tetap Pakai Tailwind -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Auto Install Rclone & Backup</title>
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
> storage: 20 (Google Drive)
> client_id: [kosongkan]
> client_secret: [kosongkan]
> scope: 1 (full access)
> service_account_file: [kosongkan]
> y/n: [kosongkan]
> y/n: n
> Dapatkan link & login akun Google kamu
> Paste > rclone authorize "drive" "eyJzY29wZSI6ImRyaXZlIn0" < di OS utama yang terinstall rclone
> config_token: [paste token]
> y/n: [kosongkan]
> y/e/d: [kosongkan]
> Copy seluruh JSON access token yang muncul setelah login berhasil

        </pre>
        <p class="text-green-300 mt-2">👉 Tempel token JSON tersebut di bawah ini:</p>
    </div>

    <form method="POST">
        <label for="token" class="block mb-2 font-semibold">Token JSON dari Google Drive</label>
        <textarea name="token" id="token" rows="6" required class="w-full p-3 rounded bg-gray-800 text-white border border-gray-600"></textarea>
        <button type="submit" class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl shadow">🚀 Install dan Backup Sekarang</button>
    </form>
</div>
</body>
</html>

