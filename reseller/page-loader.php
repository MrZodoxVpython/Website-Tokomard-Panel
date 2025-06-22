<?php
$page = $_GET['page'] ?? 'dashboard';
$allowed = ['dashboard', 'ssh', 'vmess', 'vless', 'trojan', 'shadowsocks', 'topup', 'cek-server', 'grup-vip'];
if (in_array($page, $allowed)) {
    include __DIR__ . "/pages/$page.php";
} else {
    echo "<p class='text-red-500'>Halaman tidak ditemukan.</p>";
}
?>

