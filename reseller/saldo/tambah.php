<?php
session_start();
require '../koneksi.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Ambil semua user
$users = [];
$result = $conn->query("SELECT username, role FROM users ORDER BY role DESC, username ASC");
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tambah Saldo Reseller/Admin</title>
    <meta charset="UTF-8">
    <link href="https://unpkg.com/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script>
    function toggleManualInput(select) {
        const manualDiv = document.getElementById('manualUsername');
        const manualInput = document.getElementById('manualInput');
        const hiddenInput = document.getElementById('finalUsername');

        if (select.value === 'manual') {
            manualDiv.style.display = 'block';
            hiddenInput.value = '';
            manualInput.addEventListener('input', () => {
                hiddenInput.value = manualInput.value;
            });
        } else {
            manualDiv.style.display = 'none';
            hiddenInput.value = select.value;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const select = document.querySelector('select[name="username_select"]');
        toggleManualInput(select);
    });
    </script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-md mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-xl font-bold mb-4">Tambah Saldo Reseller / Admin</h1>
        <form action="proses_tambah.php" method="POST" class="space-y-4">
            <!-- Hidden input username final -->
            <input type="hidden" name="username" id="finalUsername">

            <!-- Dropdown username -->
            <div>
                <label class="block mb-1 font-medium">Pilih Username</label>
                <select name="username_select" onchange="toggleManualInput(this)" class="w-full border p-2 rounded">
                    <?php foreach ($users as $user): ?>
                        <option value="<?= htmlspecialchars($user['username']) ?>">
                            <?= htmlspecialchars($user['username']) ?><?= $user['role'] === 'admin' ? ' (admin)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="manual">Manual (ketik sendiri)</option>
                </select>
            </div>

            <!-- Input manual username -->
            <div id="manualUsername" style="display: none;">
                <label class="block mb-1 font-medium">Masukkan Username Manual</label>
                <input type="text" id="manualInput" class="w-full border p-2 rounded">
            </div>

            <!-- Jumlah saldo -->
            <div>
                <label class="block mb-1 font-medium">Jumlah Tambah Saldo</label>
                <input type="number" name="jumlah" min="1" required class="w-full border p-2 rounded">
            </div>

            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                Tambah Saldo
            </button>
        </form>
    </div>
</body>
</html>

