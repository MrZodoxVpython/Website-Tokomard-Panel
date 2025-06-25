<?php
require_once '/usr/local/bin/lib-akun.php';

if ($argc < 4) {
    echo "❌ Parameter tidak lengkap!\n";
    echo "Gunakan: php tambah-shadowsocks.php username expired password\n";
    exit(1);
}

$username = $argv[1];
$expired  = hitungTanggalExpired($argv[2]);
$password = $argv[3];

$commentLine = "#$ $username $expired";
$jsonLine = "},{\"password\": \"$password\", \"method\": \"aes-128-gcm\", \"email\": \"$username\"";
$tags = ['ssws', 'ssgrpc'];

prosesXray('shadowsocks', $tags, $commentLine, $jsonLine, $username, $expired, $password);

