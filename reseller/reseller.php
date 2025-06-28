<?php
$username = $_SESSION['username'];
$dataDir = "/etc/xray/data-panel/reseller/";
$akunFiles = glob($dataDir . "akun-{$username}-*.txt");

$statistik = [
    'vmess' => 0,
    'vless' => 0,
    'trojan' => 0,
    'shadowsocks' => 0,
    'aktif' => 0,
    'expired' => 0,
    'akan_expired' => 0
];

$daftarAkun = [];

foreach ($akunFiles as $file) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '{') !== false) {
            $json = json_decode($line, true);
            if (is_array($json)) {
                $proto = $json['protokol'] ?? 'unknown';
                $expired = $json['expired'] ?? '';
                $akunUser = $json['username'] ?? '';
                $statistik[$proto]++;

                $expTime = strtotime($expired);
                $now = time();
                $sisa = floor(($expTime - $now) / (60 * 60 * 24));

                if ($sisa < 0) {
                    $statistik['expired']++;
                } elseif ($sisa <= 7) {
                    $statistik['akan_expired']++;
                    $statistik['aktif']++;
                } else {
                    $statistik['aktif']++;
                }

                $daftarAkun[] = [
                    'username' => $akunUser,
                    'protokol' => strtoupper($proto),
                    'expired' => $expired,
                    'days_left' => $sisa
                ];
            }
        }
    }
}
?>

<div class="space-y-6">
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <?php foreach (['vmess','vless','trojan','shadowsocks'] as $p): ?>
            <div class="p-4 rounded-lg shadow bg-gradient-to-br from-blue-500 to-blue-700 text-white">
                <h2 class="text-sm font-semibold"><?= strtoupper($p) ?> Akun</h2>
                <div class="text-2xl font-bold"><?= $statistik[$p] ?></div>
            </div>
        <?php endforeach; ?>
        <div class="p-4 rounded-lg shadow bg-gradient-to-br from-green-500 to-green-700 text-white">
            <h2 class="text-sm font-semibold">Akun Aktif</h2>
            <div class="text-2xl font-bold"><?= $statistik['aktif'] ?></div>
        </div>
        <div class="p-4 rounded-lg shadow bg-gradient-to-br from-yellow-400 to-yellow-600 text-white">
            <h2 class="text-sm font-semibold">Akan Expired</h2>
            <div class="text-2xl font-bold"><?= $statistik['akan_expired'] ?></div>
        </div>
        <div class="p-4 rounded-lg shadow bg-gradient-to-br from-red-600 to-red-800 text-white">
            <h2 class="text-sm font-semibold">Expired</h2>
            <div class="text-2xl font-bold"><?= $statistik['expired'] ?></div>
        </div>
    </div>

    <div class="overflow-auto">
        <table class="min-w-full text-sm table-auto divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-100 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-2 text-left">Username</th>
                    <th class="px-4 py-2 text-left">Protokol</th>
                    <th class="px-4 py-2 text-left">Expired</th>
                    <th class="px-4 py-2 text-left">Sisa Hari</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                <?php foreach ($daftarAkun as $akun): ?>
                    <tr>
                        <td class="px-4 py-2"><?= htmlspecialchars($akun['username']) ?></td>
                        <td class="px-4 py-2"><?= $akun['protokol'] ?></td>
                        <td class="px-4 py-2"><?= $akun['expired'] ?></td>
                        <td class="px-4 py-2"><?= $akun['days_left'] ?> hari</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

