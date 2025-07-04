<?php
session_start();
require '../koneksi.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Ambil semua user (admin dan reseller)
$users = [];
$result = $conn->query("SELECT username, role FROM users");
while ($row = $result->fetch_assoc()) {
    $users[] = [
        'username' => $row['username'],
        'role' => $row['role']
    ];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kurangi Saldo</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script>
        // Autofill kolom username saat pilih dari dropdown
        function autofillUsername(select) {
            const input = document.getElementById("usernameInput");
            input.value = select.value;
        }
    </script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-md mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-xl font-bold mb-4">Kurangi Saldo</h1>
        <form action="proses_kurangi.php" method="POST" class="space-y-4">

            <div>
                <label class="block mb-1 font-medium">Pilih Username</label>
                <select onchange="autofillUsername(this)" class="w-full border p-2 rounded">
                    <option value="">-- Pilih User --</option>
                    <?php foreach ($users as $user): ?>
                        <?php
                            $label = $user['username'];
                            if ($user['role'] === 'admin') {
                                $label .= ' (admin)';
                            }
                        ?>
                        <option value="<?= htmlspecialchars($user['username']) ?>">
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block mb-1 font-medium">Atau Masukkan Username</label>
                <input type="text" name="username" id="usernameInput" required class="w-full border p-2 rounded">
            </div>

            <div>
                <label class="block mb-1 font-medium">Jumlah Saldo yang Dikurangi</label>
                <input type="number" name="jumlah" min="1" required class="w-full border p-2 rounded">
            </div>

            <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Kurangi Saldo</button>
        </form>
    </div>
</body>
</html>

