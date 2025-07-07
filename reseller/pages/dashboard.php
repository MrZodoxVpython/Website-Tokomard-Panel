<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$reseller = $_SESSION['reseller'] ?? $_SESSION['username'] ?? 'unknown';

$stats = ['total' => 0, 'vmess' => 0, 'vless' => 0, 'trojan' => 0, 'shadowsocks' => 0];
$rows = [];
$no = 1;

function read_remote_files($remote_ip, $remote_user, $reseller, $server_name) {
    $data = [];
    $remote_dir = "/etc/xray/data-panel/reseller/";

    // Gabungkan find + cat per file dalam satu stream
    $list_cmd = "find {$remote_dir} -type f -iname 'akun-{$reseller}-*.txt' | while read file; do echo \"===FILE:\$file===\"; cat \"\$file\"; done";
    $output = shell_exec("ssh -o StrictHostKeyChecking=no {$remote_user}@{$remote_ip} '{$list_cmd}' 2>/dev/null");
    if (!$output) return [];

    // Pisahkan blok file berdasarkan tag ===FILE:<path>===
    $blocks = preg_split('/===FILE:(.*?)===/', $output, -1, PREG_SPLIT_DELIM_CAPTURE);

    for ($i = 1; $i < count($blocks); $i += 2) {
        $file = trim($blocks[$i]);
        $content = trim($blocks[$i + 1]);

        // Ambil username dari nama file
        $buyer = basename($file, ".txt");
        if (preg_match('/akun\-[^-]+-(.+)$/i', $buyer, $match)) {
            $buyer = $match[1];
        }

        $lines = explode("\n", $content);
        $proto = null;
        $expired = "-";
        $uuidOrPass = "-";

        foreach ($lines as $line) {
            if (stripos($line, 'TROJAN ACCOUNT') !== false) $proto = 'trojan';
            elseif (stripos($line, 'VMESS ACCOUNT') !== false) $proto = 'vmess';
            elseif (stripos($line, 'VLESS ACCOUNT') !== false) $proto = 'vless';
            elseif (stripos($line, 'SHADOWSOCKS ACCOUNT') !== false) $proto = 'shadowsocks';
            elseif (stripos($line, 'Expired On') !== false) $expired = trim(explode(':', $line, 2)[1] ?? '-');
            elseif (stripos($line, 'Password') !== false) $uuidOrPass = trim(explode(':', $line, 2)[1] ?? '-');
        }

        if ($proto) {
            $data[] = [
                'user' => $buyer,
                'proto' => $proto,
                'exp' => $expired,
                'buyer' => $uuidOrPass,
                'server_name' => $server_name
            ];
        }
    }

    return $data;
}

$remote_servers = [
    ['ip' => '152.42.182.187', 'user' => 'root', 'name' => 'SGDO-MARD1'],
    ['ip' => '203.194.113.140', 'user' => 'root', 'name' => 'RW-MARD'],
];

// Lokal
foreach (glob("/etc/xray/data-panel/reseller/akun-{$reseller}-*.txt") as $file) {
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
        elseif (stripos($line, 'Expired On') !== false) $expired = trim(explode(':', $line, 2)[1] ?? '-');
        elseif (stripos($line, 'Password') !== false) $uuidOrPass = trim(explode(':', $line, 2)[1] ?? '-');
    }

    if ($proto) {
        $stats[$proto]++;
        $stats['total']++;
        $rows[] = [
            'no' => $no++, 'user' => $buyer,
            'proto' => strtoupper($proto), 'exp' => $expired,
            'buyer' => $uuidOrPass, 'server_name' => 'SGDO-2DEV/Lokal'
        ];
    }
}

// Remote
foreach ($remote_servers as $srv) {
    $remote_data = read_remote_files($srv['ip'], $srv['user'], $reseller, $srv['name']);
    foreach ($remote_data as $r) {
        $stats[$r['proto']]++;
        $stats['total']++;
        $rows[] = [
            'no' => $no++, 'user' => $r['user'],
            'proto' => strtoupper($r['proto']), 'exp' => $r['exp'],
            'buyer' => $r['buyer'], 'server_name' => $r['server_name']
        ];
    }
}
?>
<style>
    html, body {
        overflow-x: hidden;
        max-width: 100vw;
    }
</style>

<!-- Wrapper keseluruhan -->
<div class="p-4 sm:p-6 md:p-8 space-y-6">

    <!-- Stat Boxes Responsive -->
<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 px-2 sm:px-4">
    <div class="bg-blue-500 text-white rounded-xl p-3 text-center text-sm">
        <div class="text-lg font-bold"><?= $stats['total'] ?></div>
        <div>Total</div>
    </div>
    <div class="bg-green-500 text-white rounded-xl p-3 text-center text-sm">
        <div class="text-lg font-bold"><?= $stats['vmess'] ?></div>
        <div>VMess</div>
    </div>
    <div class="bg-purple-500 text-white rounded-xl p-3 text-center text-sm">
        <div class="text-lg font-bold"><?= $stats['vless'] ?></div>
        <div>VLess</div>
    </div>
    <div class="bg-red-500 text-white rounded-xl p-3 text-center text-sm">
        <div class="text-lg font-bold"><?= $stats['trojan'] ?></div>
        <div>Trojan</div>
    </div>
    <div class="bg-yellow-500 text-white rounded-xl p-3 text-center text-sm">
        <div class="text-lg font-bold"><?= $stats['shadowsocks'] ?></div>
        <div>Shadowsocks</div>
    </div>
</div>

<!-- Grafik Chart.js -->
<div class="w-full max-w-md md:max-w-2xl mx-auto px-2">
    <div class="relative w-full" style="height:250px">
        <canvas id="chartProtokol" class="w-full h-full"></canvas>
    </div>
</div>

<!-- Tabel Akun Responsive Fix -->
<div class="w-full overflow-x-auto mt-6 px-2">
    <div class="min-w-[640px] bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-300 dark:border-gray-700">
        <table class="w-full text-[13px] sm:text-sm text-left text-gray-800 dark:text-white">
            <thead class="bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th class="px-2 py-2">No</th>
                    <th class="px-2 py-2">Username</th>
                    <th class="px-2 py-2">Protocol</th>
                    <th class="px-2 py-2">Expired</th>
                    <th class="px-2 py-2">UUID/Pass</th>
                    <th class="px-2 py-2">Server</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)) : ?>
                    <tr>
                        <td colspan="6" class="text-center px-2 py-3 text-gray-500 dark:text-gray-400">Belum ada akun.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($rows as $r) : ?>
                        <tr class="border-t border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-2 py-2"><?= $r['no'] ?></td>
                            <td class="px-2 py-2"><?= $r['user'] ?></td>
                            <td class="px-2 py-2"><?= $r['proto'] ?></td>
                            <td class="px-2 py-2"><?= $r['exp'] ?></td>
                            <td class="px-2 py-2 font-mono break-all"><?= $r['buyer'] ?></td>
                            <td class="px-2 py-2"><?= $r['server_name'] ?></td>
                        </tr>
                    <?php endforeach ?>
                <?php endif ?>
            </tbody>
        </table>
    </div>
</div>

