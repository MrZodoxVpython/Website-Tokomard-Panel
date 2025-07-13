<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.mailersend.net';
    $mail->SMTPAuth = true;
    $mail->Username = 'MS_8uGM8H@tokomard.store'; // dari dashboard MailerSend
    $mail->Password = 'mssp.vxBocLl.3z0vklo5jve47qrx.T9ohzZH'; // API Key/Password SMTP kamu
    $mail->SMTPSecure = 'tls';
    $mail->Port = 2525;

    // Ini sekarang sudah boleh karena domain sudah verified di MailerSend
    $mail->setFrom('noreply@tokomard.store', 'Tokomard Panel');
    $mail->addAddress('yudizlaberoz064@gmail.com'); // penerima email
    $mail->Subject = 'Test Kirim Email via MailerSend';
    $mail->Body    = '✅ Ini email uji coba dari PHPMailer + MailerSend (SMTP).';

    $mail->send();
    echo "✅ Email berhasil dikirim.\n";
} catch (Exception $e) {
    echo "❌ Gagal kirim email: {$mail->ErrorInfo}\n";
}

