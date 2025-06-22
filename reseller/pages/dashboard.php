<?php
$accountStats = [
    'ssh' => 5,
    'trojan' => 1,
    'vmess' => 1,
    'vless' => 1,
    'shadowsocks' => 2,
    'orders' => 12
];
?>

<div class="space-y-1">
    <h2 class="text-xl font-semibold">Statistik Akun</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($accountStats as $key => $val): ?>
            <div class="bg-gray-100 dark:bg-gray-800 p-4 rounded-lg shadow-md">
                <h3 class="text-lg font-bold capitalize">Total <?= ucfirst($key) ?> Account</h3>
                <p class="text-2xl mt-2 font-semibold"><?= $val ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-md">
        <h3 class="text-lg font-bold mb-4">Grafik Order Per Bulan</h3>
        <canvas id="orderChart" height="120"></canvas>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            new Chart(document.getElementById('orderChart'), {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Order',
                        data: [2, 3, 4, 2, 5, 6],
                        backgroundColor: 'rgba(79, 70, 229, 0.7)'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        </script>
    </div>

    <div class="bg-yellow-100 dark:bg-yellow-800 p-4 rounded-lg">
        <h4 class="font-semibold">📢 Notification Admin</h4>
        <p class="text-sm mt-2">Silakan hubungi admin untuk update layanan atau kendala teknis lainnya.</p>
    </div>
</div>

