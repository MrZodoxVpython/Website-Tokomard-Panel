<?php
$reseller = $_SESSION['username'];
$dataDir = "/etc/xray/data-panel/";
$statistik = [
    'total' => 0,
    'aktif' => 0,
    'expired' => 0,
    'akan_expired' => 0,
    'ssh' => 0,
    'vmess' => 0,
    'vless' => 0,
    'trojan' => 0,
    'shadowsocks' => 0
];
$daftarAkun = [];

$akunFiles = glob($dataDir . "akun-{$reseller}-*.txt");
$now = time();

foreach ($akunFiles as $file) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '{') !== false) {
            $akun = json_decode($line, true);
            if (!$akun || !is_array($akun)) continue;

            $protokol = strtolower($akun['protokol'] ?? '');
            $username = $akun['username'] ?? '';
            $expired = $akun['expired'] ?? '';

            if (!$protokol || !$username || !$expired) continue;

            $statistik['total']++;
            if (isset($statistik[$protokol])) $statistik[$protokol]++;

            $expUnix = strtotime($expired);
            $selisihHari = floor(($expUnix - $now) / (60 * 60 * 24));

            if ($selisihHari < 0) {
                $statistik['expired']++;
            } elseif ($selisihHari <= 7) {
                $statistik['akan_expired']++;
                $statistik['aktif']++;
            } else {
                $statistik['aktif']++;
            }

            $daftarAkun[] = [
                'username' => $username,
                'protokol' => strtoupper($protokol),
                'expired' => $expired,
                'days_left' => $selisihHari
            ];
        }
    }
}
?>

<h2 class="text-xl font-bold mb-4">📊 Statistik Akun Reseller</h2>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
    <div class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 p-4 rounded shadow">
        <p class="text-lg font-semibold">Total Akun</p>
        <p class="text-3xl font-bold"><?= $statistik['total'] ?></p>
    </div>
    <div class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 p-4 rounded shadow">
        <p class="text-lg font-semibold">Akun Aktif</p>
        <p class="text-3xl font-bold"><?= $statistik['aktif'] ?></p>
    </div>
    <div class="bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 p-4 rounded shadow">
        <p class="text-lg font-semibold">Akan Expired ≤ 7 Hari</p>
        <p class="text-3xl font-bold"><?= $statistik['akan_expired'] ?></p>
    </div>
    <div class="bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 p-4 rounded shadow">
        <p class="text-lg font-semibold">Expired</p>
        <p class="text-3xl font-bold"><?= $statistik['expired'] ?></p>
    </div>
    <div class="bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200 p-4 rounded shadow">
        <p class="text-lg font-semibold">SSH</p>
        <p class="text-3xl font-bold"><?= $statistik['ssh'] ?></p>
    </div>
    <div class="bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 p-4 rounded shadow">
        <p class="text-lg font-semibold">VMess</p>
        <p class="text-3xl font-bold"><?= $statistik['vmess'] ?></p>
    </div>
    <div class="bg-pink-100 dark:bg-pink-900 text-pink-800 dark:text-pink-200 p-4 rounded shadow">
        <p class="text-lg font-semibold">VLess</p>
        <p class="text-3xl font-bold"><?= $statistik['vless'] ?></p>
    </div>
    <div class="bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 p-4 rounded shadow">
        <p class="text-lg font-semibold">Trojan</p>
        <p class="text-3xl font-bold"><?= $statistik['trojan'] ?></p>
    </div>
    <div class="bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 p-4 rounded shadow">
        <p class="text-lg font-semibold">Shadowsocks</p>
        <p class="text-3xl font-bold"><?= $statistik['shadowsocks'] ?></p>
    </div>
</div>

<h2 class="text-lg font-semibold mb-2">📋 Daftar Akun Terdaftar</h2>
<div class="overflow-auto rounded border dark:border-gray-700">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
            <tr>
                <th class="p-2 text-left">Username</th>
                <th class="p-2 text-left">Protokol</th>
                <th class="p-2 text-left">Expired</th>
                <th class="p-2 text-left">Sisa Hari</th>
            </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-100">
            <?php foreach ($daftarAkun as $akun): ?>
                <tr class="border-b dark:border-gray-700">
                    <td class="p-2"><?= htmlspecialchars($akun['username']) ?></td>
                    <td class="p-2"><?= htmlspecialchars($akun['protokol']) ?></td>
                    <td class="p-2"><?= htmlspecialchars($akun['expired']) ?></td>
                    <td class="p-2"><?= $akun['days_left'] >= 0 ? $akun['days_left'] . ' hari' : 'Expired' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

