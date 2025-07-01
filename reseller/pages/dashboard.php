<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$reseller = $_SESSION['reseller'] ?? $_SESSION['username'] ?? 'unknown';

$total = 0;
$vmess = 0;
$vless = 0;
$trojan = 0;
$shadowsocks = 0;
?>

<div class="w-full px-2 sm:px-4 md:px-6 lg:px-8 py-4 overflow-x-hidden">
    <!-- Grid Statistik -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-green-500 text-white rounded-lg p-4 flex justify-between items-center">
            <div>Total Akun</div>
            <div class="text-xl font-bold">0</div>
        </div>
        <div class="bg-blue-600 text-white rounded-lg p-4 flex justify-between items-center">
            <div>VMess</div>
            <div class="text-xl font-bold">0</div>
        </div>
        <div class="bg-purple-500 text-white rounded-lg p-4 flex justify-between items-center">
            <div>VLess</div>
            <div class="text-xl font-bold">0</div>
        </div>
        <div class="bg-red-600 text-white rounded-lg p-4 flex justify-between items-center">
            <div>Trojan</div>
            <div class="text-xl font-bold">0</div>
        </div>
        <div class="bg-yellow-600 text-white rounded-lg p-4 flex justify-between items-center">
            <div>Shadowsocks</div>
            <div class="text-xl font-bold">0</div>
        </div>
    </div>

    <!-- Chart -->
    <div class="bg-gray-800 rounded-lg p-4 mb-6 overflow-x-auto">
        <canvas id="accountChart" class="w-full max-w-full"></canvas>
    </div>

    <!-- Tabel -->
    <div class="bg-gray-800 rounded-lg p-4 overflow-x-auto">
        <table class="min-w-full text-sm text-white">
            <thead>
                <tr class="bg-gray-700 text-left">
                    <th class="px-3 py-2">No</th>
                    <th class="px-3 py-2">Username</th>
                    <th class="px-3 py-2">Protocol</th>
                    <th class="px-3 py-2">Expired</th>
                    <th class="px-3 py-2">UUID / Password</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2">Action</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-t border-gray-700">
                    <td class="px-3 py-2 text-center" colspan="7">Belum ada akun.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js (panggil setelah ini atau sudah ada sebelumnya) -->
<script>
    const ctx = document.getElementById('accountChart').getContext('2d');
    const accountChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['VMess', 'VLess', 'Trojan', 'Shadowsocks'],
            datasets: [{
                label: 'Jumlah Akun',
                data: [0, 0, 0, 0],
                backgroundColor: [
                    '#2563eb',
                    '#8b5cf6',
                    '#dc2626',
                    '#d97706'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>

