<?php
session_start();
$data = json_decode(file_get_contents("php://input"), true);
$theme = ($data['theme'] ?? 'light') === 'dark' ? 'dark' : 'light';

// Simpan ke session
$_SESSION['theme'] = $theme;

// Simpan ke file
$username = $_SESSION['username'] ?? 'guest';
$themeFile = __DIR__ . '/uploads/theme.json';
$themes = [];

if (file_exists($themeFile)) {
    $themes = json_decode(file_get_contents($themeFile), true);
}

$themes[$username] = $theme;
file_put_contents($themeFile, json_encode($themes, JSON_PRETTY_PRINT));

