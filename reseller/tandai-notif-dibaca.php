<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['username'])) exit;
$username = $_SESSION['username'];
$conn->query("UPDATE notifikasi_reseller SET sudah_dibaca=1 WHERE username IS NULL OR username = '$username'");
header("Location: reseller.php");

