<?php
require '../koneksi.php';

session_start();

// Handle kirim pesan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_pesan'])) {
    $pesan = trim($_POST['pesan']);
    $isBroadcast = isset($_POST['broadcast']);
    $target = $_POST['target_reseller'] ?? '';

    if (!empty($pesan)) {
        if ($isBroadcast) {
            $result = $conn->query("SELECT username FROM users WHERE role = 'reseller'");
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $reseller = $row['username'];
                    $stmt = $conn->prepare("INSERT INTO notifikasi_reseller (username, pesan) VALUES (?, ?)");
                    $stmt->bind_param("ss", $reseller, $pesan);
                    $stmt->execute();
                    $stmt->close();
                }
                $sukses = true;
            } else {
                $error = "Tidak ada reseller ditemukan.";
            }
        } else {
            if (!empty($target)) {
                $stmt = $conn->prepare("INSERT INTO notifikasi_reseller (username, pesan) VALUES (?, ?)");
                $stmt->bind_param("ss", $target, $pesan);
                $stmt->execute();
                $stmt->close();
                $sukses = true;
            } else {
                $error = "Pilih reseller yang ingin dikirimi pesan.";
            }
        }
    } else {
        $error = "Isi pesan tidak boleh kosong.";
    }
}

// Handle hapus
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    $conn->query("DELETE FROM notifikasi_reseller WHERE id = $id");
    header("Location: pesan.php");
    exit;
}

// Ambil semua pesan unik (per user)
$riwayat = $conn->query("SELECT id, username, pesan, dibuat_pada FROM notifikasi_reseller ORDER BY dibuat_pada DESC");

// Ambil semua reseller
$resellerList = [];
$hasil = $conn->query("SELECT username FROM users WHERE role = 'reseller'");
while ($row = $hasil->fetch_assoc()) {
    $resellerList[] = $row['username'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Kirim Notifikasi Reseller</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen p-6 text-gray-900 dark:text-gray-100">

<div class="max-w-3xl mx-auto space-y-8">
    <!-- Alert -->
    <?php if (isset($sukses)): ?>
        <div class="p-4 bg-green-100 dark:bg-green-800 text-green-800 dark:text-green-100 rounded shadow">
            ✅ Notifikasi berhasil dikirim.
        </div>
    <?php elseif (isset($error)): ?>
        <div class="p-4 bg-red-100 dark:bg-red-800 text-red-800 dark:text-red-100 rounded shadow">
            ❌ <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Form Kirim -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow border dark:border-gray-700">
        <h2 class="text-xl font-bold mb-4">📢 Kirim Notifikasi</h2>
        <form method="POST" class="space-y-5">
            <textarea 
                name="pesan" 
                rows="5" 
                required
                placeholder="Tulis pesan ke reseller..." 
                class="w-full p-4 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"
            ></textarea>

            <!-- Toggle Broadcast -->
            <div class="flex items-center justify-between">
                <label class="flex items-center gap-3 cursor-pointer">
                    <span class="text-sm font-semibold">Broadcast ke Semua</span>
                    <input type="checkbox" id="broadcastToggle" name="broadcast" checked class="sr-only">
                    <div id="broadcastIndicator" class="w-12 h-6 bg-green-500 rounded-full relative transition-all duration-300">
                        <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full transition-all duration-300"></div>
                    </div>
                </label>
            </div>

            <!-- Pilih Reseller -->
            <div id="resellerSelect" class="hidden">
                <label class="block text-sm font-medium mb-1">Pilih Reseller:</label>
                <select name="target_reseller" class="w-full p-3 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">-- Pilih --</option>
                    <?php foreach ($resellerList as $reseller): ?>
                        <option value="<?= htmlspecialchars($reseller) ?>"><?= htmlspecialchars($reseller) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button 
                type="submit" 
                name="kirim_pesan"
                class="w-full bg-blue-600 text-white py-3 rounded hover:bg-blue-700 transition font-semibold"
            >
                🚀 Kirim Sekarang
            </button>
        </form>
    </div>

    <!-- Riwayat Pesan -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow border dark:border-gray-700">
        <h2 class="text-xl font-bold mb-4">🕒 Riwayat Pesan Terkirim</h2>
        <?php if ($riwayat->num_rows > 0): ?>
            <ul class="space-y-3">
                <?php while ($row = $riwayat->fetch_assoc()): ?>
                    <li class="p-4 bg-gray-50 dark:bg-gray-700 rounded flex justify-between items-start">
                        <div>
                            <div class="text-sm text-gray-700 dark:text-gray-200 font-semibold"><?= htmlspecialchars($row['username']) ?></div>
                            <div class="text-sm text-gray-600 dark:text-gray-300 mt-1"><?= htmlspecialchars($row['pesan']) ?></div>
                            <div class="text-xs text-gray-400 mt-1"><?= date('d M Y H:i', strtotime($row['dibuat_pada'])) ?></div>
                        </div>
                        <a href="?hapus=<?= $row['id'] ?>" onclick="return confirm('Hapus pesan ini?')" class="text-red-600 dark:text-red-400 hover:underline text-sm">🗑 Hapus</a>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <div class="text-gray-500 dark:text-gray-400 italic">Belum ada pesan dikirim.</div>
        <?php endif; ?>
    </div>
</div>

<!-- JS Toggle -->
<script>
const toggle = document.getElementById('broadcastToggle');
const indicator = document.getElementById('broadcastIndicator');
const resellerSelect = document.getElementById('resellerSelect');

toggle.addEventListener('change', () => {
    if (toggle.checked) {
        indicator.classList.remove('bg-red-500');
        indicator.classList.add('bg-green-500');
        indicator.firstElementChild.style.transform = 'translateX(0)';
        resellerSelect.classList.add('hidden');
    } else {
        indicator.classList.remove('bg-green-500');
        indicator.classList.add('bg-red-500');
        indicator.firstElementChild.style.transform = 'translateX(24px)';
        resellerSelect.classList.remove('hidden');
    }
});
</script>
</body>
</html>

