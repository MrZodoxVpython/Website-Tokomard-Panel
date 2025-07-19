<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$user = $_GET['user'] ?? '';
$proto = $_GET['proto'] ?? '';
$vps = $_GET['vps'] ?? 'sgdo-2dev';
$updated = false;

// Konfigurasi daftar VPS
$vpsList = [
    'sgdo-2dev' => ['user' => 'root', 'ip' => '127.0.0.1'], // lokal
    'sgdo-mard1' => ['user' => 'root', 'ip' => '152.42.182.187'],
    'rw-mard' => ['user' => 'root', 'ip' => '203.194.113.140'],
];
$vpsMap = [
    'sgdo-2dev' => '/etc/xray/config.json',
    'sgdo-mard1' => '/etc/xray/config.json',
    'rw-mard' => '/etc/xray/config.json',
];

$sshUser = $vpsList[$vps]['user'];
$sshIp = $vpsList[$vps]['ip'];
$configPath = $vpsMap[$vps];
$isRemote = $vps !== 'sgdo-2dev';

// Mapping tag protokol
$tagMap = [
    'vmess' => ['#vmess', '#vmessgrpc'],
    'vless' => ['#vless', '#vlessgrpc'],
    'trojan' => ['#trojanws', '#trojangrpc'],
    'shadowsocks' => ['#ssws', '#ssgrpc']
];

// Fungsi Update Tanggal Expired
function updateExpired($lines, $user, $newDate, $proto, $tagMap) {
    $currentTags = $tagMap[$proto] ?? [];
    $updated = false;
    $currentTag = '';

    for ($i = 0; $i < count($lines); $i++) {
        $line = $lines[$i];

        if (preg_match('/^\s*#(vmess|vless|trojan|ss)(grpc|ws)?$/i', trim($line), $m)) {
            $currentTag = '#' . strtolower($m[1] . ($m[2] ?? ''));
        }

        if (in_array($currentTag, $currentTags)) {
            if (preg_match('/^\s*(###|#!|#&|#\$)\s+' . preg_quote($user, '/') . '\s+\d{4}-\d{2}-\d{2}/', $line, $matches)) {
                $prefix = $matches[1];
                $lines[$i] = "$prefix $user $newDate\n";
                $updated = true;
            }
        }
    }

    return [$updated, $lines];
}

// Ambil isi config
if ($isRemote) {
    $cmd = "ssh -o StrictHostKeyChecking=no $sshUser@$sshIp 'cat $configPath'";
    $configContent = shell_exec($cmd);
    if (!$configContent) die("❌ Gagal membaca config.json dari VPS $vps");
    $lines = explode("\n", $configContent);
} else {
    if (!file_exists($configPath)) die("❌ Config file tidak ditemukan di lokal");
    $lines = file($configPath);
}

// Saat form dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_expired = trim($_POST['expired']);
    if (preg_match('/^\d+$/', $new_expired)) {
        $new_expired = date('Y-m-d', strtotime("+$new_expired days"));
    }

    list($updated, $updatedLines) = updateExpired($lines, $user, $new_expired, $proto, $tagMap);
    if ($updated) {
    $newConfig = implode("\n", $updatedLines);
    if ($isRemote) {
        $tmpFile = "/tmp/tmp_config_" . uniqid() . ".json";
        file_put_contents($tmpFile, $newConfig);

        // Salin ke VPS
        $scpOutput = shell_exec("scp -o StrictHostKeyChecking=no $tmpFile $sshUser@$sshIp:$configPath 2>&1");

        // Restart Xray
        $restartOutput = shell_exec("ssh -o StrictHostKeyChecking=no $sshUser@$sshIp 'systemctl restart xray && echo ✅ Restart sukses || echo ❌ Restart gagal' 2>&1");

        unlink($tmpFile);

        // Tampilkan output (debug sementara, boleh dihapus setelah yakin)
        echo "<pre class='text-sm bg-gray-900 text-white p-3 rounded mb-4 overflow-x-auto'>";
        echo "📦 <b>SCP Output:</b>\n" . htmlspecialchars($scpOutput);
        echo "\n\n🚀 <b>Restart Output:</b>\n" . htmlspecialchars($restartOutput);
        echo "</pre>";
    } else {
        file_put_contents($configPath, $newConfig);
        shell_exec("systemctl restart xray");
    }
}
}

include 'templates/header.php';
?>

<div class="max-w-xl mx-auto mt-10 bg-gray-800 p-6 rounded-xl shadow-md text-white">
    <h2 class="text-xl font-bold mb-4">Edit Akun: <?= htmlspecialchars($user) ?> (<?= htmlspecialchars($proto) ?>)</h2>

    <?php if ($updated): ?>
        <div class="mb-4 text-green-400">✅ Tanggal expired berhasil diperbarui di VPS <?= strtoupper($vps) ?>!</div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="vps" value="<?= htmlspecialchars($vps) ?>">
        <label class="block mb-1">Expired (tanggal atau jumlah hari)</label>
        <input type="text" name="expired" class="w-full p-2 bg-gray-700 rounded mb-4" required>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded">Simpan Perubahan</button>
    </form>

    <a href="kelola-akun.php?vps=<?= htmlspecialchars($vps) ?>" class="inline-block mt-4 text-blue-300 hover:underline">⬅ Kembali</a>
</div>

<?php include 'templates/footer.php'; ?>

