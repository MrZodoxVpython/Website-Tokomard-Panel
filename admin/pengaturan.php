<?php
// Simulasi proses simpan pengaturan admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $panel_name = $_POST['panel_name'] ?? '';
    $admin_password = $_POST['admin_password'] ?? '';
    $default_port = $_POST['default_port'] ?? '';
    $max_connections = $_POST['max_connections'] ?? '';
    $enable_logging = isset($_POST['enable_logging']) ? '1' : '0';
    $dark_mode_default = isset($_POST['dark_mode_default']) ? '1' : '0';

    // TODO: simpan ke database atau file konfigurasi

    $message = "Pengaturan admin berhasil disimpan.";
}
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth" data-theme="dark">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Pengaturan Admin Panel VPS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body.dark {
            background-color: #1a202c;
            color: #cbd5e1;
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-200 min-h-screen flex items-center justify-center p-6">

    <div class="w-full max-w-2xl bg-gray-800 rounded-lg p-8 shadow-lg">
        <?php if (isset($message)): ?>
            <div class="mb-6 p-4 bg-green-700 rounded text-green-100"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <h2 class="text-2xl font-semibold mb-4">Pengaturan Admin Panel VPS</h2>

            <div>
                <label for="panel_name" class="block mb-2 font-medium">Nama Panel</label>
                <input type="text" name="panel_name" id="panel_name" placeholder="Contoh: Panel VPS Saya" required
                    class="w-full rounded border border-gray-600 bg-gray-900 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>

            <div>
                <label for="admin_password" class="block mb-2 font-medium">Password Admin Baru</label>
                <input type="password" name="admin_password" id="admin_password" placeholder="Kosongkan jika tidak diganti"
                    class="w-full rounded border border-gray-600 bg-gray-900 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>

            <div>
                <label for="default_port" class="block mb-2 font-medium">Default Port Tunneling</label>
                <input type="number" name="default_port" id="default_port" min="1" max="65535" required
                    class="w-full rounded border border-gray-600 bg-gray-900 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>

            <div>
                <label for="max_connections" class="block mb-2 font-medium">Max Koneksi per User</label>
                <input type="number" name="max_connections" id="max_connections" min="1" max="1000" required
                    class="w-full rounded border border-gray-600 bg-gray-900 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>

            <div class="flex items-center space-x-3">
                <input type="checkbox" name="enable_logging" id="enable_logging" class="w-5 h-5 rounded bg-gray-700 checked:bg-blue-600 checked:ring-2 checked:ring-blue-400" />
                <label for="enable_logging" class="font-medium">Aktifkan Logging</label>
            </div>

            <div class="flex items-center space-x-3">
                <input type="checkbox" name="dark_mode_default" id="dark_mode_default" class="w-5 h-5 rounded bg-gray-700 checked:bg-blue-600 checked:ring-2 checked:ring-blue-400" />
                <label for="dark_mode_default" class="font-medium">Default Mode Gelap (Dark Mode)</label>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded transition">
                Simpan Pengaturan
            </button>
        </form>
    </div>

</body>
</html>

