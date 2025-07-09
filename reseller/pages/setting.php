<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../koneksi.php';

// Validasi session
if (!isset($_SESSION['username'])) {
    echo "Session reseller tidak ditemukan.";
    exit;
}

$reseller = $_SESSION['username'];
// Ambil avatar dari JSON
$defaultAvatar = 'uploads/avatars/default.png';
$avatarJsonPath = __DIR__ . '/../uploads/avatar.json';
$avatar = $defaultAvatar;

if ($reseller && file_exists($avatarJsonPath)) {
    $avatarData = json_decode(file_get_contents($avatarJsonPath), true);
    if (isset($avatarData[$reseller])) {
        $customAvatar = __DIR__ . '/../' . $avatarData[$reseller];
        if (file_exists($customAvatar)) {
            $avatar = $avatarData[$reseller]; // relative path
        }
    }
}
$email = '';
$account_id = '';
$balance = 0;
$transactions = [];

// Ambil ID dan saldo user
$stmt = $conn->prepare("SELECT id, email, saldo FROM users WHERE username = ?");
$stmt->bind_param("s", $reseller);
$stmt->execute();
$userResult = $stmt->get_result();

if ($userRow = $userResult->fetch_assoc()) {
    $userId = $userRow['id'];
    $email = $userRow['email'];
    $balance = $userRow['saldo'];
    $account_id = 'ID-' . str_pad($userId, 3, '0', STR_PAD_LEFT);

    // Ambil transaksi user
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
// Validasi tab dari URL

// List tab yang diperbolehkan
$allowedTabs = ['general', 'advanced'];

// Gunakan 'overview' sebagai default jika 'tab' tidak tersedia di URL
$tab = $_GET['tab'] ?? 'overview';

// Validasi agar hanya tab yang diizinkan
if (!in_array($tab, $allowedTabs)) {
    $tab = 'overview';
}
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
  <main class="max-ful mx-auto px-1 py-1">

<!-- Header: Profil dan Avatar -->
<div class="flex flex-col md:flex-row items-center md:items-start gap-4 mb-10">
  <!-- Box Profil -->
  <div class="bg-gray-800 rounded-lg p-4 w-full pb-7 md:w-1/3 text-center shadow border border-gray-700 overflow-hidden">
    <img src="<?= $avatar ?>?v=<?= time() ?>" alt="Avatar" class="w-24 h-24 mx-auto rounded-full" />
    <h2 class="text-xl font-semibold mt-4"><?= $reseller ?></h2>
    <p class="text-gray-400 text-sm"><?= $email ?></p>

    <div class="text-center mt-4 text-left text-sm">
      <p><strong>Account ID:</strong> <?= $account_id ?></p>
      <p><strong>Email:</strong> <?= $email ?></p>
    </div>
  </div>

  <!-- Konten Kanan -->
  <div class="flex-1 w-full space-y-6">

    <!-- Tabs -->
    <div class="w-full px-4">
	<div class="flex space-x-4 border-b border-gray-700 text-sm -mb-2">
  	<a href="?page=setting&tab=overview" class="px-2 pb-2 <?= $tab === 'overview' ? 'text-blue-400 border-b-2 border-blue-400' : 'hover:text-gray-300' ?>">Overview</a>
  	<a href="?page=setting&tab=general" class="px-2 pb-2 <?= $tab === 'general' ? 'text-blue-400 border-b-2 border-blue-400' : 'hover:text-gray-300' ?>">General Settings</a>
  	<a href="?page=setting&tab=advanced" class="px-2 pb-2 <?= $tab === 'advanced' ? 'text-blue-400 border-b-2 border-blue-400' : 'hover:text-gray-300' ?>">Advanced Settings</a>
        </div>
    </div>

    <!-- Balance & Reseller Box -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 w-full">
      <div class="flex flex-col justify-center items-center text-center bg-green-500/10 text-green-300 p-14 rounded-lg shadow border border-green-400/30">
        <h3 class="text-sm font-semibold mb-1">Balance</h3>
        <p class="text-2xl font-bold">Rp. <?= number_format($balance, 0, ',', '.') ?></p>
        <p class="text-xs text-gray-400">Earn reward points with every purchase.</p>
      </div>

      <div class="text-center bg-green-600 text-white p-8 rounded-lg shadow border border-green-400">
        <h1 class="text-7xl py-4">🏆</h1>
        <h3 class="text-sm font-semibold mb-1">Reseller</h3>
        <p class="text-sm">Keep up to date with your account.</p>
      </div>
    </div>

  </div>
</div>

<!-- Riwayat Transaksi -->
<!-- Riwayat Transaksi -->
<div class="bg-gray-800 p-4 -mt-6 rounded-lg shadow border max-w-full border-gray-700">
  <div class="flex justify-between items-center mb-3">
    <h3 class="text-lg font-semibold">Transaction History</h3>
    <input type="text" placeholder="Search Transaction" class="bg-gray-900 text-sm rounded px-3 py-1 border border-gray-600 focus:outline-none" />
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-[11px] sm:text-sm border-collapse table-fixed">
      <thead class="bg-gray-100 dark:bg-gray-700">
        <tr>
          <th class="px-2 md:px-4 py-2 w-[16%] text-left">TYPE</th>
          <th class="px-2 md:px-4 py-2 w-[25%] text-left">STATUS</th>
          <th class="px-2 md:px-4 py-2 w-[25%] text-left">AMOUNT</th>
          <th class="px-2 md:px-4 py-2 w-[26%] text-left">DETAIL</th>
          <th class="px-2 md:px-4 py-2 w-[25%] text-left">DATE</th>
        </tr>
      </thead>
      <tbody class="text-gray-300">
        <?php foreach ($transactions as $trx): ?>
        <tr class="border-t border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
          <td class="px-2 md:px-4 py-2 w-[16%]"><?= $trx['type'] ?></td>
          <td class="px-2 md:px-4 py-2 w-[25%] text-green-400 font-semibold"><?= $trx['status'] ?></td>
          <td class="px-2 md:px-4 py-2 w-[25%]">Rp. <?= number_format($trx['amount'], 0, ',', '.') ?></td>
          <td class="px-2 md:px-4 py-2 w-[26%]"><?= $trx['detail'] ?></td>
          <td class="px-2 md:px-4 py-2 w-[25%]"><?= $trx['date'] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<!-- Include content tab -->
<div class="mt-8">
<?php
    // Include konten sesuai tab
    if ($tab === 'general') {
        include 'general.php';
    } elseif ($tab === 'advanced') {
        include 'advanced.php';
    } else {
        // TIDAK include apa pun, karena overview = setting.php itu sendiri
        // Jadi cukup tampilkan pesan kosong atau abaikan
        echo ''; // atau kamu bisa tampilkan konten tambahan overview di sini
    }
?>
</div>
  </main>
</body>
</html>

