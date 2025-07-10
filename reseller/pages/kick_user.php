<?php
session_start();

// Validasi hanya admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak.");
}

$userFile = __DIR__ . '/../data/reseller_users.json';

// Ambil daftar user
$users = [];
if (file_exists($userFile)) {
    $users = json_decode(file_get_contents($userFile), true) ?? [];
}

// Jika form dikirim (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameToKick = $_POST['username'] ?? '';

    if (!$usernameToKick) {
        $message = "Username tidak valid.";
    } else {
        $found = false;
        foreach ($users as &$user) {
            if (strtolower(trim($user['username'])) === strtolower(trim($usernameToKick))) {
                $user['status'] = 'pending';
                $found = true;
                break;
            }
        }

        if ($found) {
            file_put_contents($userFile, json_encode($users, JSON_PRETTY_PRINT));
            $message = "✅ User <b>$usernameToKick</b> berhasil dikeluarkan dan harus daftar ulang.";
        } else {
            $message = "❌ User tidak ditemukan.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Kick User</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.1/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-10">

    <div class="max-w-md mx-auto bg-white rounded-xl shadow-md p-6">
        <h1 class="text-2xl font-bold mb-4 text-center text-red-600">Kick User VIP</h1>

        <?php if (isset($message)): ?>
            <div class="mb-4 p-3 bg-yellow-100 text-yellow-800 rounded">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <label class="block font-medium text-gray-700">Pilih User yang akan di-Kick:</label>
            <select name="username" class="w-full p-2 border rounded">
                <option value="">-- Pilih Username --</option>
                <?php foreach ($users as $u): ?>
                    <?php if (strtolower($u['status']) === 'approved'): ?>
                        <option value="<?= htmlspecialchars($u['username']) ?>">
                            <?= htmlspecialchars($u['username']) ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="w-full bg-red-600 text-white py-2 rounded hover:bg-red-700">Kick User</button>
        </form>
    </div>

</body>
</html>

