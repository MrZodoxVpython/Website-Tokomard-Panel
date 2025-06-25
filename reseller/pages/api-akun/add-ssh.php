<?php
require_once '/reseller/pages/tambah-akun/lib-akun.php'; // File yang berisi fungsi hitungTanggalExpired()

if ($argc < 4) {
    echo "❌ Format: php tambah-akun-ssh.php username expired password\n";
    exit(1);
}
$username = $argv[1];
$expired  = hitungTanggalExpired($argv[2]);
$key      = $argv[3];

$cmd = "sudo useradd -e $expired -s /bin/false -M $username && echo \"$username:$key\" | sudo chpasswd";
shell_exec($cmd);

// Cetak hasil sama seperti logika di `tambah-akun.php`
tampilkanSSH($username, $expired, $key);

