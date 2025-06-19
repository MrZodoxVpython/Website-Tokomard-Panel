<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Daftar server (gunakan domain valid yang dipasang di Xray config)
$servers = [
    'RW-MARD'     => 'rw-mard.tokomard.store',
    'SGDO-MARD1'  => 'vpn-premium.tokomard.store',
    'SGDO-2DEV'   => 'sgdo-2dev.tokomard.store',
];

// Fungsi cek status WebSocket Xray via fsockopen + upgrade handshake
function check_xray_ws($host, $port = 443, $path = '/trojan-ws') {
    $fp = @fsockopen("ssl://$host", $port, $errno, $errstr, 5);
    if (!$fp) {
        return ['status' => 'Tidak Terkoneksi', 'color' => 'red'];
    }

    $key = base64_encode(random_bytes(16));
    $headers = "GET $path HTTP/1.1\r\n"
             . "Host: $host\r\n"
             . "Upgrade: websocket\r\n"
             . "Connection: Upgrade\r\n"
             . "Sec-WebSocket-Key: $key\r\n"
             . "Sec-WebSocket-Version: 13\r\n\r\n";

    fwrite($fp, $headers);
    $response = fread($fp, 2048);
    fclose($fp);

    if (strpos($response, '101 Switching Protocols') !== false) {
        return ['status' => 'Aktif', 'color' => 'green'];
    }

    return ['status' => 'Tidak Aktif', 'color' => 'red'];
}

$results = [];
foreach ($servers as $name => $domain) {
    $res = check_xray_ws($domain);
    $results[] = [
        'name' => $name,
        'host' => $domain,
        'status' => $res['status'],
        'color' => $res['color']
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
        <h1 class="text-2xl font-semibold mb-4">Status WebSocket Inject Tunneling Server Tokomard</h1>

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

        <p class="text-xs text-gray-400 mt-4">* Status dihitung dari respon WebSocket handshake 101 Switching Protocols.</p>
    </div>

</body>
</html>

