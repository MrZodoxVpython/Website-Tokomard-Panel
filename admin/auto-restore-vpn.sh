#!/bin/bash

TOKEN_FILE="/tmp/token.json"
DEST="/root/backup-vpn.tar.gz"
RESTORE_DIR="/root/backup-vpn"
RCLONE_CONF="/root/.config/rclone/rclone.conf"

# Cek token
if [ ! -f "" ]; then
    echo "❌ Token file tidak ditemukan!"
    exit 1
fi

if ! jq .access_token "" &>/dev/null; then
    echo "❌ Token JSON tidak valid!"
    exit 1
fi

echo "🔄 Mengatur rclone config..."
mkdir -p "$(dirname "")"
cat > "" <<EOF
[GDRIVE]
type = drive
scope = drive
token = $(cat "")
team_drive =
EOF

# Unduh file backup
echo "☁ Mengunduh file backup dari Google Drive..."
if rclone --config="" copy GDRIVE:/TOKOMARD/Backup-VPS/SGDO-2DEV/backup-vpn.tar.gz /root/; then
    echo "🗜 Mengekstrak dan merestore..."
    
    if tar -xzf "" -C /root; then
        if cp -r ""/* / --no-preserve=ownership; then
            echo "✅ Restore dari GDrive berhasil!"
            
            echo "🔁 Restart layanan xray dan ssh..."
            systemctl restart xray && echo "✅ xray berhasil direstart" || echo "❌ Gagal restart xray"
            systemctl restart ssh && echo "✅ ssh berhasil direstart" || echo "❌ Gagal restart ssh"
            
        else
            echo "❌ Gagal menyalin file ke root filesystem!"
            exit 1
        fi
    else
        echo "❌ Gagal mengekstrak file backup!"
        exit 1
    fi
else
    echo "❌ Gagal mengunduh dari Google Drive."
    exit 1
fi