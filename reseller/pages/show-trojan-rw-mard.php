<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$reseller = $_SESSION['reseller'] ?? $_SESSION['username'] ?? 'unknown';

$remoteIP = '203.194.113.140'; // IP VPS remote
$sshUser = 'root';
$remotePath = "/etc/xray/data-panel/reseller";
$sshPrefix = "ssh -o ConnectTimeout=5 -o StrictHostKeyChecking=no $sshUser@$remoteIP";

// === Proses Aksi Stop, Delete, Edit ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $username = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['username'] ?? '');
    $escapedFile = escapeshellarg("$remotePath/akun-$reseller-$username.txt");

    if ($aksi === 'stop') {
        // Tandai akun sebagai nonaktif (tambahkan baris DISABLED jika belum ada)
        $cmd = "$sshPrefix \"grep -q 'DISABLED' $escapedFile || echo 'DISABLED' >> $escapedFile\"";
        shell_exec($cmd);
    } elseif ($aksi === 'delete') {
        $cmd = "$sshPrefix 'rm -f $escapedFile'";
        shell_exec($cmd);
    } elseif ($aksi === 'edit') {
        $new_expired = preg_replace('/[^0-9\-]/', '', $_POST['expired'] ?? '');
        $cmd = "$sshPrefix \"sed -i 's/^Expired On.*/Expired On: $new_expired/' $escapedFile\"";
        shell_exec($cmd);
    }

    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// === Ambil Daftar Akun ===
$cmdListFiles = "$sshPrefix 'ls $remotePath/akun-$reseller-*.txt 2>/dev/null'";
$fileListRaw = shell_exec($cmdListFiles);
$fileList = array_filter(explode("\n", trim($fileListRaw ?? '')));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Trojan - RW-MARD</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen p-6">
<div class="max-w-5xl mx-auto">
    <h1 class="text-2xl font-bold text-center mb-6">Daftar Akun Trojan (RW-MARD) - <?= htmlspecialchars($reseller) ?></h1>

    <?php if (empty($fileList)) : ?>
        <div class="text-center bg-yellow-500/10 border border-yellow-400 text-yellow-300 p-4 rounded">
            ⚠ Belum ada daftar akun untuk reseller <strong><?= htmlspecialchars($reseller) ?></strong>,
            silakan buat akun terlebih dahulu.
        </div>
    <?php else: ?>
        <?php foreach ($fileList as $remoteFile):
            $filename = basename($remoteFile);
            preg_match('/akun-' . preg_quote($reseller, '/') . '-(.+)\.txt/', $filename, $m);
            $username = $m[1] ?? 'unknown';
            $escapedFile = escapeshellarg($remoteFile);
            $sshCatCmd = "$sshPrefix 'cat $escapedFile'";
            $content = trim(shell_exec($sshCatCmd));
        ?>
        <div class="bg-gray-800 rounded shadow mb-6 p-4">
            <div class="flex justify-between items-center flex-wrap">
                <div class="text-blue-400 font-semibold text-lg"><?= htmlspecialchars($username) ?></div>
                <div class="flex flex-col sm:flex-row gap-2 mt-2 sm:mt-0">
                    <button onclick="toggleDetail('<?= $username ?>')" id="btn-<?= $username ?>" class="toggle-btn bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded text-white">Show</button>

                    <!-- STOP -->
                    <form method="POST" class="inline">
                        <input type="hidden" name="aksi" value="stop">
                        <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
                        <button class="bg-yellow-600 hover:bg-yellow-700 px-3 py-1 rounded">Stop</button>
                    </form>

                    <!-- DELETE -->
                    <form method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus akun ini?')">
                        <input type="hidden" name="aksi" value="delete">
                        <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
                        <button class="bg-red-600 hover:bg-red-700 px-3 py-1 rounded">Delete</button>
                    </form>

                    <!-- EDIT (TGL EXPIRED) -->
                    <form method="POST" class="inline flex gap-1">
                        <input type="hidden" name="aksi" value="edit">
                        <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
                        <input type="date" name="expired" required class="text-black px-2 py-1 rounded">
                        <button class="bg-green-600 hover:bg-green-700 px-3 py-1 rounded">Edit</button>
                    </form>
                </div>
            </div>
            <div id="detail-<?= $username ?>" class="hidden mt-4">
                <div class="overflow-x-auto bg-gray-700 rounded p-3">
                    <pre class="text-sm text-green-300 whitespace-pre-wrap"><?= htmlspecialchars($content ?: "❌ Gagal membaca isi file atau file kosong.") ?></pre>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function toggleDetail(id) {
    const detailBox = document.getElementById('detail-' + id);
    const btn = document.getElementById('btn-' + id);
    const isHidden = detailBox.classList.contains('hidden');
    
    // Sembunyikan semua detail & reset semua tombol
    document.querySelectorAll('[id^="detail-"]').forEach(e => e.classList.add('hidden'));
    document.querySelectorAll('.toggle-btn').forEach(b => b.textContent = 'Show');

    if (isHidden) {
        detailBox.classList.remove('hidden');
        btn.textContent = 'Hide';
    }
}
</script>
</body>
</html>

