<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

function runVnstatCommand($option) {
    $allowed = ["", "-5", "-h", "-d", "-m", "-y", "-t", "-hg", "-l", "-tr"];
    if (!in_array($option, $allowed)) {
        return "Perintah tidak valid.";
    }
    return shell_exec("vnstat $option 2>&1");
}

$menu = $_GET['menu'] ?? '';
$output = '';

switch ($menu) {
  //case '1': $output = runVnstatCommand(); break;
     case '1':
    $output = shell_exec("vnstat 2>&1");
    if (trim($output) === '') {
        $output = "❌ Tidak ada output.\n\nCoba jalankan:\n\nsudo -u www-data vnstat";
    }
    break;
    case '2': $output = runVnstatCommand('-5'); break;
    case '3': $output = runVnstatCommand('-h'); break;
    case '4': $output = runVnstatCommand('-d'); break;
    case '5': $output = runVnstatCommand('-m'); break;
    case '6': $output = runVnstatCommand('-y'); break;
    case '7': $output = runVnstatCommand('-t'); break;
    case '8': $output = runVnstatCommand('-hg'); break;
    case '9':
    $output = "❌ <strong>Live monitoring tidak bisa ditampilkan di web</strong><br><br>
Silakan buka terminal dan jalankan perintah berikut:<br>
<code>vnstat -l</code><br><br>
Perintah ini bersifat streaming (live) dan hanya dapat digunakan di terminal.";
    break;
 // case '9': $output = runVnstatCommand('-l'); break;
    case '10': $output = runVnstatCommand('-tr'); break;
    default: $output = "Silakan pilih menu di atas untuk melihat statistik penggunaan bandwidth.";
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

    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
      <a href="?menu=1" class="<?= isActive('1') ?> hover:bg-green-600 text-center p-3 rounded border border-green-400 transition">1️⃣ Total Bandwidth Tersisa</a>
      <a href="?menu=2" class="<?= isActive('2') ?> hover:bg-green-600 text-center p-3 rounded border border-green-400 transition">2️⃣ Setiap 5 Menit</a>
      <a href="?menu=3" class="<?= isActive('3') ?> hover:bg-green-600 text-center p-3 rounded border border-green-400 transition">3️⃣ Setiap Jam</a>
      <a href="?menu=4" class="<?= isActive('4') ?> hover:bg-green-600 text-center p-3 rounded border border-green-400 transition">4️⃣ Setiap Hari</a>
      <a href="?menu=5" class="<?= isActive('5') ?> hover:bg-green-600 text-center p-3 rounded border border-green-400 transition">5️⃣ Setiap Bulan</a>
      <a href="?menu=6" class="<?= isActive('6') ?> hover:bg-green-600 text-center p-3 rounded border border-green-400 transition">6️⃣ Setiap Tahun</a>
      <a href="?menu=7" class="<?= isActive('7') ?> hover:bg-green-600 text-center p-3 rounded border border-green-400 transition">7️⃣ Penggunaan Tertinggi</a>
      <a href="?menu=8" class="<?= isActive('8') ?> hover:bg-green-600 text-center p-3 rounded border border-green-400 transition">8️⃣ Grafik Per Jam</a>
      <a href="?menu=9" class="<?= isActive('9') ?> hover:bg-red-600 text-center p-3 rounded border border-red-400 transition">9️⃣ Live Sekarang</a>
      <a href="?menu=10" class="<?= isActive('10') ?> hover:bg-red-600 text-center p-3 rounded border border-red-400 transition">🔟 Live Trafik (5s)</a>
    </div>

    <div class="bg-gray-900 p-4 rounded-lg whitespace-pre overflow-x-auto border border-green-500">
      <?php
        if ($menu === '9') {
            echo $output; // tampilkan langsung HTML
        } else {
            echo nl2br(htmlspecialchars($output)); // amankan untuk text vnstat biasa
        }
      ?>
    </div>


    <div class="mt-6">
      <a href="/dashboard.php" class="inline-block bg-blue-700 hover:bg-blue-600 text-white px-4 py-2 rounded transition">
        ⬅️ Kembali ke Dashboard
      </a>
    </div>
  </div>
</body>
</html>
