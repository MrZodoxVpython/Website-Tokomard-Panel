#!/bin/bash
TOKEN_FILE="/tmp/token.json"
if [ ! -f "$TOKEN_FILE" ]; then
    echo "❌ Token file tidak ditemukan!"
    exit 1
fi
if ! jq .access_token "$TOKEN_FILE" &>/dev/null; then
    echo "❌ Token JSON tidak valid atau rusak!"
    exit 1
fi
echo "📦 Menjalankan proses backup..."
if ! command -v rclone &>/dev/null; then
    echo "📥 Menginstall rclone..."
    curl https://rclone.org/install.sh | bash
    if [ $? -ne 0 ]; then
        echo "❌ Gagal menginstal rclone!"
        exit 1
    fi
fi
RCLONE_CONF="/root/.config/rclone/rclone.conf"
mkdir -p "$(dirname "$RCLONE_CONF")"
cat > "$RCLONE_CONF" <<EOF
[GDRIVE]
type = drive
scope = drive
token = $(cat "$TOKEN_FILE")
team_drive =
EOF
BACKUP_DIR="/root/backup-vpn/etc"
BACKUP_FILE="/root/backup-vpn.tar.gz"
WEB_DEST="/var/www/html/Website-Tokomard-Panel/admin/backup-vpn.tar.gz"
rm -rf "$BACKUP_DIR"
mkdir -p "$BACKUP_DIR"
cp -r /etc/xray "$BACKUP_DIR/" 2>/dev/null || echo "⚠ /etc/xray tidak ditemukan"
cp -r /etc/v2ray "$BACKUP_DIR/" 2>/dev/null || echo "⚠ /etc/v2ray tidak ditemukan"
cp -r /etc/passwd /etc/shadow /etc/group /etc/gshadow "$BACKUP_DIR/" 2>/dev/null
cp -r /etc/cron.d "$BACKUP_DIR/" 2>/dev/null
cp -r /etc/ssh "$BACKUP_DIR/" 2>/dev/null
cp -r /etc/systemd/system "$BACKUP_DIR/" 2>/dev/null
echo "🗜 Membuat arsip backup..."
tar -czf "$BACKUP_FILE" -C /root backup-vpn
if [ ! -f "$BACKUP_FILE" ]; then
    echo "❌ File backup gagal dibuat."
    ls -lah /root/backup-vpn
    exit 1
fi
echo "☁ Mengupload ke Google Drive..."
# ATUR PATH BACKUP DI AKUN GDRIVE
if ! rclone --config="$RCLONE_CONF" copy "$BACKUP_FILE" GDRIVE:/TOKOMARD/Backup-VPS/SGDO-2DEV --progress 2>&1; then
  echo "❌ Upload ke Google Drive gagal!"
else
  echo "✅ Upload ke Google Drive berhasil!"
fi
cp "$BACKUP_FILE" "$WEB_DEST"
chmod 644 "$WEB_DEST"
rm -rf "$BACKUP_DIR"
rm -rf "$BACKUP_FILE"
echo "✅ Backup berhasil! File tersedia untuk diunduh di web panel."