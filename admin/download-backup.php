<?php
$file = '/root/backup-vpn.tar.gz';
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

