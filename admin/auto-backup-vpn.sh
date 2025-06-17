#!/bin/bash

TOKEN_BASE64="$1"
TOKEN=$(echo "$TOKEN_BASE64" | base64 -d)

# Cek validitas
if ! echo "$TOKEN" | jq .access_token &>/dev/null; then
  echo "❌ Token JSON tidak valid atau rusak!"
  exit 1
fi

echo "📦 Menjalankan proses backup..."

# Install rclone jika belum ada
if ! command -v rclone &>/dev/null; then
    echo "📥 Menginstall rclone..."
    curl https://rclone.org/install.sh | bash
    if [ $? -ne 0 ]; then
        echo "❌ Gagal menginstal rclone!"
        exit 1
    fi
fi

# Konfigurasi rclone
RCLONE_CONF="/root/.config/rclone/rclone.conf"
mkdir -p $(dirname "$RCLONE_CONF")
echo -e "[GDRIVE]
type = drive
scope = drive
token = $TOKEN
team_drive =" > "$RCLONE_CONF"

# Lokasi backup
BACKUP_DIR="/root/backup-vpn"
BACKUP_FILE="/root/backup-vpn.tar.gz"
WEB_DEST="/var/www/html/Website-Tokomard-Panel/admin/backup-vpn.tar.gz"

# Bersihkan dan buat folder backup baru
rm -rf "$BACKUP_DIR"
mkdir -p "$BACKUP_DIR"

# File penting yang akan di-backup
cp -r /etc/xray "$BACKUP_DIR/" 2>/dev/null || echo "⚠️ /etc/xray tidak ditemukan"
cp -r /etc/v2ray "$BACKUP_DIR/" 2>/dev/null || echo "⚠️ /etc/v2ray tidak ditemukan"
cp -r /etc/passwd /etc/shadow /etc/group /etc/gshadow "$BACKUP_DIR/" 2>/dev/null
cp -r /etc/cron.d "$BACKUP_DIR/" 2>/dev/null
cp -r /etc/ssh "$BACKUP_DIR/" 2>/dev/null
cp -r /etc/systemd/system "$BACKUP_DIR/" 2>/dev/null

# Buat file tar.gz
echo "🗜️ Membuat arsip backup..."
tar -czf "$BACKUP_FILE" -C /root backup-vpn
if [ ! -f "$BACKUP_FILE" ]; then
    echo "❌ File backup gagal dibuat."
    ls -lah /root/backup-vpn
    exit 1
fi

# Upload ke Google Drive
echo "☁ Mengupload ke Google Drive..."
if ! rclone --config="$RCLONE_CONF" copy "$BACKUP_FILE" GDRIVE:/TOKOMARD/Backup-VPS/SGDO-2DEV --progress 2>&1; then
  echo "❌ Upload ke Google Drive gagal!"
else
  echo "✅ Upload ke Google Drive berhasil!"
fi

# Salin ke web folder
cp "$BACKUP_FILE" "$WEB_DEST"
chmod 644 "$WEB_DEST"

echo "✅ Backup berhasil! File tersedia untuk diunduh di web panel."

