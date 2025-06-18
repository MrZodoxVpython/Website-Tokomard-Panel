<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$servers = [
    'RW‑MARD1'     => ['ip'=>'203.194.113.140','user'=>'root','port'=>22],
    'SGDO‑MARD1'   => ['ip'=>'143.198.202.86','user'=>'root','port'=>22],
    'SGDO‑2DEV'    => ['ip'=>'203.194.113.140','user'=>'root','port'=>22],
];

function fetchRemote($conn, $cmd) {
    $stream = @ssh2_exec($conn, $cmd . ' 2>&1');
    if (!$stream) return null;
    stream_set_blocking($stream, true);
    return trim(stream_get_contents($stream));
}

$password = $_POST['password'] ?? null;

if (!$password):
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><title>Masukkan Password</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white p-8 font-mono">
  <div class="max-w-md mx-auto bg-gray-800 p-6 rounded-xl shadow">
    <h1 class="text-xl font-bold mb-4 text-green-400">🖥 Autentikasi SSH</h1>
    <form method="POST">
      <label class="block mb-2">Masukkan Password root untuk semua VPS:</label>
      <input type="password" name="password" class="w-full px-4 py-2 rounded bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-blue-400" required>
      <button type="submit" class="mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded w-full">Login & Lanjutkan</button>
    </form>
  </div>
</body>
</html>
<?php
exit;
endif;

$results = [];
foreach ($servers as $name => $srv) {
    $res = ['name' => $name, 'online' => false, 'error' => '', 'metrics' => []];
    if (!function_exists('ssh2_connect')) {
        $res['error'] = 'Ekstensi SSH2 tidak tersedia di PHP.';
    } elseif ($conn = @ssh2_connect($srv['ip'], $srv['port'])) {
        if (@ssh2_auth_password($conn, $srv['user'], $password)) {
            $res['online'] = true;
            $res['metrics'] = [
                'load'      => fetchRemote($conn, "uptime | awk -F'load average:' '{print \$2}'"),
                'ram'       => fetchRemote($conn, "free -m | awk 'NR==2{printf \"%sMB / %sMB\", \$3,\$2}'"),
                'disk'      => fetchRemote($conn, "df -h / | awk 'NR==2{printf \"%s / %s (%s)\", \$3,\$2,\$5}'"),
                'uptime'    => fetchRemote($conn, "uptime -p"),
                'bandwidth' => fetchRemote($conn, "vnstat --oneline | awk -F';' '{print \"DL: \" \$10 \" UL: \" \$11 \" TOTAL: \" \$12}'"),
                'os'        => fetchRemote($conn, "grep PRETTY_NAME /etc/os-release | cut -d= -f2 | tr -d '\"'"),
                'ip'        => fetchRemote($conn, "curl -s ifconfig.me"),
                'country'   => fetchRemote($conn, "curl -s ipinfo.io/\$(curl -s ifconfig.me)/country"),
                'domain'    => fetchRemote($conn, "hostname -f"),
                'domain_cf' => fetchRemote($conn, "cat /etc/xray/domain")
            ];
        } else {
            $res['error'] = 'Autentikasi gagal.';
        }
    } else {
        $res['error'] = 'Gagal konek SSH.';
    }
    $results[] = $res;
}

$datetime = date("D, d M Y H:i:s");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><title>✅ VPS Monitoring</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white p-6 font-mono">
  <h1 class="text-3xl font-bold mb-6">✅ Monitoring 3 VPS</h1>

  <?php foreach ($results as $srv): ?>
    <div class="bg-gray-800 p-5 rounded-lg mb-6 shadow-lg">
      <h2 class="text-xl font-bold mb-2"><?= htmlspecialchars($srv['name']) ?></h2>
      <?php if (!$srv['online']): ?>
        <p class="text-red-400"><?= htmlspecialchars($srv['error']) ?></p>
      <?php else: ?>
        <div class="grid grid-cols-2 gap-4">
          <div><span class="text-blue-400">CPU Load:</span> <?= $srv['metrics']['load'] ?></div>
          <div><span class="text-blue-400">RAM:</span> <?= $srv['metrics']['ram'] ?></div>
          <div><span class="text-blue-400">Disk:</span> <?= $srv['metrics']['disk'] ?></div>
          <div><span class="text-blue-400">Uptime:</span> <?= $srv['metrics']['uptime'] ?></div>
          <div><span class="text-blue-400">Bandwidth:</span> <?= $srv['metrics']['bandwidth'] ?></div>
        </div>
        <div class="mt-4 text-sm bg-gray-900 p-4 rounded">
<pre>
OS         : <?= $srv['metrics']['os'] ?>
IP Publik  : <?= $srv['metrics']['ip'] ?>
Negara     : <?= $srv['metrics']['country'] ?>
Domain VPS : <?= $srv['metrics']['domain'] ?>
Domain XRay: <?= $srv['metrics']['domain_cf'] ?>
Time       : <?= $datetime ?>
</pre>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</body>
</html>

