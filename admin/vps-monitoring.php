<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$servers = [
    'RW‑MAR1'     => ['ip'=>'203.194.113.140','user'=>'root','port'=>22,'alias'=>'RW‑MAR1'],
    'SGDO‑MARD1'  => ['ip'=>'143.198.202.86','user'=>'root','port'=>22,'alias'=>'SGDO‑MARD1'],
    'SGDO‑2DEV'   => ['ip'=>'203.194.113.140','user'=>'root','port'=>22,'alias'=>'SGDO‑2DEV'],
];

function fetchRemote($conn, $cmd) {
    $stream = @ssh2_exec($conn, $cmd.' 2>&1');
    if (!$stream) return null;
    stream_set_blocking($stream, true);
    return trim(stream_get_contents($stream));
}

$results = [];
foreach ($servers as $key => $srv) {
    $res = ['name'=>$srv['alias'],'online'=>false,'error'=>'', 'metrics'=>[]];
    if (!function_exists('ssh2_connect')) {
        $res['error'] = 'Ext ssh2 tidak tersedia.';
    } elseif ($conn = @ssh2_connect($srv['ip'], $srv['port'])) {
        if (@ssh2_auth_password($conn, $srv['user'], $_POST['password'] ?? '')) {
            $res['online'] = true;
            $res['metrics'] = [
                'cpu'       => fetchRemote($conn, "sys_getloadavg << 'EOF'
EOF"), // unsupported, use load via uptime
                'load'      => fetchRemote($conn, "uptime | awk -F'load average:' '{print \$2}'"),
                'ram'       => fetchRemote($conn, "free -m | awk 'NR==2{printf \"%s/%sMB\", \$3,\$2}'"),
                'disk'      => fetchRemote($conn, "df -h / | awk 'NR==2{printf \"%s/%s (%s)\", \$3,\$2,\$5}'"),
                'uptime'    => fetchRemote($conn, "uptime -p"),
                'bandwidth' => fetchRemote($conn, "vnstat --oneline | awk -F';' '{print \"DL:\" \$10 \" UL:\" \$11 \" TOT:\" \$12}'"),
                'os'        => fetchRemote($conn, "grep PRETTY_NAME /etc/os-release | cut -d'=' -f2 | tr -d '\"'"),
                'ip'        => fetchRemote($conn, "curl -s ifconfig.me"),
                'country'   => fetchRemote($conn, "curl -s ipinfo.io/$(curl -s ifconfig.me)/country"),
                'domain'    => fetchRemote($conn, "hostname -f"),
                'domain_cf' => fetchRemote($conn, "cat /etc/xray/domain")
            ];
        } else {
            $res['error'] = 'Autentikasi gagal.';
        }
    } else {
        $res['error'] = 'Tidak bisa konek SSH.';
    }
    $results[$key] = $res;
}

$datetime = date("D, d M Y H:i:s");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><title>✅ VPS Multi‑Monitoring</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white p-6 font-mono">
  <h1 class="text-2xl mb-6">✅ Monitoring 3 VPS</h1>
  <?php foreach ($results as $srv): ?>
    <div class="bg-gray-800 p-4 rounded-lg mb-6">
      <h2 class="text-xl font-semibold mb-2"><?= $srv['name'] ?></h2>
      <?php if (!$srv['online']): ?>
        <p class="text-red-400"><?= $srv['error'] ?: 'Offline' ?></p>
      <?php else: ?>
        <div class="grid grid-cols-2 gap-4">
          <?php foreach (['load'=>'CPU Load','ram'=>'RAM','disk'=>'Disk','uptime'=>'Uptime','bandwidth'=>'Bandwidth'] as $k=>$lbl): ?>
            <div class="bg-gray-700 p-3 rounded">
              <h3 class="text-blue-400"><?= $lbl ?></h3>
              <p><?= htmlspecialchars($srv['metrics'][$k] ?? 'N/A') ?></p>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="bg-gray-700 p-3 rounded mt-4 text-sm">
<pre>
OS         : <?= htmlspecialchars($srv['metrics']['os'] ?? 'N/A') ?>
IP Publik  : <?= htmlspecialchars($srv['metrics']['ip'] ?? 'N/A') ?>
Negara     : <?= htmlspecialchars($srv['metrics']['country'] ?? 'N/A') ?>
Domain VPS : <?= htmlspecialchars($srv['metrics']['domain'] ?? 'N/A') ?>
Domain XRay: <?= htmlspecialchars($srv['metrics']['domain_cf'] ?? 'N/A') ?>
Time       : <?= $datetime ?>
</pre>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</body>
</html>

