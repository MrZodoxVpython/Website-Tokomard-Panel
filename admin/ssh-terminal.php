<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$host = $_POST['host'] ?? '';
$user = $_POST['user'] ?? 'root';
$port = $_POST['port'] ?? 22;
$password = $_POST['password'] ?? '';

$command = $_POST['command'] ?? '';
$output = '';
$domain = '[Tidak diketahui]';
$ipAktif = '[Tidak diketahui]';
$ssh_error = false;

// 🔐 Cek ekstensi SSH
if (!function_exists('ssh2_connect')) {
    $output = '[❌ PHP tidak memiliki ekstensi ssh2]';
    $ssh_error = true;
} else {
    $conn = @ssh2_connect($host, $port);
    if ($conn && @ssh2_auth_password($conn, $user, $password)) {

        // Ambil Domain VPS
        $stream1 = ssh2_exec($conn, 'cat /etc/xray/domain');
        stream_set_blocking($stream1, true);
        $domain = trim(stream_get_contents($stream1)) ?: '[Tidak ditemukan]';

        // Ambil IP aktif VPS
        $stream2 = ssh2_exec($conn, 'hostname -I');
        stream_set_blocking($stream2, true);
        $ipAktif = trim(stream_get_contents($stream2)) ?: '[Tidak ditemukan]';

        // Jalankan perintah jika ada
        if (!empty($command)) {
            $stream3 = ssh2_exec($conn, $command);
            stream_set_blocking($stream3, true);
            $output = stream_get_contents($stream3);
        }

    } else {
        $output = '[❌ Gagal SSH ke VPS - periksa password, port, atau status host]';
        $ssh_error = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shell Terminal - <?= htmlspecialchars($host) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen p-6">
    <h1 class="text-2xl font-bold mb-4">🖥 Akses Shell: <?= htmlspecialchars($host) ?></h1>

    <p class="text-green-400 mb-1">🌐 Domain VPS: <?= htmlspecialchars($domain) ?></p>
    <p class="text-blue-400 mb-4">🧪 IP Aktif VPS: <?= htmlspecialchars($ipAktif) ?></p>

    <form method="post" class="bg-gray-800 p-6 rounded shadow w-full max-w-3xl mb-4">
        <input type="hidden" name="host" value="<?= htmlspecialchars($host) ?>">
        <input type="hidden" name="user" value="<?= htmlspecialchars($user) ?>">
        <input type="hidden" name="port" value="<?= htmlspecialchars($port) ?>">
        <input type="hidden" name="password" value="<?= htmlspecialchars($password) ?>">

        <label class="block mb-2">Masukkan Perintah:</label>
        <input type="text" name="command" autofocus required placeholder="Contoh: ls -lah /etc"
               class="w-full p-2 mb-4 rounded bg-gray-700 text-white">

        <button type="submit" class="bg-blue-600 px-4 py-2 rounded hover:bg-blue-700 w-full">Jalankan</button>
    </form>

    <?php if (!empty($command) || $ssh_error): ?>
        <div class="bg-black text-green-400 font-mono p-6 rounded shadow w-full max-w-3xl overflow-auto">
            <p class="mb-2 text-sm text-yellow-400">Perintah: <code><?= htmlspecialchars($command ?: '[Tidak ada]') ?></code></p>
            <pre><?= htmlspecialchars($output ?: '[Perintah tidak menghasilkan output]') ?></pre>
        </div>
    <?php endif; ?>
</body>
</html>

