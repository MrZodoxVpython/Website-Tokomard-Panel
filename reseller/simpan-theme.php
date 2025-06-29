<?php
session_start();

$theme = $_POST['theme'] ?? null;
$username = $_SESSION['username'] ?? null;

if (!$theme || !$username) {
    http_response_code(400);
    echo "Data tidak lengkap";
    exit;
}

$path = __DIR__ . '/uploads/theme.json';
$data = [];

if (file_exists($path)) {
    $data = json_decode(file_get_contents($path), true);
    if (!is_array($data)) $data = [];
}

$data[$username] = $theme;
file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));

// Simpan juga ke session
$_SESSION['theme'] = $theme;

echo "OK";

