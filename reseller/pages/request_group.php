<?php
session_start();

$reseller = $_SESSION['username'];
$userFile = '../data/reseller_users.json';
$users = file_exists($userFile) ? json_decode(file_get_contents($userFile), true) : [];

$found = false;
foreach ($users as &$user) {
    if ($user['username'] === $reseller) {
        $user['status'] = 'pending';
        $found = true;
        break;
    }
}
unset($user);

if (!$found) {
    $users[] = [
        'username' => $reseller,
        'status' => 'pending'
    ];
}

file_put_contents($userFile, json_encode($users, JSON_PRETTY_PRINT));

header("Location: ../pages/vip.php");
exit;

