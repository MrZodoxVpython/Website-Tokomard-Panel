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
    'RW-MAR1' => [
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
        'ip' => '203.194.113.140',
        'ssh_user' => 'root',
        'ssh_port' => 22
    ]
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performa Server</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen p-6">
    <h1 class="text-3xl font-bold mb-6">Performa Server</h1>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($servers as $name => $data): ?>
            <?php $alive = isServerAlive($data['ip']); ?>
            <div class="bg-gray-800 p-4 rounded-lg shadow-lg">
                <h2 class="text-xl font-semibold mb-2"><?php echo $name; ?></h2>
                <p>Status: 
                    <?php if ($alive): ?>
                        <span class="text-green-400 font-bold">Online</span>
                    <?php else: ?>
                        <span class="text-red-400 font-bold">Offline</span>
                    <?php endif; ?>
                </p>
                <form method="post" action="ssh-terminal.php" class="mt-4">
                    <input type="hidden" name="host" value="<?php echo $data['ip']; ?>">
                    <input type="hidden" name="user" value="<?php echo $data['ssh_user']; ?>">
                    <input type="hidden" name="port" value="<?php echo $data['ssh_port']; ?>">
                    <button type="submit" class="mt-2 px-4 py-2 bg-blue-500 rounded hover:bg-blue-600 transition"
                        <?php if (!$alive) echo 'disabled class="opacity-50 cursor-not-allowed"'; ?>>
                        Akses Shell
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>

