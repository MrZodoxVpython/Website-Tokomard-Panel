<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit;
}

$reseller = $_SESSION['username'];
$remoteIP = "152.42.182.187"; // IP SGDO-MARD
$sshUser = "root";
$remoteCmd = "cat /etc/xray/data-panel/reseller/akun-{$reseller}-*.txt | grep -i TROJAN";

$sshCommand = "ssh -o StrictHostKeyChecking=no $sshUser@$remoteIP \"$remoteCmd\"";
$output = shell_exec($sshCommand);
?>

<h2 class="text-xl font-bold mb-4">📡 Daftar Akun Trojan - SGDO-MARD</h2>
<pre class="bg-gray-800 text-white p-4 rounded-lg whitespace-pre-wrap"><?= htmlspecialchars($output ?: "Tidak ada akun ditemukan.") ?></pre>

