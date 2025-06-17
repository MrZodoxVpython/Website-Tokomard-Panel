<?php
// auto-install-rclone.php

session_start();

function execute($cmd) {
    ob_start();
    passthru($cmd);
    return ob_get_clean();
}

if (isset($_POST['token'])) {
    $token = $_POST['token'];
    $rcloneDir = '/root/.config/rclone';
    $rcloneConf = $rcloneDir . '/rclone.conf';

    // Pastikan direktori rclone ada
    if (!is_dir($rcloneDir)) {
        mkdir($rcloneDir, 0700, true);
    }

    // Tulis konfigurasi rclone.conf
    file_put_contents($rcloneConf, "[GDRIVE]\n");
    file_put_contents($rcloneConf, "type = drive\n", FILE_APPEND);
    file_put_contents($rcloneConf, "scope = drive\n", FILE_APPEND);
    file_put_contents($rcloneConf, "token = $token\n", FILE_APPEND);
    file_put_contents($rcloneConf, "team_drive =\n", FILE_APPEND);

    // Jalankan proses backup
    $backupDir = "/root/backup-vpn";
    $backupFile = "/root/backup-vpn.tar.gz";
    if (!is_dir($backupDir)) mkdir($backupDir, 0700, true);

    execute("cp -r /etc/xray $backupDir/");
    execute("cp -r /etc/v2ray $backupDir/ 2>/dev/null");
    execute("cp -r /etc/passwd /etc/shadow /etc/group /etc/gshadow $backupDir/");
    execute("cp -r /etc/cron.d $backupDir/");
    execute("cp -r /etc/ssh $backupDir/");
    execute("cp -r /etc/systemd/system $backupDir/");

    execute("tar -czf $backupFile -C /root backup-vpn");
    $uploadOutput = execute("rclone --config=$rcloneConf copy $backupFile GDRIVE:/TOKOMARD/Backup-VPS/SGDO-2DEV --progress");

    echo "<pre>✅ Backup selesai!\n\n$uploadOutput</pre>";
    echo "<a href='/backup-vpn.tar.gz' download class='text-blue-400 underline'>Download file backup dari server</a>";
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

