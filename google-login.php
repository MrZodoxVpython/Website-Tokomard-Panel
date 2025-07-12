<?php
require_once 'google-config.php';

// Ambil mode dari URL (login atau register)
$mode = $_GET['mode'] ?? 'login';

// Simpan mode ke dalam `state` untuk diteruskan ke callback
$client->setState($mode);

// Buat URL login Google
$login_url = $client->createAuthUrl();

// Redirect ke Google
header("Location: $login_url");
exit;

