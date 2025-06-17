#!/bin/bash

TOKEN=$1
if [ -z "$TOKEN" ]; then
    echo "❌ Token JSON tidak diberikan!"
    exit 1
fi

# Install rclone jika belum
if ! command -v rclone &>/dev/null; then
    echo "📦 Menginstall rclone..."
    curl https://rclone.org/install.sh | bash
    if [ $? -ne 0 ]; then
        echo "❌ Gagal install rclone!"
        exit 1
    fi
fi

# Konfigurasi rclone
RCLONE_CONF="/root/.config/rclone/rclone.conf"
mkdir -p /root/.config/rclone

echo -e "[GDRIVE]
type = drive
scope = drive
token = $TOKEN
team_drive =" > $RCLONE_CONF

# Backup
BACKUP_DIR="/root/backup-vpn"
BACKUP_FILE="/root/backup-vpn.tar.gz"

rm -rf $BACKUP_DIR
mkdir -p $BACKUP_DIR

cp -r /etc/xray $BACKUP_DIR/
cp -r /etc/v2ray $BACKUP_DIR/ 2>/dev/null
cp -r /etc/passwd /etc/shadow /etc/group /etc/gshadow $BACKUP_DIR/
cp -r /etc/cron.d $BACKUP_DIR/
cp -r /etc/ssh $BACKUP_DIR/
cp -r /etc/systemd/system $BACKUP_DIR/

tar -czf $BACKUP_FILE -C /root backup-vpn

# Upload ke Google Drive
rclone --config=$RCLONE_CONF copy $BACKUP_FILE GDRIVE:/TOKOMARD/Backup-VPS/SGDO-2DEV --progress

# Salin backup ke folder web
cp $BACKUP_FILE /var/www/html/Website-Tokomard-Panel/admin/backup-vpn.tar.gz
