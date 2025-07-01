<div class="w-full px-2 sm:px-4 md:px-6 lg:px-8 py-4 overflow-x-hidden">
    <!-- Statistik Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4 mb-6">
        <div class="bg-green-500 text-white rounded-lg p-4 flex justify-between items-center">
            <span>Total Akun</span>
            <span class="text-xl font-bold">0</span>
        </div>
        <div class="bg-blue-600 text-white rounded-lg p-4 flex justify-between items-center">
            <span>VMess</span>
            <span class="text-xl font-bold">0</span>
        </div>
        <div class="bg-purple-500 text-white rounded-lg p-4 flex justify-between items-center">
            <span>VLess</span>
            <span class="text-xl font-bold">0</span>
        </div>
        <div class="bg-red-600 text-white rounded-lg p-4 flex justify-between items-center">
            <span>Trojan</span>
            <span class="text-xl font-bold">0</span>
        </div>
        <div class="bg-yellow-600 text-white rounded-lg p-4 flex justify-between items-center">
            <span>Shadowsocks</span>
            <span class="text-xl font-bold">0</span>
        </div>
    </div>

    <!-- Chart Section -->
    <div class="bg-gray-800 rounded-lg p-4 mb-6">
        <div class="w-full overflow-x-auto">
            <canvas id="accountChart" class="!w-[100%] max-w-[100%] h-[300px]"></canvas>
        </div>
    </div>

    <!-- Tabel Section -->
    <div class="bg-gray-800 rounded-lg p-4 overflow-x-auto">
        <table class="w-full text-sm text-white min-w-[600px]">
            <thead>
                <tr class="bg-gray-700 text-left">
                    <th class="px-3 py-2">NO</th>
                    <th class="px-3 py-2">USERNAME</th>
                    <th class="px-3 py-2">PROTOCOL</th>
                    <th class="px-3 py-2">EXPIRED</th>
                    <th class="px-3 py-2">UUID / PASSWORD</th>
                    <th class="px-3 py-2">STATUS</th>
                    <th class="px-3 py-2">ACTION</th>
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

<!-- Chart.js Script -->
<script>
    const ctx = document.getElementById('accountChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['VMess', 'VLess', 'Trojan', 'Shadowsocks'],
            datasets: [{
                label: 'Jumlah Akun',
                data: [0, 0, 0, 0],
                backgroundColor: ['#3b82f6', '#a855f7', '#ef4444', '#f59e0b']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
</script>

