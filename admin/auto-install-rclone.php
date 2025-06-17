<?php
session_start();

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

# Validasi token JSON
if ! jq .access_token "\$TOKEN_FILE" &>/dev/null; then
    echo "❌ Token JSON tidak valid atau rusak!"
    exit 1
fi

echo "📦 Menjalankan proses backup..."

# Install rclone jika belum ada
if ! command -v rclone &>/dev/null; then
    echo "📥 Menginstall rclone..."
    curl https://rclone.org/install.sh | bash
    if [ \$? -ne 0 ]; then
        echo "❌ Gagal menginstal rclone!"
        exit 1
    fi
fi

# Konfigurasi rclone
RCLONE_CONF="/root/.config/rclone/rclone.conf"
mkdir -p "\$(dirname "\$RCLONE_CONF")"

cat > "\$RCLONE_CONF" <<EOF
[GDRIVE]
type = drive
scope = drive
token = $(cat "\$TOKEN_FILE")
team_drive =
EOF

# Lokasi backup
BACKUP_DIR="/root/backup-vpn"
BACKUP_FILE="/root/backup-vpn.tar.gz"
WEB_DEST="/var/www/html/Website-Tokomard-Panel/admin/backup-vpn.tar.gz"

# Bersihkan dan buat folder backup baru
rm -rf "\$BACKUP_DIR"
mkdir -p "\$BACKUP_DIR"

# File penting yang akan di-backup
cp -r /etc/xray "\$BACKUP_DIR/" 2>/dev/null || echo "⚠ /etc/xray tidak ditemukan"
cp -r /etc/v2ray "\$BACKUP_DIR/" 2>/dev/null || echo "⚠ /etc/v2ray tidak ditemukan"
cp -r /etc/passwd /etc/shadow /etc/group /etc/gshadow "\$BACKUP_DIR/" 2>/dev/null
cp -r /etc/cron.d "\$BACKUP_DIR/" 2>/dev/null
cp -r /etc/ssh "\$BACKUP_DIR/" 2>/dev/null
cp -r /etc/systemd/system "\$BACKUP_DIR/" 2>/dev/null

# Buat file tar.gz
echo "🗜 Membuat arsip backup..."
tar -czf "\$BACKUP_FILE" -C /root backup-vpn
if [ ! -f "\$BACKUP_FILE" ]; then
    echo "❌ File backup gagal dibuat."
    ls -lah /root/backup-vpn
    exit 1
fi

# Upload ke Google Drive
echo "☁ Mengupload ke Google Drive..."
if ! rclone --config="\$RCLONE_CONF" copy "\$BACKUP_FILE" GDRIVE:/TOKOMARD/Backup-VPS/SGDO-2DEV --progress 2>&1; then
  echo "❌ Upload ke Google Drive gagal!"
else
  echo "✅ Upload ke Google Drive berhasil!"
fi

# Salin ke web folder
cp "\$BACKUP_FILE" "\$WEB_DEST"
chmod 644 "\$WEB_DEST"

echo "✅ Backup berhasil! File tersedia untuk diunduh di web panel."
EOL;

        file_put_contents($backupScript, $scriptContent);
        chmod($backupScript, 0700);
    }

    // Jalankan script backup
    $output = execute("sudo bash $backupScript");

    echo "<pre>$output</pre>";
    if (file_exists("/var/www/html/Website-Tokomard-Panel/admin/backup-vpn.tar.gz")) {
        echo "<a href='backup-vpn.tar.gz' download class='text-blue-400 underline'>📥 Download file backup dari server</a>";
    } else {
        echo "<p class='text-red-400 mt-4'>❌ File backup gagal dibuat atau tidak tersedia.</p>";
    }
    exit;
}
?>

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

