<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">
  <meta property="og:title" content="Check status inject tunneling server tokomard.">
  <meta property="og:description" content="Website Tokomard untuk cek status konek atau tidak nya injek di server">
  <meta property="og:image" content="https://i.imgur.com/q3DzxiB.png">
  <meta property="og:url" content="https://panel.tokomard.store/">
  <meta property="og:type" content="website">
  <title>Status Server & Info Pengunjung</title>
  <link rel="shortcut icon" href="https://i.imgur.com/q3DzxiB.png">
  <meta http-equiv="refresh" content="5">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    html, body {
      margin: 0;
      padding: 0;
      width: 100%;
      overflow-x: hidden;
    }
    table {
      table-layout: fixed;
      width: 100%;
      word-wrap: break-word;
    }
    td, th {
      word-break: break-word;
    }
  </style>
</head>
<body class="bg-gray-900 text-white w-full">

<!-- STATUS SERVER -->
<div class="bg-gray-800 p-4 w-full max-w-full">
  <h1 class="text-xl font-semibold mb-4 text-center">Status Server Tokomard</h1>
  <div class="overflow-x-auto w-full max-w-full">
    <table class="text-sm text-left divide-y divide-gray-700 w-full max-w-full">
      <thead class="bg-gray-700 text-white">
        <tr>
          <th class="p-2">Nama</th>
          <th class="p-2">Host</th>
          <th class="p-2">Negara</th>
          <th class="p-2">Status</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-600">
        <?php foreach ($results as $r): ?>
        <tr>
          <td class="p-2 break-words"><?= $r['name'] ?></td>
          <td class="p-2 break-words"><?= $r['host'] ?></td>
          <td class="p-2 break-words"><?= $r['country'] ?></td>
          <td class="p-2 text-<?= $r['color'] ?>-400 font-bold break-words"><?= $r['status'] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="text-xs text-gray-400 mt-4 text-center">
    * Status ditentukan dari respon 101 Switching Protocols WebSocket.
  </p>
</div>

<!-- INFO PENGUNJUNG -->
<div class="bg-gray-800 p-4 w-full mt-6 rounded-lg">
  <h2 class="text-xl font-bold text-center text-white mb-4">Who Are You?</h2>
  <div class="overflow-x-auto w-full">
    <table class="table-auto text-sm text-white w-full">
      <thead class="bg-gray-700">
        <tr>
          <th class="p-2 text-left whitespace-nowrap w-32">Jenis</th>
          <th class="p-2 text-left">Detail</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-600">
        <tr><td class="p-2">Device</td><td class="p-2"><?= $deviceInfo['device'] ?></td></tr>
        <tr><td class="p-2">OS</td><td class="p-2"><?= $deviceInfo['os'] ?></td></tr>
        <tr><td class="p-2">Browser</td><td class="p-2"><?= $deviceInfo['browser'] ?></td></tr>
        <tr><td class="p-2">User Agent</td><td class="p-2 text-xs break-all"><?= $deviceInfo['user_agent'] ?></td></tr>
        <?php if ($visitorLoc): ?>
        <tr><td class="p-2">IP</td><td class="p-2"><?= $visitorLoc['query'] ?></td></tr>
        <tr><td class="p-2">Negara</td><td class="p-2"><?= $visitorLoc['country'] ?></td></tr>
        <tr><td class="p-2">Provinsi</td><td class="p-2"><?= $visitorLoc['regionName'] ?></td></tr>
        <tr><td class="p-2">Kota</td><td class="p-2"><?= $visitorLoc['city'] ?></td></tr>
        <tr><td class="p-2">Kode POS</td><td class="p-2"><?= $visitorLoc['zip'] ?></td></tr>
        <tr><td class="p-2">Koordinat</td><td class="p-2"><?= $visitorLoc['lat'] ?>, <?= $visitorLoc['lon'] ?></td></tr>
        <tr><td class="p-2">Zona</td><td class="p-2"><?= $visitorLoc['timezone'] ?></td></tr>
        <tr><td class="p-2">ISP</td><td class="p-2"><?= $visitorLoc['isp'] ?></td></tr>
        <?php endif; ?>
        <tr><td class="p-2">Layar</td><td class="p-2" id="js-screen">—</td></tr>
        <tr><td class="p-2">Bahasa</td><td class="p-2" id="js-lang">—</td></tr>
        <tr><td class="p-2">RAM</td><td class="p-2" id="js-memory">—</td></tr>
        <tr><td class="p-2">CPU</td><td class="p-2" id="js-cpu">—</td></tr>
        <tr><td class="p-2">Online</td><td class="p-2" id="js-online">—</td></tr>
        <tr><td class="p-2">Waktu Lokal</td><td class="p-2" id="js-localtime">—</td></tr>
        <tr><td class="p-2">GPS</td><td class="p-2" id="js-gps">—</td></tr>
      </tbody>
    </table>
  </div>
</div>

<script>
  document.getElementById("js-screen").innerText = `${screen.width} x ${screen.height}`;
  document.getElementById("js-lang").innerText = navigator.language;
  document.getElementById("js-memory").innerText = navigator.deviceMemory || "—";
  document.getElementById("js-cpu").innerText = navigator.hardwareConcurrency || "—";
  document.getElementById("js-online").innerText = navigator.onLine ? "Online" : "Offline";
  document.getElementById("js-localtime").innerText = new Date().toLocaleString();

  if ("geolocation" in navigator) {
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        const { latitude, longitude } = pos.coords;
        document.getElementById("js-gps").innerText = `${latitude.toFixed(4)}, ${longitude.toFixed(4)}`;
      },
      () => {
        document.getElementById("js-gps").innerText = "Tidak diizinkan";
      }
    );
  } else {
    document.getElementById("js-gps").innerText = "Tidak didukung";
  }
</script>

</body>
</html>

