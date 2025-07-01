<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$reseller = $_SESSION['reseller'] ?? $_SESSION['username'] ?? 'unknown';

$stats = ['total' => 0, 'vmess' => 0, 'vless' => 0, 'trojan' => 0, 'shadowsocks' => 0];
$rows = [];
$dir = "/etc/xray/data-panel/reseller/";
$no = 1;

foreach (glob("{$dir}akun-{$reseller}-*.txt") as $file) {
    $buyer = basename($file, ".txt");
    $buyer = str_replace("akun-{$reseller}-", "", $buyer);
    $lines = file($file);
    $proto = null;
    $expired = "-";
    $uuidOrPass = "-";
    foreach ($lines as $line) {
        if (stripos($line, 'TROJAN ACCOUNT') !== false) $proto = 'trojan';
        elseif (stripos($line, 'VMESS ACCOUNT') !== false) $proto = 'vmess';
        elseif (stripos($line, 'VLESS ACCOUNT') !== false) $proto = 'vless';
        elseif (stripos($line, 'SHADOWSOCKS ACCOUNT') !== false) $proto = 'shadowsocks';
        elseif (stripos($line, 'Expired On') !== false) {
            $expParts = explode(':', $line, 2);
            $expired = trim($expParts[1] ?? '-');
        }
        if (stripos($line, 'Password') !== false) {
            $uuidOrPass = trim(explode(':', $line, 2)[1] ?? '-');
        }
    }
    if ($proto) {
        $stats[$proto]++;
        $stats['total']++;
        $rows[] = [
            'no' => $no++, 'user' => $buyer,
            'proto' => strtoupper($proto), 'exp' => $expired, 'buyer' => $uuidOrPass
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard Akun</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
<style>
    html, body {
        max-width: 100%;
        overflow-x: hidden;
    }
</style>

</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-white p-4">

<!-- Statistik Akun -->
<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 mb-6 text-center">
    <?php
    foreach (['total' => 'Total Akun', 'vmess' => 'VMess', 'vless' => 'VLess', 'trojan' => 'Trojan', 'shadowsocks' => 'Shadowsocks'] as $k => $label) {
        $color = ['total' => 'green', 'vmess' => 'blue', 'vless' => 'purple', 'trojan' => 'red', 'shadowsocks' => 'yellow'][$k];
        echo "<div class='bg-{$color}-100 dark:bg-{$color}-800 text-{$color}-900 dark:text-white p-4 rounded-lg shadow'>
                <p class='text-sm font-semibold'>{$label}</p>
                <p class='text-xl font-bold'>{$stats[$k]}</p>
              </div>";
    }
    ?>
</div>

<!-- Grafik Akun Terjual -->
<div class="w-full flex justify-center mb-8">
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow w-full max-w-2xl">
        <div class="relative h-[280px] w-full">
            <canvas id="myChart"></canvas>
        </div>
        <div class="text-center mt-3 text-sm text-gray-600 dark:text-gray-300">
            <span class="inline-block mx-2">VMess</span>
            <span class="inline-block mx-2">VLess</span>
            <span class="inline-block mx-2">Trojan</span>
            <span class="inline-block mx-2">Shadowsocks</span>
        </div>
    </div>
</div>

<script>
const ctx = document.getElementById("myChart").getContext("2d");
new Chart(ctx, {
    type: "bar",
    data: {
        labels: ["VMess", "VLess", "Trojan", "Shadowsocks"],
        datasets: [{
            label: "Akun Terjual",
            data: [<?= $stats['vmess'] ?>, <?= $stats['vless'] ?>, <?= $stats['trojan'] ?>, <?= $stats['shadowsocks'] ?>],
            backgroundColor: ["#6366f1", "#3b82f6", "#ef4444", "#10b981"],
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: "#1f2937",
                titleColor: "#fff",
                bodyColor: "#ddd"
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { color: "#94a3b8" }
            },
            x: {
                ticks: { color: "#94a3b8" }
            }
        }
    }
});
</script>

<!-- Tabel Akun -->
<div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-300 dark:border-gray-700">
    <table class="min-w-full text-sm text-left text-gray-800 dark:text-white">
        <thead class="bg-gray-100 dark:bg-gray-700">
            <tr>
                <th class="px-3 py-2">No</th>
                <th class="px-3 py-2">Username</th>
                <th class="px-3 py-2">Protocol</th>
                <th class="px-3 py-2">Expired</th>
                <th class="px-3 py-2">UUID/Pass</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)) : ?>
                <tr>
                    <td colspan="5" class="text-center px-3 py-4 text-gray-500 dark:text-gray-400">Belum ada akun.</td>
                </tr>
            <?php else : ?>
                <?php foreach ($rows as $r) : ?>
                    <tr class="border-t border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-3 py-2"><?= $r['no'] ?></td>
                        <td class="px-3 py-2"><?= $r['user'] ?></td>
                        <td class="px-3 py-2"><?= $r['proto'] ?></td>
                        <td class="px-3 py-2"><?= $r['exp'] ?></td>
                        <td class="px-3 py-2 font-mono break-all"><?= $r['buyer'] ?></td>
                    </tr>
                <?php endforeach ?>
            <?php endif ?>
        </tbody>
    </table>
</div>

</body>
</html>

