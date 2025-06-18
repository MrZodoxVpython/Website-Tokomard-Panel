<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

require __DIR__ . '/vendor/autoload.php'; // pastikan phpseclib sudah di-install

use phpseclib3\Net\SSH2;

// Ambil data koneksi dari POST/GET
$host = $_POST['host'] ?? $_GET['host'] ?? null;
$user = $_POST['user'] ?? $_GET['user'] ?? 'root';
$port = $_POST['port'] ?? $_GET['port'] ?? 22;
$password = $_POST['password'] ?? null;

$ttydPort = 7681; // Port ttyd jika ingin buka terminal web langsung

if (!$host) {
    echo "❌ Host tidak ditemukan.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Akses Terminal VPS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen p-8">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Akses Terminal: <?= htmlspecialchars($host) ?></h1>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $password): ?>
            <?php
            $ssh = new SSH2($host, (int)$port);
            if (!$ssh->login($user, $password)) {
                echo '<p class="text-red-400 mb-4">Login Gagal: Username/password salah.</p>';
            } else {
                echo '<p class="text-green-400 mb-4">✅ Login Berhasil ke ' . htmlspecialchars($host) . '</p>';
                echo '<h2 class="text-xl font-semibold mb-2">Output (contoh: uptime)</h2>';
                echo '<pre class="bg-black p-4 rounded-lg border border-gray-700 overflow-x-auto text-green-300">';
                echo htmlspecialchars($ssh->exec('uptime'));
                echo '</pre>';
            }
            ?>
        <?php else: ?>
            <p class="mb-4 text-gray-300">Masukkan password untuk login ke VPS (<?= htmlspecialchars($user) ?>@<?= htmlspecialchars($host) ?>)</p>
            <form method="post" class="space-y-4">
                <input type="hidden" name="host" value="<?= htmlspecialchars($host) ?>">
                <input type="hidden" name="user" value="<?= htmlspecialchars($user) ?>">
                <input type="hidden" name="port" value="<?= htmlspecialchars($port) ?>">

                <div>
                    <label class="block text-sm mb-1">Password:</label>
                    <input type="password" name="password" required class="w-full px-4 py-2 rounded bg-gray-800 border border-gray-600 text-white">
                </div>

                <button type="submit" class="bg-green-600 px-5 py-2 rounded hover:bg-green-700 transition font-semibold">
                    Login & Jalankan SSH
                </button>
            </form>
        <?php endif; ?>

        <div class="mt-8">
            <p class="text-sm text-gray-400 mb-2">Atau buka akses terminal via browser jika ttyd aktif:</p>
            <a href="http://<?= $host ?>:<?= $ttydPort ?>" target="_blank"
               class="inline-block bg-blue-600 px-5 py-2 rounded hover:bg-blue-700 font-semibold">
                Buka ttyd Terminal
            </a>
        </div>
    </div>
</body>
</html>

