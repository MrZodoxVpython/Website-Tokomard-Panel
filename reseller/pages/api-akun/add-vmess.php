<?php
require_once '/lib-akun.php';

if ($argc < 4) {
    echo "❌ Parameter tidak lengkap!\n";
    echo "Gunakan: php tambah-vmess.php username expired uuid\n";
    exit(1);
}

$username = $argv[1];
$expired  = hitungTanggalExpired($argv[2]);
$uuid     = $argv[3];

$commentLine = "### $username $expired";
$jsonLine = "},{\"id\": \"$uuid\", \"alterId\": 0, \"email\": \"$username\"";
$tags = ['vmess', 'vmessgrpc'];

prosesXray('vmess', $tags, $commentLine, $jsonLine, $username, $expired, $uuid);

