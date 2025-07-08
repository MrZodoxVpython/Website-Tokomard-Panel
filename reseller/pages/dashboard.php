<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$reseller = $_SESSION['reseller'] ?? $_SESSION['username'] ?? 'unknown';

$stats = ['total' => 0, 'vmess' => 0, 'vless' => 0, 'trojan' => 0, 'shadowsocks' => 0];
$rows = [];
$no = 1;

function read_remote_files($remote_ip, $remote_user, $reseller, $server_name) {
    $data = [];
    $remote_dir = "/etc/xray/data-panel/reseller/";
    $list_cmd = "find {$remote_dir} -type f -iname 'akun-{$reseller}-*.txt' | while read file; do echo \"===FILE:\$file===\"; cat \"\$file\"; done";
    $output = shell_exec("ssh -o StrictHostKeyChecking=no {$remote_user}@{$remote_ip} '{$list_cmd}' 2>/dev/null");
    if (!$output) return [];
    $blocks = preg_split('/===FILE:(.*?)===/', $output, -1, PREG_SPLIT_DELIM_CAPTURE);

    for ($i = 1; $i < count($blocks); $i += 2) {
        $file = trim($blocks[$i]);
        $content = trim($blocks[$i + 1]);
        $buyer = basename($file, ".txt");
        if (preg_match('/akun\-[^-]+-(.+)$/i', $buyer, $match)) {
            $buyer = $match[1];
        }
        $lines = explode("\n", $content);
        $proto = null;
        $expired = "-";
        $uuidOrPass = "-";

	foreach ($lines as $line) {
	    if (stripos($line, 'TROJAN ACCOUNT') !== false) {
   	        $proto = 'trojan';
   	    } elseif (stripos($line, 'VMESS ACCOUNT') !== false) {
       	        $proto = 'vmess';
    	    } elseif (stripos($line, 'VLESS ACCOUNT') !== false) {
        	$proto = 'vless';
    	    } elseif (stripos($line, 'SHADOWSOCKS ACCOUNT') !== false) {
        	$proto = 'shadowsocks';
    	    } elseif (stripos($line, 'Expired On') !== false) {
        	$expired = trim(explode(':', $line, 2)[1] ?? '-');
   	    } elseif (stripos($line, 'Password') !== false && $uuidOrPass === '-') {
        	$uuidOrPass = trim(explode(':', $line, 2)[1] ?? '-');
    	    } elseif (stripos($line, 'ID') !== false && $uuidOrPass === '-') {
        	$uuidOrPass = trim(explode(':', $line, 2)[1] ?? '-');
            }
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

// Remote VPS list
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
	elseif (
	    stripos($line, 'Password') !== false ||
	    stripos($line, 'UUID') !== false
        ) {
    	    $uuidOrPass = trim(explode(':', $line, 2)[1] ?? '-');
	}
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

<!-- Statistik Responsive + Tooltip & Clickable -->
<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2 sm:gap-4 mb-4 text-center">
<?php
// Fungsi JS untuk tampilkan isi teks saat diklik (mobile)
echo "<script>
function showFullText(text) {
    alert(text);
}
</script>";

// Saldo Anda
echo "<div class='bg-orange-100 dark:bg-orange-800 text-orange-900 dark:text-white p-2 sm:p-4 text-[13px] sm:text-sm rounded-lg shadow overflow-hidden max-w-full cursor-pointer' title='Total Saldo Anda: {$formattedSaldo}' onclick=\"showFullText('Total Saldo Anda: {$formattedSaldo}')\">
        <p class='font-semibold'>Total Saldo Anda</p>
        <p class='text-base sm:text-lg font-bold'>{$formattedSaldo}</p>
      </div>";

// Statistik Lainnya
foreach (['total'=>'Total Akun','vmess'=>'VMess','vless'=>'VLess','trojan'=>'Trojan','shadowsocks'=>'Shadowsocks'] as $k => $label) {
    $color = ['total'=>'green','vmess'=>'blue','vless'=>'purple','trojan'=>'red','shadowsocks'=>'yellow'][$k];
    $value = $stats[$k];
    echo "<div class='bg-{$color}-100 dark:bg-{$color}-800 text-{$color}-900 dark:text-white p-2 sm:p-4 text-[13px] sm:text-sm rounded-lg shadow overflow-hidden max-w-full cursor-pointer' title='{$label}: {$value}' onclick=\"showFullText('{$label}: {$value}')\">
            <p class='font-semibold truncate'>{$label}</p>
            <p class='text-base sm:text-lg font-bold truncate'>{$value}</p>
          </div>";
}
?>
</div>


<!-- Grafik -->
<div class="overflow-x-auto w-full mt-4">
  <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow border border-gray-300 dark:border-gray-700 w-full min-w-full">
    <div class="relative w-full h-[200px] sm:h-[260px]">
      <canvas id="myChart"></canvas>
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
            label: "Jumlah Akun",
            data: [<?= $stats['vmess'] ?>, <?= $stats['vless'] ?>, <?= $stats['trojan'] ?>, <?= $stats['shadowsocks'] ?>],
            backgroundColor: ["#3b82f6", "#8b5cf6", "#ef4444", "#10b981"],
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { color: "#94a3b8" },
                grid: { color: "rgba(255,255,255,0.1)" }
            },
            x: {
                ticks: { color: "#94a3b8" },
                grid: { color: "rgba(255,255,255,0.05)" }
            }
        }
    }
});
</script>


<!-- Tabel Akun -->
<div class="overflow-x-auto w-full mt-4">
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-300 dark:border-gray-700 min-w-full">
    <table class="w-full text-[11px] sm:text-sm text-left text-gray-800 dark:text-white table-fixed">
      <thead class="bg-gray-100 dark:bg-gray-700">
        <tr>
          <th class="px-2 md:px-4 py-2 w-[15%]">No</th>
          <th class="px-2 md:px-4 py-2 w-[30%]">Username</th>
          <th class="px-2 md:px-4 py-2 w-[60%]">Protocol</th>
          <th class="px-2 md:px-4 py-2 w-[105%]">Expired</th>
          <th class="px-2 md:px-4 py-2 w-[45%]">UUID/Pass</th>
          <th class="px-2 md:px-4 py-2 w-[20%] hidden md:table-cell">VPS</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)) : ?>
          <tr>
            <td colspan="6" class="text-center px-4 py-4 text-gray-500">Belum ada akun</td>
          </tr>
        <?php else : ?>
          <?php foreach ($rows as $r) : ?>
            <tr class="border-t border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
              <td class="px-2 md:px-4 py-2"><?= $r['no'] ?></td>
              <td class="px-2 md:px-4 py-2 truncate"><?= $r['user'] ?></td>
              <td class="px-2 md:px-4 py-2"><?= $r['proto'] ?></td>
              <td class="px-2 md:px-4 py-2"><?= $r['exp'] ?></td>
              <td class="px-2 md:px-4 py-2 font-mono break-all"><?= $r['buyer'] ?></td>
              <td class="px-2 md:px-4 py-2 hidden md:table-cell truncate"><?= $r['server_name'] ?></td>
            </tr>
          <?php endforeach ?>
        <?php endif ?>
      </tbody>
    </table>
  </div>
</div>

