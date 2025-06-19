<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$servers = [
    'RW-MARD'     => 'rw-mard.tokomard.store',
    'SGDO-MARD1'  => 'vpn-premium.tokomard.store',
    'SGDO-2DEV'   => 'sgdo-2dev.tokomard.store',
];

function check_xray_ws($host, $port = 443, $path = '/trojan-ws') {
    $fp = @fsockopen("ssl://$host", $port, $errno, $errstr, 5);
    if (!$fp) return ['status' => 'Tidak Terkoneksi', 'color' => 'red'];

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

    return (strpos($response, '101 Switching Protocols') !== false)
        ? ['status' => 'Aktif', 'color' => 'green']
        : ['status' => 'Tidak Aktif', 'color' => 'red'];
}

function get_country($domain) {
    $ip = @file_get_contents("https://{$domain}/ipinfo.txt");
    if (!$ip) return 'Tidak Diketahui';

    $url = "https://ipapi.co/{$ip}/country_name/";
    $country = @file_get_contents($url);
    return $country ?: 'Tidak Diketahui';
}


$results = [];
foreach ($servers as $name => $domain) {
    $ws = check_xray_ws($domain);
    $country = get_country($domain);

    $results[] = [
        'name' => $name,
        'host' => $domain,
        'status' => $ws['status'],
        'color' => $ws['color'],
        'country' => $country
    ];
}
?>

<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Status Xray WebSocket</title>
    <meta http-equiv="refresh" content="5">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen p-4 md:p-6">

    <div class="max-w-5xl mx-auto bg-gray-800 rounded-lg p-4 md:p-6 shadow-lg">
        <h1 class="text-xl md:text-2xl font-semibold mb-4 text-center">Status Inject Tunneling Server Tokomard</h1>

        <div class="overflow-x-auto">
            <table class="w-full table-auto border-collapse text-xs md:text-sm">
                <thead>
                    <tr class="bg-gray-700">
                        <th class="p-2 md:p-3 text-left border-b border-gray-600">Nama Server</th>
                        <th class="p-2 md:p-3 text-left border-b border-gray-600">Host</th>
                        <th class="p-2 md:p-3 text-left border-b border-gray-600">Negara</th>
                        <th class="p-2 md:p-3 text-left border-b border-gray-600">Status WS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): ?>
                        <tr class="hover:bg-gray-700">
                            <td class="p-2 md:p-3 border-b border-gray-700"><?= htmlspecialchars($row['name']) ?></td>
                            <td class="p-2 md:p-3 border-b border-gray-700"><?= htmlspecialchars($row['host']) ?></td>
                            <td class="p-2 md:p-3 border-b border-gray-700"><?= htmlspecialchars($row['country']) ?></td>
                            <td class="p-2 md:p-3 border-b border-gray-700 text-<?= $row['color'] ?>-400 font-bold">
                                <?= htmlspecialchars($row['status']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <p class="text-xs text-gray-400 mt-4 text-center">
            * Status Tunneling/Inject connect atau tidak pada setiap server ditentukan dari respon WebSocket handshake 101 Switching Protocols. <br>
            * Pengecekan Status Tunneling akan otomatis direfresh setiap 5 detik.
        </p>
    </div>

</body>
</html>

