<?php
require_once 'vendor/autoload.php'; // pastikan composer sudah install google api client

$client = new Google_Client();
$client->setClientId('584801870346-ugn9g1f07l0m5mcfnmssvgp9s16ufpja.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('https://panel.tokomard.store/google-callback.php');
$client->addScope('email');
$client->addScope('profile');
?>

