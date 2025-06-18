<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

function isServerAlive($ip) {
    $ping = shell_exec("ping -c 1 -W 1 $ip");
    return (strpos($ping, '1 received') !== false || strpos($ping, '1 packets received') !== false);
}

$servers = [
    'RW-MARD' => [
        'ip' => '203.194.113.140',
        'ssh_user' => 'root',
        'ssh_port' => 22
    ],
    'SGDO-MARD1' => [
        'ip' => '143.198.202.86',
        'ssh_user' => 'root',
        'ssh_port' => 22
    ],
    'SGDO-2DEV' => [
        'ip' => '178.128.60.185',
        'ssh_user' => 'root',
        'ssh_port' => 22
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Performa Server</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen p-6">
    <h1 class="text-3xl font-bold mb-4 text-green-400">🖥  VPS Shell Access</h1>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($servers as $name => $data): ?>
            <?php $alive = isServerAlive($data['ip']); ?>
            <div class="bg-gray-800 p-4 rounded-lg shadow-lg">
                <h2 class="text-xl font-semibold mb-2"><?= $name; ?></h2>
                <p>IP: <span class="text-blue-300"><?= $data['ip'] ?></span></p>
                <p>Status:
                    <?php if ($alive): ?>
                        <span class="text-green-400 font-bold">Online</span>
                    <?php else: ?>
                        <span class="text-red-400 font-bold">Offline</span>
                    <?php endif; ?>
                </p>

                <!-- Form Akses Shell -->
                <form method="post" action="ssh-terminal.php" class="mt-4 space-y-3">
                    <input type="hidden" name="host" value="<?= htmlspecialchars($data['ip']); ?>">
                    <input type="hidden" name="user" value="<?= htmlspecialchars($data['ssh_user']); ?>">
                    <input type="hidden" name="port" value="<?= htmlspecialchars($data['ssh_port']); ?>">

                    <label class="block text-sm">Password Root:</label>
                    <input type="password" name="password" required
                        class="w-full p-2 rounded bg-gray-700 text-white"
                        placeholder="Masukkan password VPS" <?= !$alive ? 'disabled' : '' ?>>

                    <button type="submit"
                        class="w-full px-4 py-2 bg-blue-500 rounded hover:bg-blue-600 transition <?= !$alive ? 'opacity-50 cursor-not-allowed' : '' ?>"
                        <?= !$alive ? 'disabled' : '' ?>>
                        Akses Shell
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>

