<?php
session_start();
require_once __DIR__ . 'koneksi.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    echo "Akses ditolak.";
    exit;
}

// Handle penghapusan transaksi
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    $conn->query("DELETE FROM transactions WHERE id = $del_id");
    header("Location: transaksi.php");
    exit;
}

// Ambil daftar user
$users = [];
$res = $conn->query("SELECT id, username FROM users ORDER BY username ASC");
while ($r = $res->fetch_assoc()) {
    $users[] = $r;
}

// Filter transaksi berdasarkan user terpilih
$selected_user = $_GET['user'] ?? '';
$transactions = [];
if ($selected_user) {
    $stmt = $conn->prepare("SELECT t.id, t.type, t.status, t.amount, t.detail, t.date, u.username FROM transactions t JOIN users u ON t.user_id = u.id WHERE u.username = ? ORDER BY t.date DESC");
    $stmt->bind_param("s", $selected_user);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Riwayat Transaksi</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white p-6">
  <div class="max-w-6xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Riwayat Transaksi</h1>

    <!-- Dropdown User -->
    <form method="GET" class="mb-6">
      <label for="user" class="block mb-2 text-gray-300">Pilih User</label>
      <select name="user" id="user" class="bg-gray-700 text-white p-2 rounded w-60" onchange="this.form.submit()">
        <option value="">-- Pilih User --</option>
        <?php foreach ($users as $u): ?>
        <option value="<?= htmlspecialchars($u['username']) ?>" <?= $selected_user === $u['username'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($u['username']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </form>

    <?php if ($selected_user): ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm table-auto border border-gray-700">
        <thead class="bg-gray-700">
          <tr>
            <th class="p-2 text-left">Tipe</th>
            <th class="p-2 text-left">Status</th>
            <th class="p-2 text-left">Jumlah</th>
            <th class="p-2 text-left">Detail</th>
            <th class="p-2 text-left">Tanggal</th>
            <th class="p-2 text-left">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($transactions as $trx): ?>
          <tr class="border-t border-gray-600 hover:bg-gray-800">
            <td class="p-2"><?= htmlspecialchars($trx['type']) ?></td>
            <td class="p-2 text-green-400"><?= htmlspecialchars($trx['status']) ?></td>
            <td class="p-2">Rp. <?= number_format($trx['amount'], 0, ',', '.') ?></td>
            <td class="p-2"><?= htmlspecialchars($trx['detail']) ?></td>
            <td class="p-2"><?= htmlspecialchars($trx['date']) ?></td>
            <td class="p-2">
              <a href="?user=<?= urlencode($selected_user) ?>&delete_id=<?= $trx['id'] ?>" onclick="return confirm('Yakin ingin hapus transaksi ini?')" class="text-red-400 hover:text-red-600">Hapus</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</body>
</html>

