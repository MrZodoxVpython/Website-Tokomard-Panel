<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'reseller') {
    header("Location: ../index.php");
    exit;
}

require_once '../../koneksi.php';
require_once __DIR__ . '/api-akun/lib-akun.php';

$server = [
    'name' => 'SGDO-2DEV',
    'country' => 'Singapura',
    'isp' => 'DigitalOcean, LLC',
    'price' => 20000,
    'rules' => [
        'NO TORRENT',
        'NO MULTY LOGIN',
        'SUPPORT ENHANCED HTTP CUSTOM',
        'Max Login 1 device'
    ]
];

$protocol = 'vless';
$output = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $expiredInput = trim($_POST['expired'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$username || !$expiredInput) {
        $output = "❌ Username dan expired harus diisi.";
    } else {
        if (empty($password)) {
            $password = generateUUID();
        }

        $lamaHari = (int)$expiredInput;
        $hargaDasar = $server['price'];
        $hargaFinal = intval($hargaDasar * $lamaHari / 30);

        $reseller = $_SESSION['reseller'] ?? $_SESSION['username'] ?? 'unknown';
        $q = $conn->prepare("SELECT saldo FROM users WHERE username = ?");
        $q->bind_param("s", $reseller);
        $q->execute();
        $q->bind_result($saldoUser);
        $q->fetch();
        $q->close();

        if ($saldoUser < $hargaFinal) {
            $output = "❌ Saldo tidak cukup.\nSaldo Anda: Rp" . number_format($saldoUser, 0, ',', '.') .
                      "\nHarga: Rp" . number_format($hargaFinal, 0, ',', '.');
        } else {
            // Panggil file lokal add-vless.php (bukan SSH)
            $phpCmd = "php /var/www/html/Website-Tokomard-Panel/reseller/pages/api-akun/add-vless.php '$username' '$expiredInput' '$password' '$reseller'";
            $outputRaw = shell_exec($phpCmd . ' 2>&1');

            if (!empty(trim($outputRaw)) && str_contains($outputRaw, 'VLESS ACCOUNT')) {
                // Kurangi saldo
                $stmt = $conn->prepare("UPDATE users SET saldo = saldo - ? WHERE username = ?");
                $stmt->bind_param("is", $hargaFinal, $reseller);
                if ($stmt->execute()) {
                    $output = $outputRaw;
                    $output .= "\n✅ Akun berhasil dibuat.";
                    $output .= "\n💳 Saldo terpotong: Rp" . number_format($hargaFinal, 0, ',', '.');

                    // Dapatkan ID user (reseller)
                    $stmtUserId = $conn->prepare("SELECT id FROM users WHERE username = ?");
                    $stmtUserId->bind_param("s", $reseller);
                    $stmtUserId->execute();
                    $stmtUserId->bind_result($userId);
                    $stmtUserId->fetch();
                    $stmtUserId->close();

                    // Masukkan log transaksi
                    $detail = 'Pembelian Vless SGDO-2DEV';
                    $type = 'buy';
                    $status = 'SUCCESS';
                    $dateNow = date('Y-m-d H:i:s');

                    $stmtTrans = $conn->prepare("INSERT INTO transactions (user_id, type, status, amount, detail, date) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmtTrans->bind_param("ississ", $userId, $type, $status, $hargaFinal, $detail, $dateNow);
                    $stmtTrans->execute();
                    $stmtTrans->close();

                    // Kurangi stok
                    $stokFile = __DIR__ . '/data/stok-vmess.json';
                    $serverName = $server['name'];
                    if (file_exists($stokFile)) {
                        $stokData = json_decode(file_get_contents($stokFile), true);
                        if (isset($stokData[$serverName])) {
                            $stokData[$serverName]['stock'] -= 1;
                            if ($stokData[$serverName]['stock'] <= 0) {
                                $stokData[$serverName]['stock'] = 0;
                                $stokData[$serverName]['available'] = false;
                            }
                            file_put_contents($stokFile, json_encode($stokData, JSON_PRETTY_PRINT));
                        }
                    }
                } else {
                    $output = "⚠ Akun dibuat, tapi gagal memotong saldo.";
                }
                $stmt->close();
            } else {
                $output = "❌ Gagal membuat akun: $outputRaw";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <title>Checkout VMess SGDO-2DEV</title>
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

    <h3 class="text-xl font-semibold">🧾 Buat Akun VMess</h3>

    <?php if ($output): ?>
        <div class="bg-gray-800 text-green-400 p-4 rounded text-sm font-mono whitespace-pre-wrap break-all border border-green-500"><?= htmlspecialchars($output) ?></div>
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

