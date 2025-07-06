<?php
session_start();
// Cek role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

$file = '/var/www/html/Website-Tokomard-Panel/admin/backup-from-remote/backup-vpn.tar.gz';
if (file_exists($file)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/gzip');
    header('Content-Disposition: attachment; filename="backup-vpn.tar.gz"');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
} else {
    echo "File backup tidak ditemukan.";
}
?>

