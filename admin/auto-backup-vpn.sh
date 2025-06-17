#!/bin/bash

TOKEN=$1
if [ -z "$TOKEN" ]; then
    echo "❌ Token JSON tidak diberikan!"
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

rm -rf "$BACKUP_DIR"
mkdir -p "$BACKUP_DIR"

# File penting yang akan di-backup
cp -r /etc/xray "$BACKUP_DIR/" 2>/dev/null
cp -r /etc/v2ray "$BACKUP_DIR/" 2>/dev/null
cp -r /etc/passwd /etc/shadow /etc/group /etc/gshadow "$BACKUP_DIR/" 2>/dev/null
cp -r /etc/cron.d "$BACKUP_DIR/" 2>/dev/null
cp -r /etc/ssh "$BACKUP_DIR/" 2>/dev/null
cp -r /etc/systemd/system "$BACKUP_DIR/" 2>/dev/null

# Buat file tar.gz
echo "📦 Membuat arsip backup..."
tar -czf "$BACKUP_FILE" -C /root backup-vpn
if [ ! -f "$BACKUP_FILE" ]; then
    echo "❌ File backup gagal dibuat."
    exit 1
fi

# Upload ke Google Drive
echo "☁️ Mengupload ke Google Drive..."
rclone --config="$RCLONE_CONF" copy "$BACKUP_FILE" GDRIVE:/TOKOMARD/Backup-VPS/SGDO-2DEV --progress

# Salin ke web folder
cp "$BACKUP_FILE" "$WEB_DEST"

echo "✅ Backup berhasil! File tersedia untuk diunduh."