<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../koneksi.php';

$tab = $_GET['tab'] ?? 'overview';

if (!isset($_SESSION['username'])) {
    echo "Session reseller tidak ditemukan.";
    exit;
}

$reseller = $_SESSION['username'];

$defaultAvatar = 'uploads/avatars/default.png';
$avatarJsonPath = __DIR__ . '/../uploads/avatar.json';
$avatar = $defaultAvatar;

if ($reseller && file_exists($avatarJsonPath)) {
    $avatarData = json_decode(file_get_contents($avatarJsonPath), true);
    if (isset($avatarData[$reseller])) {
        $customAvatar = __DIR__ . '/../' . $avatarData[$reseller];
        if (file_exists($customAvatar)) {
            $avatar = $avatarData[$reseller];
        }
    }
}

$email = '';
$account_id = '';
$balance = 0;
$transactions = [];

$stmt = $conn->prepare("SELECT id, email, saldo FROM users WHERE username = ?");
$stmt->bind_param("s", $reseller);
$stmt->execute();
$userResult = $stmt->get_result();

if ($userRow = $userResult->fetch_assoc()) {
    $userId = $userRow['id'];
    $email = $userRow['email'];
    $balance = $userRow['saldo'];
    $account_id = 'ID-' . str_pad($userId, 3, '0', STR_PAD_LEFT);

    $stmt2 = $conn->prepare("SELECT type, status, amount, detail, date FROM transactions WHERE user_id = ? ORDER BY date DESC");
    $stmt2->bind_param("i", $userId);
    $stmt2->execute();
    $result = $stmt2->get_result();
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    $stmt2->close();
} else {
    echo "User ID tidak ditemukan untuk reseller: $reseller";
    exit;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1" />
  <title>Profil Reseller</title>
  <link rel="icon" href="<?= $avatar ?>" />
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    html, body {
      margin: 0;
      padding: 0;
      width: 100%;
      overflow-x: hidden;
    }
    table {
      table-layout: fixed;
      width: 100%;
      word-wrap: break-word;
    }
    td, th {
      word-break: break-word;
    }
  </style>
</head>
<body class="bg-gray-900 text-white">
  <main class="max-w-full mx-auto px-1 py-1">

    <!-- Tab Navigasi -->
    <div class="w-full px-4 mb-14">
        <div class="flex space-x-4 border-b border-gray-700 text-sm -mb-2">
        <a href="?page=setting&tab=overview" class="px-2 pb-2 <?= $tab === 'overview' ? 'text-blue-400 border-b-2 border-blue-400' : 'hover:text-gray-300' ?>">Overview</a>
        <a href="?page=setting&tab=general" class="px-2 pb-2 <?= $tab === 'general' ? 'text-blue-400 border-b-2 border-blue-400' : 'hover:text-gray-300' ?>">General Settings</a>
        <a href="?page=setting&tab=advanced" class="px-2 pb-2 <?= $tab === 'advanced' ? 'text-blue-400 border-b-2 border-blue-400' : 'hover:text-gray-300' ?>">Advanced Settings</a>
        </div>
    </div>

    <!-- Konten Tab -->
    <div class="mt-8">
    <?php
    $allowedTabs = ['overview', 'general', 'advanced'];
    if (in_array($tab, $allowedTabs)) {
        $file = __DIR__ . "/setting/{$tab}.php";
        if (file_exists($file)) {
            include $file;
        } else {
            echo "<p class='text-red-400'>File tab '$tab.php' tidak ditemukan.</p>";
        }
    } else {
        echo "<p class='text-red-400'>Tab tidak valid: $tab</p>";
    }
    ?>
    </div>

  </main>
</body>
</html>

