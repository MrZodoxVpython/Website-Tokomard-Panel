<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$user = $_GET['user'] ?? '';
$vps = $_GET['vps'] ?? 'sgdo-2dev';
$found = false;

// Konfigurasi daftar VPS
$vpsList = [
    'sgdo-2dev' => ['user' => 'root', 'ip' => '127.0.0.1'], // lokal
    'sgdo-mard1' => ['user' => 'root', 'ip' => '152.42.182.187'],
    'rw-mard' => ['user' => 'root', 'ip' => '203.194.113.140'],
];
$vpsMap = [
    'sgdo-2dev' => '/etc/xray/config.json',
    'sgdo-mard1' => '/etc/xray/config.json',
    'rw-mard' => '/etc/xray/config.json',
];

$sshUser = $vpsList[$vps]['user'];
$sshIp = $vpsList[$vps]['ip'];
$configPath = $vpsMap[$vps];
$isRemote = $vps !== 'sgdo-2dev';

if ($user) {
    if ($isRemote) {
        // Ambil file config dari remote
        $configContent = shell_exec("ssh -o StrictHostKeyChecking=no $sshUser@$sshIp 'cat $configPath'");
        if (!$configContent) {
            die("❌ Gagal membaca config.json dari VPS $vps");
        }
        $lines = explode("\n", $configContent);
    } else {
        if (!file_exists($configPath)) {
            die("❌ Config file tidak ditemukan di lokal");
        }
        $lines = file($configPath);
    }

    $newLines = [];
    for ($i = 0; $i < count($lines); $i++) {
        if (preg_match('/^\s*(###|#&|#!|#\$)\s+' . preg_quote($user, '/') . '\s+\d{4}-\d{2}-\d{2}/', $lines[$i])) {
            $found = true;
            $i++; // skip juga baris JSON
            continue;
        }
        $newLines[] = $lines[$i];
    }

    if ($found) {
        $newConfig = implode("\n", $newLines);

        if ($isRemote) {
            // Simpan sementara lalu kirim via SCP dan restart xray
            $tmpFile = "/tmp/tmp_config_" . uniqid() . ".json";
            file_put_contents($tmpFile, $newConfig);
            shell_exec("scp -o StrictHostKeyChecking=no $tmpFile $sshUser@$sshIp:$configPath");
            shell_exec("ssh -o StrictHostKeyChecking=no $sshUser@$sshIp 'systemctl restart xray'");
            unlink($tmpFile);
        } else {
            file_put_contents($configPath, $newConfig);
            shell_exec("systemctl restart xray");
        }
    }
}

// Redirect kembali ke halaman
header("Location: kelola-akun.php?vps=" . urlencode($vps));
exit;
?>

