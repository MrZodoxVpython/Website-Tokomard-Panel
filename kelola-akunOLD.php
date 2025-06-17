<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$configPath = '/etc/xray/config.json';

function generateUUID() {
    return trim(shell_exec('cat /proc/sys/kernel/random/uuid'));
}

function calculateExpiredDate($input) {
    if (preg_match('/^\d+$/', $input)) {
        return date('Y-m-d', strtotime("+$input days"));
    }
    return $input;
}

function akunSudahAda($username, $expired, $configPath) {
    $lines = file($configPath);
    foreach ($lines as $line) {
        if (preg_match('/^\s*(###|#&|#!|#\$)\s+' . preg_quote($username, '/') . '\s+' . preg_quote($expired, '/') . '\s*$/', trim($line))) {
            return true;
        }
    }
    return false;
}

function gantiUUIDJikaINACTIVE(&$lines, $username, $proto, &$newUUID) {
    $prefixMap = [
        'vmess' => '###',
        'vless' => '#&',
        'trojan' => '#!',
        'shadowsocks' => '#$'
    ];
    $prefix = $prefixMap[$proto] ?? '';
    if (!$prefix) return;

    $newUUID = generateUUID();
    for ($i = 0; $i < count($lines); $i++) {
        if (preg_match("/^\s*{$prefix}\s+{$username}\s+([\d-]+)\s+INACTIVE/", trim($lines[$i]), $match)) {
            $lines[$i] = "$prefix $username {$match[1]}" . "\n";
            for ($j = $i + 1; $j < count($lines); $j++) {
                if (strpos($lines[$j], '{') !== false) {
                    switch ($proto) {
                        case 'vmess':
                        case 'vless':
                            $lines[$j] = preg_replace('/"id"\s*:\s*"[^"]+"/', '"id": "' . $newUUID . '"', $lines[$j]);
                            break;
                        case 'trojan':
                        case 'shadowsocks':
                            $lines[$j] = preg_replace('/"password"\s*:\s*"[^"]+"/', '"password": "' . $newUUID . '"', $lines[$j]);
                            break;
                    }
                    break;
                }
            }
            break;
        }
    }
}

if (isset($_GET['hapus']) && $_GET['hapus']) {
    $hapus = $_GET['hapus'];
    $prefixes = ['###', '#&', '#!', '#$'];
    $lines = file($configPath);
    $newLines = [];
    $skipNextLine = false;

    for ($i = 0; $i < count($lines); $i++) {
        $line = trim($lines[$i]);

        if ($skipNextLine) {
            $skipNextLine = false;
            continue; // skip JSON line setelah komentar
        }

        $isCommentLine = false;
        foreach ($prefixes as $prefix) {
            if (preg_match("/^$prefix\s+$hapus\b/", $line)) {
                $isCommentLine = true;
                $skipNextLine = true;
                break;
            }
        }

        if ($isCommentLine) continue;

        $newLines[] = $lines[$i];
    }

    file_put_contents($configPath, implode('', $newLines));
    header("Location: kelola-akun.php");
    exit;
}

$username = trim($_POST['username'] ?? '');
$expired = trim($_POST['expired'] ?? '');
$protokol = trim($_POST['protokol'] ?? '');
$key = trim($_POST['key'] ?? '');
$editMode = isset($_POST['editmode']) ? $_POST['editmode'] : '';

$expired = calculateExpiredDate($expired);
$proses = ($_SERVER['REQUEST_METHOD'] === 'POST' && $username && $expired && $protokol);

include 'templates/header.php';
?>

<div class="max-w-5xl mx-auto mt-10 bg-gray-800 p-6 rounded-xl shadow-md text-white">
<?php if ($proses): ?>
    <?php
    $lines = file($configPath);
    $protoPrefix = ['vmess'=>'###', 'vless'=>'#&', 'trojan'=>'#!', 'shadowsocks'=>'#$'][$protokol] ?? '';
    $newKey = $key ?: generateUUID();
    $commentPrefix = $protoPrefix;
    $jsonEntry = '';
    switch ($protokol) {
    case 'vmess':
        $jsonEntry = '},{"id": "' . $newKey . '","alterId": 0,"email": "' . $username . '"';
        break;
    case 'vless':
        $jsonEntry = '},{"id": "' . $newKey . '","email": "' . $username . '"';
        break;
    case 'trojan':
        $jsonEntry = '},{"password": "' . $newKey . '","email": "' . $username . '"';
        break;
    case 'shadowsocks':
        $jsonEntry = '},{"password": "' . $newKey . '","method": "aes-128-gcm","email": "' . $username . '"';
        break;
    }

    $tagMap = [
        'vmess' => ['vmess', 'vmessgrpc'],
        'vless' => ['vless', 'vlessgrpc'],
        'trojan' => ['trojanws', 'trojangrpc'],
        'shadowsocks' => ['ssws', 'ssgrpc']
    ];
    $tags = $tagMap[$protokol] ?? [];

    copy($configPath, $configPath . '.bak-' . date('YmdHis'));

    function insertIntoTag($configPath, $tags, $commentLine, $jsonLine) {
        $lines = file($configPath);
        foreach ($tags as $tag) {
            $inserted = false;
            foreach ($lines as $i => $line) {
                if (strpos($line, "#$tag") !== false) {
                    array_splice($lines, $i + 1, 0, [$commentLine . "\n", $jsonLine . "\n"]);
                    $inserted = true;
                    break;
                }
            }
            if ($inserted) {
                file_put_contents($configPath, implode('', $lines));
            }
        }
    }

       if ($editMode === 'yes') {
        header("Location: kelola-akun.php");
        exit;
    } else {
        $commentLine = "$commentPrefix $username $expired";
        if (!empty($tags)) {
            insertIntoTag($configPath, $tags, $commentLine, $jsonEntry);
            echo "<p class='text-green-400'>✅ Akun $username berhasil ditambahkan ke semua tag.</p>";

            // Blok tambahan untuk Trojan
            if ($protokol === 'trojan') {
                $domain = trim(shell_exec('cat /etc/xray/domain'));
                $tls = "443";
                $ntls = "80";
                $path = "/trojan-ws";
                $servicename = "trojan-grpc";

                $trojanlink  = "trojan://$newKey@$domain:$tls?path=$path&security=tls&type=ws#$username";
                $trojanlink2 = "trojan://$newKey@$domain:$ntls?path=$path&security=none&type=ws#$username";
                $trojanlink1 = "trojan://$newKey@$domain:$tls?mode=gun&security=tls&type=grpc&serviceName=$servicename#$username";

                echo '<pre style="background-color: #1e1e2e; color: #cdd6f4; padding: 1em; border-radius: 10px; overflow-x: auto">';
                echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                echo "           TROJAN ACCOUNT           \n";
                echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                echo "Remarks        : $username\n";
                echo "Host/IP        : $domain\n";
                echo "Wildcard       : (bug.com).$domain\n";
                echo "Port TLS       : $tls\n";
                echo "Port none TLS  : $ntls\n";
                echo "Port gRPC      : $tls\n";
                echo "Key            : $newKey\n";
                echo "Path           : $path\n";
                echo "ServiceName    : $servicename\n";
                echo "Expired On     : $expired\n";
                echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                echo "Link TLS       : $trojanlink\n";
                echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                echo "Link none TLS  : $trojanlink2\n";
                echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                echo "Link gRPC      : $trojanlink1\n";
                echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                echo '</pre>';
            }

        } else {
            echo "<p class='text-yellow-400'>⚠ Akun gagal ditambahkan. Tag tidak ditemukan.</p>";
        }
    }

   ?>
    <a href="kelola-akun.php" class="inline-block mt-4 bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded">🔙 Kembali</a>
