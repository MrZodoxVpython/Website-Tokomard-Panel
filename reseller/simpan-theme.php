<?php
session_start();

$theme = $_POST['theme'] ?? null;
$username = $_SESSION['username'] ?? null;

if (!$theme || !$username) {
    http_response_code(400);
    echo "Gagal simpan tema: data tidak lengkap";
    exit;
}

$file = __DIR__ . '/uploads/theme.json';
$data = [];

if (file_exists($file)) {
    $data = json_decode(file_get_contents($file), true);
    if (!is_array($data)) $data = [];
}

$data[$username] = $theme;
file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
$_SESSION['theme'] = $theme;

echo "OK";

