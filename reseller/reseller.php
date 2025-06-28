<?php
session_start();
$reseller = $_SESSION['username'] ?? null;
if (!$reseller) {
    echo "<p class='text-red-500'>❌ Tidak ada sesi login reseller!</p>";
    exit;
}

$dataDir = '/etc/xray/data-panel/reseller';
$files = glob("$dataDir/akun-$reseller-*.txt");

$statistik = [
    'vmess' => [],
    'vless' => [],
    'trojan' => [],
    'shadowsocks' => []
];

$prefixMap = [
    '###' => 'vmess',
    '#&'  => 'vless',
    '#!'  => 'trojan',
    '#$'  => 'shadowsocks'
];

$today = date('Y-m-d');
$sevenDaysLater = date('Y-m-d', strtotime('+7 days'));

foreach ($files as $file) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $isParsed = false;

    // Cek apakah file ini pakai prefix (#)
    foreach ($lines as $line) {
        if (preg_match('/^(###|#&|#!|#\$)\s+(\S+)\s+(\d{4}-\d{2}-\d{2})$/', $line, $m)) {
            list($_, $prefix, $username, $expired) = $m;
            $proto = $prefixMap[$prefix] ?? null;
            if (!$proto) continue;

            $status = ($expired < $today) ? 'expired' : (($expired <= $sevenDaysLater) ? 'expiring' : 'active');
            $statistik[$proto][] = [
                'username' => $username,
                'expired'  => $expired,
                'status'   => $status,
                'online'   => false
            ];
            $isParsed = true;
        }
    }

    // Kalau tidak pakai prefix, deteksi dari isi isi file (X ACCOUNT)
    if (!$isParsed) {
        $isi = file_get_contents($file);

        if (preg_match('/\s*([A-Z]+)\s+ACCOUNT/i', $isi, $match)) {
            $proto = strtolower($match[1]);
            if (!in_array($proto, ['vmess', 'vless', 'trojan', 'shadowsocks'])) {
                continue;
            }

            if (preg_match('/Remarks\s*:\s*(\S+)/i', $isi, $mUser) &&
                preg_match('/Expired On\s*:\s*(\d{4}-\d{2}-\d{2})/i', $isi, $mExp)) {

                $username = trim($mUser[1]);
                $expired = trim($mExp[1]);

                $status = ($expired < $today) ? 'expired' : (($expired <= $sevenDaysLater) ? 'expiring' : 'active');

                $statistik[$proto][] = [
                    'username' => $username,
                    'expired'  => $expired,
                    'status'   => $status,
                    'online'   => false
                ];
            }
        }
    }
}

function countStatus($data, $status) {
    return count(array_filter($data, fn($x) => $x['status'] === $status));
}
?>

<!-- TAMPILAN HTML TETAP -->
<div class="max-w-7xl mx-auto px-4 py-10">
  <div class="text-center mb-10">
    <h1 class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-sky-400 to-blue-600">
      📊 Statistik Akun Anda
    </h1>
    <p class="mt-2 text-gray-400">Reseller: <span class="font-semibold text-blue-300">@<?= htmlspecialchars($reseller) ?></span></p>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mb-12">
    <?php
    $icons = [
        'vmess' => ['emoji' => '🌀', 'color' => 'from-blue-500 to-blue-700'],
        'vless' => ['emoji' => '🔮', 'color' => 'from-purple-400 to-purple-600'],
        'trojan' => ['emoji' => '⚔', 'color' => 'from-yellow-400 to-orange-500'],
        'shadowsocks' => ['emoji' => '🕶', 'color' => 'from-green-300 to-teal-400'],
    ];

    foreach ($statistik as $proto => $akun):
        $icon = $icons[$proto]['emoji'];
        $gradient = $icons[$proto]['color'];
        $total = count($akun);
        $active = countStatus($akun, 'active');
        $expiring = countStatus($akun, 'expiring');
        $expired = countStatus($akun, 'expired');
    ?>
      <div class="flex flex-col justify-between h-full min-h-[240px] rounded-xl p-6 shadow-lg text-white bg-gradient-to-br <?= $gradient ?> hover:scale-[1.02] transition-transform duration-200">
        <div class="text-center">
          <div class="text-4xl"><?= $icon ?></div>
          <h2 class="text-lg font-semibold mt-2"><?= strtoupper($proto) ?></h2>
        </div>
        <div class="mt-4 space-y-1 text-sm">
          <div class="flex justify-between border-b border-white/20 pb-1">
            <span>Total</span><span class="font-bold"><?= $total ?></span>
          </div>
          <div class="flex justify-between">
            <span class="text-green-300">Aktif</span><span><?= $active ?></span>
          </div>
          <div class="flex justify-between">
            <span class="text-yellow-300">Mau Expired</span><span><?= $expiring ?></span>
          </div>
          <div class="flex justify-between">
            <span class="text-red-400">Expired</span><span><?= $expired ?></span>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php foreach ($statistik as $proto => $akun): ?>
    <?php if (empty($akun)) continue; ?>
    <div class="bg-gray-900 rounded-2xl p-6 shadow-xl mb-10">
      <h2 class="text-2xl font-bold text-white mb-6 border-b border-gray-700 pb-2"><?= strtoupper($proto) ?> - Detail Akun</h2>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-800 text-sm text-left">
          <thead class="bg-gray-800 text-gray-300">
            <tr>
              <th class="px-4 py-3">#</th>
              <th class="px-4 py-3">👤 Username</th>
              <th class="px-4 py-3">📅 Expired</th>
              <th class="px-4 py-3">📌 Status</th>
              <th class="px-4 py-3">🌐 Online</th>
            </tr>
          </thead>
          <tbody class="bg-gray-700 divide-y divide-gray-800 text-white">
            <?php $no = 1; foreach ($akun as $u): ?>
              <tr class="hover:bg-gray-600 transition">
                <td class="px-4 py-2"><?= $no++ ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($u['username']) ?></td>
                <td class="px-4 py-2"><?= $u['expired'] ?></td>
                <td class="px-4 py-2">
                  <?php
                  switch ($u['status']) {
                      case 'active':
                          echo '<span class="inline-block px-2 py-1 text-green-400 bg-green-900 rounded-full text-xs">Aktif</span>';
                          break;
                      case 'expiring':
                          echo '<span class="inline-block px-2 py-1 text-yellow-400 bg-yellow-900 rounded-full text-xs">Segera Expired</span>';
                          break;
                      case 'expired':
                          echo '<span class="inline-block px-2 py-1 text-red-400 bg-red-900 rounded-full text-xs">Expired</span>';
                          break;
                  }
                  ?>
                </td>
                <td class="px-4 py-2">
                  <?= $u['online'] ? '<span class="text-green-300 font-medium">Online</span>' : '<span class="text-gray-400">Offline</span>' ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endforeach; ?>
</div>

