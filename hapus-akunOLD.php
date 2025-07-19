<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$configPath = '/etc/xray/config.json';
$user = $_GET['user'] ?? '';
$found = false;

if ($user) {
    $lines = file($configPath);
    $newLines = [];
    for ($i = 0; $i < count($lines); $i++) {
        if (preg_match('/^\s*(###|#&|#!|#\$)\s+' . preg_quote($user) . '\s+\d{4}-\d{2}-\d{2}/', $lines[$i])) {
            // Skip comment and JSON line
            $found = true;
            $i++; // skip next line (JSON)
            continue;
        }
        $newLines[] = $lines[$i];
    }

    if ($found) {
        file_put_contents($configPath, implode('', $newLines));

        // ⬇️ Restart Xray jika akun berhasil dihapus
        shell_exec('systemctl restart xray');
    }
}

header("Location: kelola-akun.php");
exit;

