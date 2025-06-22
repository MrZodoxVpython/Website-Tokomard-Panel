<?php
$page = $_GET['page'] ?? 'dashboard';

$allowedPages = ['dashboard', 'ssh', 'vmess', 'vless', 'trojan', 'shadowsocks', 'topup', 'cek-server', 'grup-vip'];

if (in_array($page, $allowedPages)) {
    $pageFile = __DIR__ . "/pages/{$page}.php";
    if (file_exists($pageFile)) {
        include $pageFile;
    } else {
        echo "<p class='text-red-600'>File halaman <strong>{$page}.php</strong> tidak ditemukan di folder <code>pages/</code>.</p>";
    }
} else {
    echo "<p class='text-red-600'>Halaman tidak valid atau tidak diizinkan.</p>";
}

