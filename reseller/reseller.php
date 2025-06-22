<?php
session_start();
$isLoggedIn = isset($_SESSION['user']); // Ganti sesuai sistem login kamu
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Reseller Xray - Tokomard</title>
    <meta name="description" content="Bergabunglah menjadi reseller Xray di Tokomard. Dapatkan harga grosir dan kelola akun sendiri.">
    <meta name="keywords" content="reseller xray, jual akun xray, sewa akun xray, tokomard, grosir xray, vps xray">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="assets/style.css"> <!-- Ganti dengan CSS kamu -->
</head>
<body>
    <header>
        <div class="container">
            <h1>Program Reseller Xray</h1>
            <nav>
                <a href="index.php">Beranda</a>
                <a href="login.php" class="login-btn"><?= $isLoggedIn ? "Dashboard" : "Login" ?></a>
            </nav>
        </div>
    </header>

    <main class="container">
        <section class="intro">
            <h2>Gabung Jadi Reseller Tokomard</h2>
            <p>Dapatkan akses untuk membuat akun Xray sendiri dengan harga lebih murah. Cocok untuk kamu yang ingin berjualan akun V2Ray / VLESS / Trojan / Shadowsocks.</p>
        </section>

        <section class="paket-reseller">
            <h3>Paket Reseller</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Paket</th>
                        <th>Harga / Bulan</th>
                        <th>Kuota Akun</th>
                        <th>Jenis Protokol</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Reseller Basic</td>
                        <td>Rp 50.000</td>
                        <td>20 Akun</td>
                        <td>VMess, VLESS</td>
                    </tr>
                    <tr>
                        <td>Reseller Pro</td>
                        <td>Rp 100.000</td>
                        <td>50 Akun</td>
                        <td>VMess, VLESS, Trojan</td>
                    </tr>
                    <tr>
                        <td>Reseller Unlimited</td>
                        <td>Rp 200.000</td>
                        <td>Tak Terbatas</td>
                        <td>VMess, VLESS, Trojan, Shadowsocks</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="formulir-daftar">
            <h3>Daftar Menjadi Reseller</h3>
            <form method="POST" action="kirim-daftar-reseller.php">
                <label for="nama">Nama Lengkap</label>
                <input type="text" id="nama" name="nama" required>

                <label for="wa">Nomor WhatsApp</label>
                <input type="text" id="wa" name="wa" required>

                <label for="paket">Pilih Paket</label>
                <select id="paket" name="paket" required>
                    <option value="">-- Pilih --</option>
                    <option value="basic">Reseller Basic</option>
                    <option value="pro">Reseller Pro</option>
                    <option value="unlimited">Reseller Unlimited</option>
                </select>

                <label for="catatan">Catatan Tambahan (Opsional)</label>
                <textarea id="catatan" name="catatan"></textarea>

                <button type="submit">Kirim Pendaftaran</button>
            </form>
        </section>
    </main>

    <footer>
        <p>&copy; <?= date('Y') ?> Tokomard Xray Panel. All rights reserved.</p>
    </footer>
</body>
</html>

