<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

$vpsList = [
    'SGDO-2DEV' => '127.0.0.1', // local VPS
    'SGDO-MARD1' => '152.42.182.187',
    'RW-MARD1'   => '203.194.113.140'
];

$selectedVps = $_GET['vps'] ?? 'SGDO-2DEV';
$vpsIp = $vpsList[$selectedVps] ?? '127.0.0.1';
$menu = $_GET['menu'] ?? '';
$output = '';

function runVnstatCommand($option, $vpsIp) {
    $allowed = ["", "-5", "-h", "-d", "-m", "-y", "-t", "-hg", "-l", "-tr"];
    if (!in_array($option, $allowed)) {
        return "Perintah tidak valid.";
    }

    $isLocal = ($vpsIp === '127.0.0.1' || $vpsIp === 'localhost');
    $cmd = "vnstat $option 2>&1";
    if ($isLocal) {
        return shell_exec($cmd);
    } else {
        return shell_exec("ssh -o StrictHostKeyChecking=no root@$vpsIp '$cmd'");
    }
}

switch ($menu) {
    case '1':
        $output = runVnstatCommand('', $vpsIp);
        if (trim($output) === '') {
            $output = "❌ Tidak ada output.\n\nCoba jalankan:\n\nsudo -u www-data vnstat";
        }
        break;
    case '2': $output = runVnstatCommand('-5', $vpsIp); break;
    case '3': $output = runVnstatCommand('-h', $vpsIp); break;
    case '4': $output = runVnstatCommand('-d', $vpsIp); break;
    case '5': $output = runVnstatCommand('-m', $vpsIp); break;
    case '6': $output = runVnstatCommand('-y', $vpsIp); break;
    case '7': $output = runVnstatCommand('-t', $vpsIp); break;
    case '8': $output = runVnstatCommand('-hg', $vpsIp); break;
    case '9':
        $output = "❌ <strong>Live monitoring tidak bisa ditampilkan di web</strong><br><br>
Silakan buka terminal dan jalankan perintah berikut:<br>
<code>vnstat -l</code><br><br>
Perintah ini bersifat streaming (live) dan hanya dapat digunakan di terminal.";
        break;
    case '10': $output = runVnstatCommand('-tr', $vpsIp); break;
    default:
        $output = "Silakan pilih menu di atas untuk melihat statistik penggunaan bandwidth.";
}

function isActive($menuId) {
    global $menu;
    return $menu === $menuId ? 'bg-green-700' : 'bg-gray-800';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Statistik VPS</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-black text-green-400 font-mono min-h-screen p-6">
  <div class="max-w-5xl mx-auto">
    <h1 class="text-3xl mb-4 text-green-300 font-bold">📊 Statistik Penggunaan Internet VPS</h1>

    <form method="get" class="mb-6">
      <label for="vps" class="block mb-2 font-semibold text-green-300">Pilih VPS:</label>
      <select id="vps" name="vps" class="bg-gray-800 text-green-300 p-2 rounded border border-green-400" onchange="this.form.submit()">
        <?php foreach ($vpsList as $label => $ip): ?>
          <option value="<?= $label ?>" <?= $selectedVps === $label ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </form>

    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
      <?php for ($i = 1; $i <= 10; $i++): ?>
        <a href="?vps=<?= urlencode($selectedVps) ?>&menu=<?= $i ?>" class="<?= isActive((string)$i) ?> hover:bg-green-600 text-center p-3 rounded border border-green-400 transition">
          <?= $i ?> <?= ["Total", "5 Menit", "Jam", "Harian", "Bulanan", "Tahunan", "Tertinggi", "Grafik", "Live", "Live Trafik"][$i-1] ?>
        </a>
      <?php endfor; ?>
    </div>

    <div class="bg-gray-900 p-4 rounded-lg whitespace-pre overflow-x-auto border border-green-500">
      <?php
        if ($menu === '9') {
            echo $output;
        } else {
            echo nl2br(htmlspecialchars($output));
        }
      ?>
    </div>

    <div class="mt-6">
      <a href="/dashboard.php" class="inline-block bg-blue-700 hover:bg-blue-600 text-white px-4 py-2 rounded transition">
        ⬅ Kembali ke Dashboard
      </a>
    </div>
  </div>
</body>
</html>

