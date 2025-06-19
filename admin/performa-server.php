<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$servers = [
    'RW-MARD'     => ['ip' => '203.194.113.140', 'port' => 443, 'path' => '/trojan-ws'],
    'SGDO-MARD1'  => ['ip' => '143.198.202.86',  'port' => 443, 'path' => '/trojan-ws'],
    'SGDO-2DEV'   => ['ip' => '178.128.60.185',  'port' => 443, 'path' => '/trojan-ws'],
];

function check_ws_xray($host, $port, $path) {
    $ch = curl_init("https://$host$path");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Connection: Upgrade",
        "Upgrade: websocket",
        "Sec-WebSocket-Version: 13",
        "Sec-WebSocket-Key: " . base64_encode(random_bytes(16)),
    ]);

    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error     = curl_error($ch);
    curl_close($ch);

    if ($http_code === 101) {
        return 'Aktif';
    } elseif ($error) {
        return 'Error: ' . $error;
    } else {
        return 'Mati';
    }
}

$results = [];
foreach ($servers as $name => $server) {
    $status = check_ws_xray($server['ip'], $server['port'], $server['path']);
    $results[$name] = [
        'ip'     => $server['ip'],
        'status' => $status,
        'color'  => $status === 'Aktif' ? 'green' : 'red'
    ];
}
?>

<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Status Xray WS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen p-6">

    <div class="max-w-4xl mx-auto bg-gray-800 rounded-lg p-6 shadow-lg">
        <h1 class="text-2xl font-semibold mb-4">Status WebSocket Xray (/trojan-ws)</h1>

        <table class="w-full table-auto border-collapse">
            <thead>
                <tr class="bg-gray-700">
                    <th class="p-3 text-left border-b border-gray-600">Server</th>
                    <th class="p-3 text-left border-b border-gray-600">IP</th>
                    <th class="p-3 text-left border-b border-gray-600">Status WS</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $name => $data): ?>
                    <tr class="hover:bg-gray-700">
                        <td class="p-3 border-b border-gray-700"><?= $name ?></td>
                        <td class="p-3 border-b border-gray-700"><?= $data['ip'] ?></td>
                        <td class="p-3 border-b border-gray-700 text-<?= $data['color'] ?>-400 font-bold"><?= $data['status'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="text-xs text-gray-400 mt-4">* Deteksi aktif jika server merespons handshake WebSocket pada path <code>/trojan-ws</code>.</p>
    </div>

</body>
</html>

