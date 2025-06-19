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
    $manual_map = [
        'rw-mard.tokomard.store'     => 'Indonesia',
        'vpn-premium.tokomard.store' => 'Singapore',
        'sgdo-2dev.tokomard.store'   => 'Singapore',
    ];
    return $manual_map[$domain] ?? 'Tidak Diketahui';
}

function detect_device_info() {
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    $device = 'Desktop';
    $os = 'Unknown OS';
    $browser = 'Unknown Browser';

    if (preg_match('/mobile|iphone|android/', $ua)) $device = 'HP / Smartphone';
    if (preg_match('/tablet|ipad/', $ua)) $device = 'Tablet';

    if (strpos($ua, 'windows') !== false) $os = 'Windows';
    elseif (strpos($ua, 'android') !== false) $os = 'Android';
    elseif (strpos($ua, 'linux') !== false) $os = 'Linux';
    elseif (strpos($ua, 'mac') !== false) $os = 'MacOS';
    elseif (strpos($ua, 'iphone') !== false) $os = 'iOS';

    if (strpos($ua, 'firefox') !== false) $browser = 'Firefox';
    elseif (strpos($ua, 'chrome') !== false) $browser = 'Chrome';
    elseif (strpos($ua, 'safari') !== false && !strpos($ua, 'chrome')) $browser = 'Safari';
    elseif (strpos($ua, 'edge') !== false) $browser = 'Edge';
    elseif (strpos($ua, 'opr/') !== false || strpos($ua, 'opera') !== false) $browser = 'Opera';

    return [
        'device' => $device,
        'os' => $os,
        'browser' => $browser,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ];
}

function get_visitor_location() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $url = "http://ip-api.com/json/{$ip}?fields=status,country,regionName,city,zip,lat,lon,isp,timezone,query";
    $ctx = @file_get_contents($url);
    if (!$ctx) return null;
    $data = json_decode($ctx, true);
    return ($data['status'] === 'success') ? $data : null;
}

$deviceInfo = detect_device_info();
$visitorLoc = get_visitor_location();

$results = [];
foreach ($servers as $name => $domain) {
    $ws = check_xray_ws($domain);
    $country = get_country($domain);
    $results[] = ['name'=>$name,'host'=>$domain,'status'=>$ws['status'],'color'=>$ws['color'],'country'=>$country];
}
?>

<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Status & Info Pengunjung</title>
    <meta http-equiv="refresh" content="5">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen p-4 md:p-6">

    <!-- TABEL STATUS SERVER -->
    <div class="max-w-5xl mx-auto bg-gray-800 rounded-lg p-4 md:p-6 shadow-lg mb-6">
        <h1 class="text-xl md:text-2xl font-semibold mb-4 text-center">Status WebSocket Xray Server Tokomard</h1>
        <div class="overflow-x-auto">
            <table class="w-full table-auto border-collapse text-xs md:text-sm">
                <thead><tr class="bg-gray-700">
                    <th class="p-2 md:p-3">Nama Server</th><th class="p-2 md:p-3">Host</th><th class="p-2 md:p-3">Negara</th><th class="p-2 md:p-3">Status WS</th>
                </tr></thead>
                <tbody>
                    <?php foreach($results as $r): ?>
                    <tr class="hover:bg-gray-700">
                        <td class="p-2 md:p-3"><?=htmlspecialchars($r['name'])?></td>
                        <td class="p-2 md:p-3"><?=htmlspecialchars($r['host'])?></td>
                        <td class="p-2 md:p-3"><?=htmlspecialchars($r['country'])?></td>
                        <td class="p-2 md:p-3 text-<?=$r['color']?>-400 font-bold"><?=htmlspecialchars($r['status'])?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-xs text-gray-400 mt-4 text-center">* Auto refresh setiap 5 detik.</p>
    </div>

    <!-- TABEL INFO PENGUNJUNG -->
    <div class="max-w-5xl mx-auto bg-gray-800 rounded-lg p-4 md:p-6 shadow-lg">
        <h2 class="text-xl font-bold text-center mb-4">How Are You?</h2>
        <div class="overflow-x-auto">
            <table class="w-full table-auto border-collapse text-sm">
                <thead><tr class="bg-gray-700">
                    <th class="p-3">Informasi</th><th class="p-3">Detail</th>
                </tr></thead>
                <tbody>
                    <tr><td class="p-3 border-b border-gray-700">Device</td><td class="p-3 border-b border-gray-700"><?=htmlspecialchars($deviceInfo['device'])?></td></tr>
                    <tr><td class="p-3 border-b border-gray-700">OS</td><td class="p-3 border-b border-gray-700"><?=htmlspecialchars($deviceInfo['os'])?></td></tr>
                    <tr><td class="p-3 border-b border-gray-700">Browser</td><td class="p-3 border-b border-gray-700"><?=htmlspecialchars($deviceInfo['browser'])?></td></tr>
                    <tr><td class="p-3 border-b border-gray-700">User Agent</td><td class="p-3 border-b border-gray-700 text-xs break-words"><?=htmlspecialchars($deviceInfo['user_agent'])?></td></tr>
                    <?php if($visitorLoc): ?>
                        <tr><td class="p-3 border-b border-gray-700">IP Publik</td><td class="p-3 border-b border-gray-700"><?=$visitorLoc['query']?></td></tr>
                        <tr><td class="p-3 border-b border-gray-700">Negara</td><td class="p-3 border-b border-gray-700"><?=$visitorLoc['country']?></td></tr>
                        <tr><td class="p-3 border-b border-gray-700">Provinsi/Negara Bagian</td><td class="p-3 border-b border-gray-700"><?=$visitorLoc['regionName']?></td></tr>
                        <tr><td class="p-3 border-b border-gray-700">Kota</td><td class="p-3 border-b border-gray-700"><?=$visitorLoc['city']?></td></tr>
                        <tr><td class="p-3 border-b border-gray-700">Kode POS</td><td class="p-3 border-b border-gray-700"><?=$visitorLoc['zip']?></td></tr>
                        <tr><td class="p-3 border-b border-gray-700">Koordinat</td><td class="p-3 border-b border-gray-700"><?=$visitorLoc['lat']?>, <?=$visitorLoc['lon']?></td></tr>
                        <tr><td class="p-3 border-b border-gray-700">Zona Waktu</td><td class="p-3 border-b border-gray-700"><?=$visitorLoc['timezone']?></td></tr>
                        <tr><td class="p-3 border-b border-gray-700">ISP</td><td class="p-3 border-b border-gray-700"><?=$visitorLoc['isp']?></td></tr>
                    <?php else: ?>
                        <tr><td class="p-3 border-b border-gray-700">Lokasi</td><td class="p-3 border-b border-gray-700">Tidak tersedia</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>

