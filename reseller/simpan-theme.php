<?php
session_start();

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['tema']) || !in_array($data['tema'], ['dark', 'light'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Tema tidak valid']);
    exit;
}

$username = $_SESSION['username'] ?? 'guest';
$themeFile = __DIR__ . "/uploads/theme.json";

$themeData = [];
if (file_exists($themeFile)) {
    $themeData = json_decode(file_get_contents($themeFile), true);
}

$themeData[$username] = $data['tema'];
file_put_contents($themeFile, json_encode($themeData, JSON_PRETTY_PRINT));
echo json_encode(['status' => 'success']);

