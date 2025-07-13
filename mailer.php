<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;

$otp = rand(100000, 999999); // generate OTP
$emailTujuan = 'yudizlaberoz064@gmail.com'; // ganti dengan email tujuan

$client = new Client([
    'base_uri' => 'https://api.resend.com/',
    'headers' => [
        'Authorization' => 're_AwrPwQ6f_Jq7UhMrkmBdSAFSLMHu2r9Ai', // ganti API Key Resend
        'Content-Type'  => 'application/json',
    ]
]);

try {
    $res = $client->post('emails', [
        'json' => [
            'from' => 'Tokomard Panel <noreply@tokomard.store>', // HARUS verified domain
            'to' => [$emailTujuan],
            'subject' => 'Kode OTP Anda',
            'html' => "<h3>Kode OTP: <strong>$otp</strong></h3><p>Jangan bagikan ke siapa pun. Berlaku 5 menit.</p>",
        ]
    ]);

    echo "✅ OTP $otp berhasil dikirim ke $emailTujuan\n";
} catch (Exception $e) {
    echo "❌ Gagal kirim OTP: " . $e->getMessage() . "\n";
}

