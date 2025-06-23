#!/bin/bash
TOKEN_FILE="/tmp/token.json"
if [ ! -f "$TOKEN_FILE" ]; then
    echo "❌ Token file tidak ditemukan!"
    exit 1
fi
if ! jq .access_token "$TOKEN_FILE" &>/dev/null; then
    echo "❌ Token JSON tidak valid!"
    exit 1
fi
echo "🔄 Mengatur rclone config..."
RCLONE_CONF="/root/.config/rclone/rclone.conf"
mkdir -p "$(dirname "$RCLONE_CONF")"
cat > "$RCLONE_CONF" <<EOF
[GDRIVE]
type = drive
scope = drive
token = $(cat "$TOKEN_FILE")
team_drive =
EOF
echo "☁ Mengunduh file backup dari Google Drive..."
DEST="/root/backup-vpn.tar.gz"
if rclone --config="$RCLONE_CONF" copy GDRIVE:/TOKOMARD/Backup-VPS/SGDO-2DEV/backup-vpn.tar.gz /root/; then
    echo "🗜 Mengekstrak dan merestore..."
    tar -xzf "$DEST" -C /root
    cp -r /root/backup-vpn/* / --no-preserve=ownership
    echo "✅ Restore dari GDrive berhasil!"
else
    echo "❌ Gagal mengunduh dari Google Drive."
fi