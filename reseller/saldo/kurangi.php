<?php
session_start();
require '../../koneksi.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Ambil semua username dari semua role
$resellers = [];
$result = $conn->query("SELECT username FROM users");
while ($row = $result->fetch_assoc()) {
    $resellers[] = $row['username'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kurangi Saldo</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script>
        function autofillUsername(select) {
            document.getElementById('usernameInput').value = select.value;
        }
    </script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-md mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-xl font-bold mb-4">Kurangi Saldo User (Admin atau Reseller)</h1>
        <form action="proses_kurangi.php" method="POST" class="space-y-4">
            <div>
                <label class="block mb-1 font-medium">Pilih Username (opsional)</label>
                <select onchange="autofillUsername(this)" class="w-full border p-2 rounded">
                    <option value="">-- Pilih User --</option>
                    <?php foreach ($resellers as $reseller): ?>
                        <option value="<?= htmlspecialchars($reseller) ?>"><?= htmlspecialchars($reseller) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block mb-1 font-medium">Atau Ketik Username</label>
                <input type="text" name="username" id="usernameInput" required class="w-full border p-2 rounded">
            </div>
            <div>
                <label class="block mb-1 font-medium">Jumlah Saldo yang Dikurangi</label>
                <input type="number" name="jumlah" min="1" required class="w-full border p-2 rounded">
            </div>
            <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded">Kurangi Saldo</button>
        </form>
    </div>
</body>
</html>

