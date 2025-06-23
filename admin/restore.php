<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

$backupFile = __DIR__ . '/backup-vpn.tar.gz';

// ✅ Fungsi eksekusi command
function execute($cmd) {
    ob_start();
    passthru($cmd);
    return ob_get_clean();
}

$output = '';

if (isset($_POST['mode'])) {
    $mode = $_POST['mode'];

    if ($mode === 'local' && file_exists($backupFile)) {
        $output = execute("sudo tar -xzf $backupFile -C /root && sudo cp -r /root/backup-vpn/* / --no-preserve=ownership && echo '✅ Restore dari lokal berhasil!'");

    } elseif ($mode === 'gdrive' && isset($_POST['token'])) {
        $token = trim($_POST['token']);
        $tmpTokenPath = "/tmp/token.json";
        file_put_contents($tmpTokenPath, $token);

        $restoreScript = "/var/www/html/Website-Tokomard-Panel/admin/auto-restore-vpn.sh";
        $scriptContent = <<<EOL
#!/bin/bash

TOKEN_FILE="/tmp/token.json"
DEST="/root/backup-vpn.tar.gz"
RESTORE_DIR="/root/backup-vpn"
RCLONE_CONF="/root/.config/rclone/rclone.conf"

# Cek token
if [ ! -f "$TOKEN_FILE" ]; then
    echo "❌ Token file tidak ditemukan!"
    exit 1
fi

if ! jq .access_token "$TOKEN_FILE" &>/dev/null; then
    echo "❌ Token JSON tidak valid!"
    exit 1
fi

echo "🔄 Mengatur rclone config..."
mkdir -p "$(dirname "$RCLONE_CONF")"
cat > "$RCLONE_CONF" <<EOF
[GDRIVE]
type = drive
scope = drive
token = $(cat "$TOKEN_FILE")
team_drive =
EOF

# Unduh file backup
echo "☁ Mengunduh file backup dari Google Drive..."
if rclone --config="$RCLONE_CONF" copy GDRIVE:/TOKOMARD/Backup-VPS/SGDO-2DEV/backup-vpn.tar.gz /root/; then
    echo "🗜 Mengekstrak dan merestore..."
    
    if tar -xzf "$DEST" -C /root; then
        if cp -r "$RESTORE_DIR"/* / --no-preserve=ownership; then
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
EOL;

        file_put_contents($restoreScript, $scriptContent);
        chmod($restoreScript, 0700);

        $output = execute("sudo bash $restoreScript");
    }

    // ✅ TAMPILKAN TERMINAL HASIL
    echo <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>🟢 Restore Panel</title>
  <style>
    html, body {
      margin: 0;
      padding: 0;
      background-color: #000;
      color: #0f0;
      font-family: 'Courier New', monospace;
      height: 100%;
    }
    .terminal-wrapper {
      display: flex;
      flex-direction: column;
      height: 100vh;
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
      white-space: pre-wrap;
      position: relative;
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
  </style>
</head>
<body>
  <div class="terminal-wrapper">
    <div class="terminal-box">
<pre>$output</pre>
    </div>
  </div>
</body>
</html>
HTML;
    exit;
}
?>

<!-- FORM PILIHAN RESTORE -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Restore Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen p-6">
    <div class="max-w-xl mx-auto">
        <h1 class="text-3xl font-bold text-yellow-400 mb-6">🔁 Restore Data VPN</h1>

        <div class="space-y-8">
            <!-- Restore dari Lokal -->
            <form method="POST" class="bg-gray-800 p-4 rounded-xl shadow">
                <input type="hidden" name="mode" value="local">
                <h2 class="text-xl font-semibold mb-2">📂 Restore dari File Lokal</h2>
                <p class="text-sm text-gray-300 mb-4">File <code>backup-vpn.tar.gz</code> harus tersedia di folder <code>/admin</code>.</p>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-xl">🔄 Restore Sekarang</button>
            </form>

            <!-- Restore dari Google Drive -->
            <form method="POST" class="bg-gray-800 p-4 rounded-xl shadow">
                <input type="hidden" name="mode" value="gdrive">
                <h2 class="text-xl font-semibold mb-2">☁ Restore dari Google Drive</h2>
                <label class="block mb-2 text-sm">Tempelkan Token JSON dari Google Drive</label>
                <textarea name="token" rows="6" required class="w-full p-3 rounded bg-gray-900 text-white border border-gray-600 mb-4"></textarea>
                <button type="submit" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded-xl">🔄 Restore dari GDrive</button>
            </form>
        </div>
    </div>
</body>
</html>

