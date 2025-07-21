<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$configPath = '/etc/xray/config.json';
$user = $_GET['user'] ?? '';
$proto = $_GET['proto'] ?? ''; // ambil protokol dari parameter GET
$updated = false;

// Mapping tag protokol
$tagMap = [
    'vmess' => ['#vmess', '#vmessgrpc'],
    'vless' => ['#vless', '#vlessgrpc'],
    'trojan' => ['#trojanws', '#trojangrpc'],
    'shadowsocks' => ['#ssws', '#ssgrpc']
];

function updateExpired($configPath, $user, $newDate, $proto, $tagMap) {
    $lines = file($configPath);
    $currentTags = $tagMap[$proto] ?? [];
    $updated = false;
    $currentTag = '';

    for ($i = 0; $i < count($lines); $i++) {
        $line = $lines[$i];

        // Deteksi tag protokol aktif (misal: #vmessgrpc)
        if (preg_match('/^\s*#(vmess|vless|trojan|ss)(grpc|ws)?$/i', trim($line), $m)) {
            $currentTag = '#' . strtolower($m[1] . ($m[2] ?? ''));
        }

        // Update expired hanya jika berada di dalam blok protokol yg sesuai
        if (in_array($currentTag, $currentTags)) {
            if (preg_match('/^\s*(###|#!|#&|#\$)\s+' . preg_quote($user, '/') . '\s+\d{4}-\d{2}-\d{2}/', $line, $matches)) {
                $prefix = $matches[1];
                $lines[$i] = "$prefix $user $newDate\n";
                $updated = true;
            }
        }
    }

    if ($updated) {
        file_put_contents($configPath, implode('', $lines));
        shell_exec('systemctl restart xray');
    }

    return $updated;
}

// Saat form dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_expired = trim($_POST['expired']);
    if (preg_match('/^\d+$/', $new_expired)) {
        $new_expired = date('Y-m-d', strtotime("+$new_expired days"));
    }
    $updated = updateExpired($configPath, $user, $new_expired, $proto, $tagMap);
}

include 'templates/header.php';
?>

<div class="max-w-xl mx-auto mt-10 bg-gray-800 p-6 rounded-xl shadow-md text-white">
    <h2 class="text-xl font-bold mb-4">Edit Akun: <?= htmlspecialchars($user) ?> (<?= htmlspecialchars($proto) ?>)</h2>

    <?php if ($updated): ?>
        <div class="mb-4 text-green-400">✅ Tanggal expired berhasil diperbarui!</div>
    <?php endif; ?>

    <form method="POST">
        <label class="block mb-1">Expired (tanggal atau jumlah hari)</label>
        <input type="text" name="expired" class="w-full p-2 bg-gray-700 rounded mb-4" required>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded">Simpan Perubahan</button>
    </form>

    <a href="kelola-akun.php" class="inline-block mt-4 text-blue-300 hover:underline">⬅ Kembali</a>
</div>

<?php include 'templates/footer.php'; ?>

