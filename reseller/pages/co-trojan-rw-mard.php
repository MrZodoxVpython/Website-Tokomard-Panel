<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'reseller') {
    header("Location: ../index.php");
    exit;
}
require_once '../../koneksi.php'; // atau sesuaikan path ke koneksi database MySQL kamu

// === Konfigurasi server remote ===
$server = [
    'name' => 'RW-MARD',
    'country' => 'Indonesia',
    'isp' => 'FCCDN',
    'ip' => '203.194.113.140',
    'price' => 20000,
    'rules' => [
        'NO TORRENT',
        'NO MULTI LOGIN',
        'SUPPORT ENHANCED HTTP CUSTOM',
        'Max Login 1 device'
    ]
];

$protocol = 'trojan';
$output = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/api-akun/lib-akun.php';

    $username = trim($_POST['username'] ?? '');
    $expiredInput = trim($_POST['expired'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$username || !$expiredInput) {
        $output = "❌ Username dan expired harus diisi.";
    } else {
        if (empty($password)) {
            $password = generateUUID();
        }

        $remoteIp = $server['ip'];
	$reseller = $_SESSION['reseller'] ?? $_SESSION['username'] ?? 'unknown';
	$phpCmd = "php /etc/xray/api-akun/add-trojan.php '$username' '$expiredInput' '$password' '$reseller'";
	$sshCmd = "ssh -i /var/www/.ssh/id_rsa -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o LogLevel=ERROR root@$remoteIp \"$phpCmd\"";
	$output = shell_exec($sshCmd . ' 2>&1');
        if (!empty(trim($output)) && str_contains($output, 'TROJAN ACCOUNT')) {
            // Proses pengurangan saldo
            $lamaHari = (int)$expiredInput;
            $hargaDasar = $server['price'];
            $hargaFinal = intval($hargaDasar * $lamaHari / 30);

            $stmt = $conn->prepare("UPDATE users SET saldo = saldo - ? WHERE username = ? AND saldo >= ?");
            $stmt->bind_param("isi", $hargaFinal, $reseller, $hargaFinal);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
               // Berhasil potong saldo
            } else {
                $output .= "\n⚠️ Akun berhasil dibuat, tetapi saldo tidak cukup untuk dipotong.";
            }
            $stmt->close();
        } else {
            $output = "❌ Gagal membuat akun: $output";
        }

    }
}
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <title>Checkout Trojan RW-MARD</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-white">

<div class="w-full max-w-2xl bg-white dark:bg-gray-900 shadow-md rounded-2xl p-6 space-y-6 border border-gray-200 dark:border-gray-700">
    <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-2">🛒 Detail Server</h2>

    <div class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
        <p><strong>Server Name</strong>: <?= htmlspecialchars($server['name']) ?></p>
        <p><strong>Country</strong>: <?= htmlspecialchars($server['country']) ?></p>
        <p><strong>ISP</strong>: <?= htmlspecialchars($server['isp']) ?></p>
        <?php foreach ($server['rules'] as $rule): ?>
            <p>🚫 <?= htmlspecialchars($rule) ?></p>
        <?php endforeach; ?>
    </div>

    <hr class="border-gray-300 dark:border-gray-600">

    <h3 class="text-xl font-semibold">🧾 Buat Akun Trojan</h3>

    <?php if ($output): ?>
        <div class="bg-gray-800 text-green-400 p-4 rounded text-sm font-mono whitespace-pre-wrap border border-green-500">
            <?= htmlspecialchars($output) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
        <div>
            <label class="block mb-1 text-sm font-medium">⏳ Expired (Hari)</label>
            <select name="expired" required class="w-full rounded border px-3 py-2 bg-white dark:bg-gray-800 dark:border-gray-600 text-sm">
                <option value="3">3 Hari - Rp<?= number_format($server['price'] * 3 / 30, 0, ',', '.') ?></option>
                <option value="7">7 Hari - Rp<?= number_format($server['price'] * 7 / 30, 0, ',', '.') ?></option>
                <option value="30">30 Hari - Rp<?= number_format($server['price'], 0, ',', '.') ?></option>
            </select>
        </div>

        <div>
            <label class="block mb-1 text-sm font-medium">👤 Username</label>
            <input type="text" name="username" required class="w-full px-3 py-2 rounded border bg-white dark:bg-gray-800 dark:border-gray-600 text-sm">
        </div>

        <div>
            <label class="block mb-1 text-sm font-medium">🔒 Password</label>
            <input type="text" name="password" class="w-full px-3 py-2 rounded border bg-white dark:bg-gray-800 dark:border-gray-600 text-sm">
            <p class="text-xs text-gray-400 mt-1">Kosongkan jika ingin UUID otomatis.</p>
        </div>

        <div>
            <button type="submit" class="w-full bg-green-600 hover:bg-green-500 text-white py-2 rounded text-sm font-semibold shadow">
                ✅ Checkout & Buat Akun
            </button>
        </div>
    </form>

    <div class="text-center text-xs text-gray-500 dark:text-gray-500 mt-6">2025© TOKOMARD.CORP NETWORKING</div>
</div>

</body>
</html>

