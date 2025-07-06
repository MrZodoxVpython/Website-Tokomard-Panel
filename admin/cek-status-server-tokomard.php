<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
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
        ? ['status' => 'Connected!', 'color' => 'green']
        : ['status' => 'Connecting..', 'color' => 'red'];
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
    $url = "http://ip-api.com/json/{$ip}?fields=status,country,regionName,city,zip,lat,lon,isp,timezone,query,org,as";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta property="og:title" content="Check status inject tunneling server tokomard.">
    <meta property="og:description" content="Website Tokomard untuk cek status konek atau tidak nya injek di server">
    <meta property="og:image" content="https://i.imgur.com/q3DzxiB.png">
    <meta property="og:url" content="https://panel.tokomard.store/">
    <meta property="og:type" content="website">
    <title>Status Server & Info Pengunjung</title>
    <link rel="SHORTCUT ICON" href="https://i.imgur.com/q3DzxiB.png">
    <meta http-equiv="refresh" content="5">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen p-4 md:p-6 space-y-6">

<!-- STATUS SERVER -->
<div class="max-w-6xl mx-auto bg-gray-800 rounded-lg p-4 md:p-6 shadow-lg mb-6">
    <h1 class="text-xl md:text-2xl font-semibold mb-4 text-center">Status Server Tokomard</h1>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-700 text-sm text-left">
            <thead class="bg-gray-700">
                <tr>
                    <th class="p-3 font-medium">Nama Server</th>
                    <th class="p-3 font-medium">Host</th>
                    <th class="p-3 font-medium">Negara</th>
                    <th class="p-3 font-medium">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-600">
                <?php foreach($results as $r): ?>
                <tr class="hover:bg-gray-700">
                    <td class="p-3"><?= htmlspecialchars($r['name']) ?></td>
                    <td class="p-3"><?= htmlspecialchars($r['host']) ?></td>
                    <td class="p-3"><?= htmlspecialchars($r['country']) ?></td>
                    <td class="p-3 font-bold text-<?= $r['color'] ?>-400"><?= htmlspecialchars($r['status']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="text-xs text-gray-400 mt-4 text-center">
        * Pengukuran status tunneling ditentukan dari respon WebSocket handshake 101 Switching Protocols.<br>
        * Pengecekan status dilakukan otomatis tiap 5 detik, jika status connecting selama 1 menit penuh, maka dipastikan server = DOWN.
    </p>
</div>

<!-- INFO PENGUNJUNG -->
<div class="max-w-6xl mx-auto bg-gray-800 rounded-lg p-4 md:p-6 shadow-lg">
    <h2 class="text-xl font-bold text-center mb-4">Who Are You?</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-700 text-sm">
            <thead class="bg-gray-700 text-left">
                <tr>
                    <th class="p-3 font-medium">Jenis</th>
                    <th class="p-3 font-medium">Detail</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-600">
                <tr><td class="p-3">Device</td><td class="p-3"><?= htmlspecialchars($deviceInfo['device']) ?></td></tr>
                <tr><td class="p-3">OS</td><td class="p-3"><?= htmlspecialchars($deviceInfo['os']) ?></td></tr>
                <tr><td class="p-3">Browser</td><td class="p-3"><?= htmlspecialchars($deviceInfo['browser']) ?></td></tr>
                <tr><td class="p-3">User Agent</td><td class="p-3 break-all text-xs"><?= htmlspecialchars($deviceInfo['user_agent']) ?></td></tr>

                <?php if ($visitorLoc): ?>
                    <tr><td class="p-3">IP Publik</td><td class="p-3"><?= htmlspecialchars($visitorLoc['query']) ?></td></tr>
                    <tr><td class="p-3">Negara</td><td class="p-3"><?= htmlspecialchars($visitorLoc['country']) ?></td></tr>
                    <tr><td class="p-3">Provinsi</td><td class="p-3"><?= htmlspecialchars($visitorLoc['regionName']) ?></td></tr>
                    <tr><td class="p-3">Kota</td><td class="p-3"><?= htmlspecialchars($visitorLoc['city']) ?></td></tr>
                    <tr><td class="p-3">Kode POS</td><td class="p-3"><?= htmlspecialchars($visitorLoc['zip']) ?></td></tr>
                    <tr><td class="p-3">Koordinat</td><td class="p-3"><?= htmlspecialchars($visitorLoc['lat']) ?>, <?= htmlspecialchars($visitorLoc['lon']) ?></td></tr>
                    <tr><td class="p-3">Zona Waktu</td><td class="p-3"><?= htmlspecialchars($visitorLoc['timezone']) ?></td></tr>
                    <tr><td class="p-3">ISP</td><td class="p-3"><?= htmlspecialchars($visitorLoc['isp']) ?></td></tr>
                <?php else: ?>
                    <tr><td class="p-3">Lokasi</td><td class="p-3">Tidak tersedia</td></tr>
                <?php endif; ?>

                <tr><td class="p-3">Layar</td><td class="p-3" id="js-screen">—</td></tr>
                <tr><td class="p-3">Bahasa</td><td class="p-3" id="js-lang">—</td></tr>
                <tr><td class="p-3">RAM (GB)</td><td class="p-3" id="js-memory">—</td></tr>
                <tr><td class="p-3">CPU Cores</td><td class="p-3" id="js-cpu">—</td></tr>
                <tr><td class="p-3">Koneksi</td><td class="p-3" id="js-online">—</td></tr>
                <tr><td class="p-3">Waktu Lokal</td><td class="p-3" id="js-localtime">—</td></tr>
                <tr><td class="p-3">GPS</td><td class="p-3" id="js-gps">—</td></tr>
            </tbody>
        </table>
    </div>
</div>

    <script>
        document.getElementById("js-screen").innerText = `${screen.width} x ${screen.height}`;
        document.getElementById("js-lang").innerText = navigator.language || 'N/A';
        document.getElementById("js-memory").innerText = navigator.deviceMemory || '—';
        document.getElementById("js-cpu").innerText = navigator.hardwareConcurrency || '—';
        document.getElementById("js-online").innerText = navigator.onLine ? "Online" : "Offline";
        document.getElementById("js-localtime").innerText = new Date().toLocaleString();

        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const lat = pos.coords.latitude.toFixed(4);
                    const lon = pos.coords.longitude.toFixed(4);
                    document.getElementById("js-gps").innerText = `${lat}, ${lon}`;
                },
                () => {
                    document.getElementById("js-gps").innerText = "Tidak diizinkan";
                }
            );
        } else {
            document.getElementById("js-gps").innerText = "Tidak didukung";
        }
    </script>
<?php include '../templates/footer.php'; ?>

</body>
</html>

