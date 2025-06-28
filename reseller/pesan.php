<?php
require '../koneksi.php'; // atau sesuaikan path-nya
$result = $conn->query("SELECT * FROM notifikasi_admin ORDER BY waktu DESC");

echo "<h2 class='text-lg font-bold mt-5 mb-2'>📢 Notifikasi Admin</h2>";

if ($result->num_rows > 0) {
    echo "<ul class='list-disc ml-5 text-sm'>";
    while ($row = $result->fetch_assoc()) {
        echo "<li class='mb-1'>" . htmlspecialchars($row['pesan']) . " <span class='text-xs text-gray-500'>(" . $row['waktu'] . ")</span></li>";
    }
    echo "</ul>";
} else {
    echo "<p class='text-gray-500'>Belum ada notifikasi.</p>";
}
?>

