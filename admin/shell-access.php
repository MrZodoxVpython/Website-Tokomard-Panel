<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$output = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['command'])) {
    $command = $_POST['command'];

    // Optional: Batasi command yang diizinkan (HANYA jika ingin aman)
    // $allowed = ['ls', 'df -h', 'uptime', 'whoami'];
    // if (!in_array($command, $allowed)) {
    //     $output = "❌ Command tidak diizinkan.";
    // } else {
        $output = shell_exec($command . " 2>&1");
    // }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Shell Access - VPS Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen">
<div class="max-w-4xl mx-auto p-6">
    <h1 class="text-3xl font-bold mb-4 text-green-400">🖥 VPS Shell Access</h1>

    <form method="POST" class="mb-4">
        <label for="command" class="block mb-2 font-semibold">Masukkan Perintah:</label>
        <input type="text" name="command" id="command"
               class="w-full px-4 py-2 text-black rounded bg-gray-100 focus:outline-none"
               placeholder="contoh: ls -la /etc/xray" required>
        <button type="submit"
                class="mt-3 px-5 py-2 bg-green-600 hover:bg-green-700 rounded text-white font-semibold">
            Jalankan
        </button>
    </form>

    <?php if ($output): ?>
        <div class="bg-black p-4 rounded-lg text-green-300 whitespace-pre overflow-x-auto border border-gray-700">
            <strong>Output:</strong><br><?= htmlspecialchars($output) ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>

