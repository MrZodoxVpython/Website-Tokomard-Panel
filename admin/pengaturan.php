<?php
// Contoh: proses simpan pengaturan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $nama_vps = $_POST['nama_vps'] ?? '';
    $port = $_POST['port'] ?? '';
    $status = isset($_POST['status']) ? 'aktif' : 'nonaktif';

    // TODO: simpan ke database / file konfigurasi

    $message = "Pengaturan berhasil disimpan.";
}
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth" data-theme="dark">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Pengaturan Panel VPS Tunneling</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Dark mode toggle style */
        body.dark {
            background-color: #1a202c;
            color: #cbd5e1;
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-200 min-h-screen flex flex-col">

    <header class="bg-gray-800 p-4 flex justify-between items-center">
        <h1 class="text-xl font-semibold">Pengaturan VPS Tunneling</h1>
        <button id="darkToggle" class="px-3 py-1 border rounded border-gray-500 hover:bg-gray-700">
            Toggle Dark Mode
        </button>
    </header>

    <main class="flex-grow container mx-auto p-6 max-w-lg">
        <?php if (isset($message)): ?>
            <div class="mb-4 p-3 bg-green-700 rounded text-green-100"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-6 bg-gray-800 p-6 rounded shadow-lg">
            <div>
                <label for="nama_vps" class="block mb-2 font-medium">Nama VPS</label>
                <input type="text" name="nama_vps" id="nama_vps" placeholder="Masukkan nama VPS" required
                    class="w-full rounded border border-gray-600 bg-gray-900 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>

            <div>
                <label for="port" class="block mb-2 font-medium">Port Tunneling</label>
                <input type="number" name="port" id="port" placeholder="e.g. 8080" min="1" max="65535" required
                    class="w-full rounded border border-gray-600 bg-gray-900 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>

            <div class="flex items-center space-x-3">
                <input type="checkbox" name="status" id="status" value="aktif" class="w-5 h-5 rounded bg-gray-700 checked:bg-blue-600 checked:ring-2 checked:ring-blue-400" />
                <label for="status" class="font-medium">Aktifkan VPS</label>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded transition">
                Simpan Pengaturan
            </button>
        </form>
    </main>

    <footer class="bg-gray-800 text-center p-4 text-sm text-gray-500">
        &copy; 2025 Panel VPS Tunneling
    </footer>

    <script>
        // Dark mode toggle script
        const toggleBtn = document.getElementById('darkToggle');
        const htmlTag = document.documentElement;
        const bodyTag = document.body;

        // Cek mode dark dari localStorage
        if(localStorage.getItem('darkMode') === 'enabled') {
            htmlTag.classList.add('dark');
            bodyTag.classList.add('dark');
        }

        toggleBtn.addEventListener('click', () => {
            htmlTag.classList.toggle('dark');
            bodyTag.classList.toggle('dark');

            if(htmlTag.classList.contains('dark')){
                localStorage.setItem('darkMode', 'enabled');
            } else {
                localStorage.setItem('darkMode', 'disabled');
            }
        });
    </script>

</body>
</html>

