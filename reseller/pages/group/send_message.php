<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? 'anonymous';
    $message = $_POST['message'] ?? '';

    if (!empty($message)) {
        $dataFile = __DIR__ . '/chatlog.json';

        $messages = [];
        if (file_exists($dataFile)) {
            $json = file_get_contents($dataFile);
            $messages = json_decode($json, true) ?? [];
        }

        $messages[] = [
            'username' => $username,
            'message' => $message,
            'time' => time()
        ];

        $result = file_put_contents($dataFile, json_encode($messages, JSON_PRETTY_PRINT));
        if ($result === false) {
            echo "❌ GAGAL MENULIS FILE: $dataFile";
        } else {
            echo "✅ BERHASIL DITULIS: $dataFile";
        }
    } else {
        echo "❗ Pesan kosong, tidak disimpan.";
    }
} else {
    echo "❌ Metode bukan POST.";
}
?>

