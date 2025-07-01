<?php
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
        // Ambil UUID atau Password
        if (stripos($line, 'Password') !== false && $proto === 'trojan') {
            $uuidOrPass = trim(explode(':', $line, 2)[1] ?? '-');
        } elseif (stripos($line, 'Password') !== false && in_array($proto, ['vmess', 'vless', 'shadowsocks'])) {
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

// Statistik
echo '<div class="text-center grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">';
foreach (['total' => 'Total Akun', 'vmess' => 'VMess', 'vless' => 'VLess', 'trojan' => 'Trojan', 'shadowsocks' => 'Shadowsocks'] as $k => $label) {
    $color = ['total' => 'green', 'vmess' => 'blue', 'vless' => 'purple', 'trojan' => 'red', 'shadowsocks' => 'yellow'][$k];
    echo "<div class='bg-{$color}-100 dark:bg-{$color}-800 text-{$color}-900 dark:text-white p-5 rounded-lg shadow'>
    <p class='text-lg font-semibold'>{$label}</p>
    <p class='text-3xl mt-2 font-bold'>{$stats[$k]}</p>
    </div>";
}
echo "</div>";

// Grafik
echo '<div class="mb-8 max-w-full bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
<canvas id="myChart" class="h-[450px]"></canvas>
</div>
<script>
const ctx = document.getElementById(\"myChart\").getContext(\"2d\");
new Chart(ctx, {
    type: \"bar\",
    data: {
        labels: [\"VMess\", \"VLess\", \"Trojan\", \"Shadowsocks\"],
        datasets: [{
            label: \"Akun Terjual\",
            data: [' . $stats['vmess'] . ',' . $stats['vless'] . ',' . $stats['trojan'] . ',' . $stats['shadowsocks'] . '],
            backgroundColor: [\"#6366f1\", \"#3b82f6\", \"#ef4444\", \"#10b981\"],
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: \"#1f2937\",
                titleColor: \"#fff\",
                bodyColor: \"#ddd\"
            }
        },
        scales: {
            y: { beginAtZero: true, ticks: { color: \"#94a3b8\" } },
            x: { ticks: { color: \"#94a3b8\" } }
        }
    }
});
</script>';

// Tabel akun
echo '<div class="overflow-x-auto">
    <table class="table-fixed w-full border border-gray-300 dark:border-gray-700 text-sm text-left">
    <thead class="bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white">
        <tr>
            <th class="w-1/12 px-3 py-2">No</th>
            <th class="w-3/12 px-3 py-2">Username</th>
            <th class="w-2/12 px-3 py-2">Protocol</th>
            <th class="w-3/12 px-3 py-2">Expired</th>
            <th class="w-3/12 px-3 py-2">Uuid/Pass</th>
        </tr>
    </thead>
    <tbody>';
if (empty($rows)) {
    echo '<tr><td colspan="5" class="text-center px-3 py-4 text-gray-500 dark:text-gray-400">Belum ada akun.</td></tr>';
} else {
    foreach ($rows as $r) {
        echo "<tr class='hover:bg-gray-100 dark:hover:bg-gray-700'>
                <td class='px-3 py-2'>{$r['no']}</td>
                <td class='px-3 py-2'>{$r['user']}</td>
                <td class='px-3 py-2'>{$r['proto']}</td>
                <td class='px-3 py-2'>{$r['exp']}</td>
                <td class='px-3 py-2 font-mono'>{$r['buyer']}</td>
              </tr>";
    }
}
echo '</tbody></table></div>';
?>
 
