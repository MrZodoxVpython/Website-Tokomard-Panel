<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: /login.php");
    exit;
}

require '../koneksi.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    dieCyberpunk("❌ ID tidak valid.");
}

$id = (int)$_GET['id'];

// Cegah penghapusan akun sendiri
if ($_SESSION['user_id'] == $id) {
    dieCyberpunk("🚫 Akses Ditolak<br>⚠️ Tidak bisa menghapus akun Anda sendiri.<br>🛡️ Sistem perlindungan aktif.");
}

// Proses hapus
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: list-akun.php");
exit;

// Fungsi tampilan error bergaya cyberpunk
function dieCyberpunk($message) {
    echo <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>ACCESS DENIED</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-black text-green-400 font-mono text-center flex flex-col items-center justify-center h-screen">
  <div class="bg-gray-900 p-8 rounded-xl border-2 border-green-500 shadow-lg animate-pulse max-w-md">
    <h1 class="text-3xl mb-4 tracking-widest">🕶️ CYBER DEFENSE SYSTEM</h1>
    <p class="text-lg leading-relaxed">{$message}</p>
    <a href="list-akun.php" class="mt-6 inline-block text-green-300 border border-green-500 px-4 py-2 rounded hover:bg-green-600 hover:text-black transition">
      ← Kembali ke Daftar Akun
    </a>
  </div>
</body>
</html>
HTML;
    exit;
}
?>

