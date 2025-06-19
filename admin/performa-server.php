<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

function check_ws_xray($host, $port, $path) {
    $url = "https://$host$path";
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_PORT, $port);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 7);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Host: $host",
        "Connection: Upgrade",
        "Upgrade: websocket",
        "Sec-WebSocket-Version: 13",
        "Sec-WebSocket-Key: " . base64_encode(random_bytes(16)),
        "User-Agent: Mozilla/5.0"
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if (strpos($response, '101 Switching Protocols') !== false) {
        return ['status' => 'Aktif', 'color' => 'green'];
    } elseif ($err) {
        return ['status' => "Error: $err", 'color' => 'yellow'];
    } else {
        return ['status' => 'Mati', 'color' => 'red'];
    }
}

$servers = [
    'RW-MARD'     => ['host' => 'rw-mard.tokomard.store',    'port' => 443, 'path' => '/trojan-ws'],
    'SGDO-MARD1'  => ['host' => 'vpn-premium.tokomard.store', 'port' => 443, 'path' => '/trojan-ws'],
    'SGDO-2DEV'   => ['host' => 'sgdo-2dev.tokomard.store', 'port' => 443, 'path' => '/trojan-ws'],
];

$results = [];
foreach ($servers as $name => $srv) {
    $res = check_ws_xray($srv['host'], $srv['port'], $srv['path']);
    $results[] = [
        'name'  => $name,
        'host'  => $srv['host'],
        'status' => $res['status'],
        'color'  => $res['color']
    ];
}
?>

<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Status Xray WebSocket</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen p-6">

    <div class="max-w-4xl mx-auto bg-gray-800 rounded-lg p-6 shadow-lg">
        <h1 class="text-2xl font-semibold mb-4">Status WebSocket Xray (/trojan-ws)</h1>

        <table class="w-full table-auto border-collapse text-sm">
            <thead>
                <tr class="bg-gray-700">
                    <th class="p-3 text-left border-b border-gray-600">Nama Server</th>
                    <th class="p-3 text-left border-b border-gray-600">Host</th>
                    <th class="p-3 text-left border-b border-gray-600">Status WS</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row): ?>
                    <tr class="hover:bg-gray-700">
                        <td class="p-3 border-b border-gray-700"><?= htmlspecialchars($row['name']) ?></td>
                        <td class="p-3 border-b border-gray-700"><?= htmlspecialchars($row['host']) ?></td>
                        <td class="p-3 border-b border-gray-700 text-<?= $row['color'] ?>-400 font-bold">
                            <?= htmlspecialchars($row['status']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="text-xs text-gray-400 mt-4">* Status diukur berdasarkan handshake WebSocket (HTTP 101). Pastikan subdomain valid dan mengarah ke server.</p>
    </div>

</body>
</html>
