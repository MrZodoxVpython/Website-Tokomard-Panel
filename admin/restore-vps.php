<?php
session_start();

function execute($cmd) {
    ob_start();
    passthru($cmd);
    return ob_get_clean();
}

// === PROSES RESTORE ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method = $_POST['method'] ?? '';

    if ($method === 'local') {
        $backupFile = "/var/www/html/Website-Tokomard-Panel/admin/backup-vpn.tar.gz";

        if (!file_exists($backupFile)) {
            echo "<p class='text-red-400'>❌ File backup tidak ditemukan.</p>";
            exit;
        }

        $script = "/tmp/restore-local.sh";
        file_put_contents($script, <<<EOL
#!/bin/bash
tar -xzf "$backupFile" -C /root
cp -rf /root/backup-vpn/* /
echo "✅ Restore dari file lokal selesai."
EOL);
        chmod($script, 0700);
        echo "<pre>" . execute("sudo bash $script") . "</pre>";
        exit;

    } elseif ($method === 'upload' && isset($_FILES['backup'])) {
        $uploadFile = "/tmp/" . basename($_FILES['backup']['name']);
        if (move_uploaded_file($_FILES['backup']['tmp_name'], $uploadFile)) {
            $script = "/tmp/restore-upload.sh";
            file_put_contents($script, <<<EOL
#!/bin/bash
tar -xzf "$uploadFile" -C /root
cp -rf /root/backup-vpn/* /
echo "✅ Restore dari file upload selesai."
EOL);
            chmod($script, 0700);
            echo "<pre>" . execute("sudo bash $script") . "</pre>";
        } else {
            echo "<p class='text-red-400'>❌ Upload gagal!</p>";
        }
        exit;

    } elseif ($method === 'gdrive' && isset($_POST['token'], $_POST['filename'])) {
        $token = trim($_POST['token']);
        $filename = trim($_POST['filename']);
        $tmpToken = "/tmp/gdrive-token.json";
        $localFile = "/tmp/restore-from-drive.tar.gz";
        file_put_contents($tmpToken, $token);

        $script = "/tmp/restore-gdrive.sh";
        file_put_contents($script, <<<EOL
#!/bin/bash
TOKEN=\$(cat "$tmpToken" | tr -d '\n' | tr -d '\r')
RCLONE_CONF="/root/.config/rclone/rclone.conf"
mkdir -p \$(dirname "\$RCLONE_CONF")
cat > "\$RCLONE_CONF" <<RCF
[GDRIVE]
type = drive
scope = drive
token = "\$TOKEN"
team_drive =
RCF

rclone --config="\$RCLONE_CONF" copy GDRIVE:/TOKOMARD/Backup-VPS/$filename "$localFile" -v

tar -xzf "$localFile" -C /root
cp -rf /root/backup-vpn/* /
echo "✅ Restore dari Google Drive selesai."
EOL);
        chmod($script, 0700);
        echo "<pre>" . execute("sudo bash $script") . "</pre>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>🔁 Restore Backup VPN</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen">
<div class="max-w-2xl mx-auto p-6">
    <h1 class="text-2xl font-bold text-green-400 mb-6">🔁 Restore Backup VPN</h1>

    <div class="space-y-8">
        <!-- Form 1: Restore via file lokal -->
        <div class="bg-gray-800 p-4 rounded-xl shadow">
            <h2 class="text-lg font-semibold mb-2 text-yellow-300">🗃 Restore dari File Lokal (admin/backup-vpn.tar.gz)</h2>
            <form method="POST">
                <input type="hidden" name="method" value="local">
                <button class="mt-2 bg-blue-600 px-4 py-2 rounded shadow">🚀 Jalankan Restore</button>
            </form>
        </div>

        <!-- Form 2: Upload File -->
        <div class="bg-gray-800 p-4 rounded-xl shadow">
            <h2 class="text-lg font-semibold mb-2 text-yellow-300">📤 Upload File Backup Manual</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="method" value="upload">
                <input type="file" name="backup" accept=".gz" class="mb-2">
                <button class="bg-blue-600 px-4 py-2 rounded shadow">🚀 Restore dari Upload</button>
            </form>
        </div>

        <!-- Form 3: Restore dari Google Drive -->
        <div class="bg-gray-800 p-4 rounded-xl shadow">
            <h2 class="text-lg font-semibold mb-2 text-yellow-300">☁ Restore dari Google Drive</h2>
            <form method="POST">
                <input type="hidden" name="method" value="gdrive">
                <label class="block mb-1">Token JSON:</label>
                <textarea name="token" rows="5" class="w-full rounded bg-gray-700 p-2 mb-2" required></textarea>
                <label class="block mb-1">Nama File (contoh: backup-vpn.tar.gz):</label>
                <input type="text" name="filename" class="w-full rounded bg-gray-700 p-2 mb-2" required>
                <button class="bg-blue-600 px-4 py-2 rounded shadow">🚀 Restore dari Drive</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>

