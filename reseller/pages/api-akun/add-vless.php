<?php
require_once '/lib-akun.php';

if ($argc < 4) {
    echo "❌ Parameter tidak lengkap!\n";
    echo "Gunakan: php tambah-vless.php username expired uuid\n";
    exit(1);
}

$username = $argv[1];
$expired  = hitungTanggalExpired($argv[2]);
$uuid     = $argv[3];

$commentLine = "#& $username $expired";
$jsonLine = "},{\"id\": \"$uuid\", \"email\": \"$username\"";
$tags = ['vless', 'vlessgrpc'];

prosesXray('vless', $tags, $commentLine, $jsonLine, $username, $expired, $uuid);