<?php else: ?>
<form action="" method="POST" class="grid gap-4">
    <div>
        <label class="block mb-1">Username</label>
        <input type="text" name="username" class="w-full p-2 bg-gray-700 rounded" required>
    </div>
    <div>
        <label class="block mb-1">Expired (tanggal atau jumlah hari)</label>
        <input type="text" name="expired" placeholder="2025-07-01 atau 30" class="w-full p-2 bg-gray-700 rounded" required>
    </div>
    <div>
        <label class="block mb-1">Protokol</label>
        <select name="protokol" class="w-full p-2 bg-gray-700 rounded" required>
            <option value="vmess">Vmess</option>
            <option value="vless">Vless</option>
            <option value="trojan">Trojan</option>
            <option value="shadowsocks">Shadowsocks</option>
        </select>
    </div>
    <div>
        <label class="block mb-1">UUID / Password (random jika kosong)</label>
        <input type="text" name="key" class="w-full p-2 bg-gray-700 rounded">
    </div>
    <div>
        <input type="hidden" name="editmode" value="">
        <button type="submit" class="w-full bg-green-600 hover:bg-green-700 py-2 rounded">Simpan Akun</button>
    </div>
</form>
<?php endif; ?>
</div>

<div class="max-w-5xl mx-auto mt-10 bg-gray-800 p-6 rounded-xl shadow-md text-white">
  <h2 class="text-xl font-bold mb-4">📋 Daftar Akun Custumer</h2>
  <div class="overflow-x-auto">
    <table class="min-w-full text-left border border-gray-700">
      <thead>
        <tr class="bg-gray-700">
          <th class="p-2 border border-gray-700">No</th>
          <th class="p-2 border border-gray-700">Nama Akun</th>
          <th class="p-2 border border-gray-700">Protokol</th>
          <th class="p-2 border border-gray-700">Expired</th>
          <th class="p-2 border border-gray-700">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $lines = file($configPath);
        $akunMap = [];
        foreach ($lines as $line) {
          if (preg_match('/^\s*(###|#&|#!|#\$)\s+(\S+)\s+(\d{4}-\d{2}-\d{2})(\s+INACTIVE)?/', trim($line), $m)) {
            $proto = [
              '###' => 'vmess',
              '#&' => 'vless',
              '#!' => 'trojan',
              '#$' => 'shadowsocks'
            ][$m[1]] ?? 'unknown';
            $akunMap[$m[1] . $m[2]] = [$m[2], $proto, $m[3]];
          }
        }
        $no = 1;
        foreach ($akunMap as $akun) {
          echo "<tr class='hover:bg-gray-700 border border-gray-700'>";
          echo "<td class='p-2 border border-gray-700'>" . $no++ . "</td>";
          echo "<td class='p-2 border border-gray-700'>" . htmlspecialchars($akun[0]) . "</td>";
          echo "<td class='p-2 border border-gray-700'>" . htmlspecialchars($akun[1]) . "</td>";
          echo "<td class='p-2 border border-gray-700'>" . htmlspecialchars($akun[2]) . "</td>";
          echo "<td class='p-2 text-sm border border-gray-700'>
                  <a href='kelola-akun.php?edit=" . urlencode($akun[0]) . "' class='text-yellow-400 underline'>Edit</a> |
                  <a href='kelola-akun.php?hapus=" . urlencode($akun[0]) . "' onclick=\"return confirm('Yakin hapus akun?');\" class='text-red-400 underline'>Hapus</a>
                </td>";
          echo "</tr>";
        }
        ?>
      </tbody>
    </table>
  </div>
</div>

<?php include 'templates/footer.php'; ?>

