<?php
header('Content-Type: application/json');

$logFile = __DIR__ . '/chatlog.json';

if (file_exists($logFile)) {
    $messages = json_decode(file_get_contents($logFile), true);
    echo json_encode($messages);
} else {
    echo json_encode([]);
}
?>

