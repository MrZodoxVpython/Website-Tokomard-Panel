<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

require '../koneksi.php';

$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role     = $_POST['role'];
    $saldo    = intval($_POST['saldo']);

    if ($username === '' || $email === '' || $password === '' || $saldo < 0) {
        $pesan = "❌ Semua data wajib diisi dan saldo tidak boleh negatif.";
    } else {
        // Cek username sudah ada
        $cek = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $cek->bind_param("s", $username);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            $pesan = "❌ Username sudah digunakan.";
        } else {
            $cek->close();

            // Enkripsi password
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // Tambah user
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, saldo) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $username, $email, $hashed, $role, $saldo);
            if ($stmt->execute()) {
                header("Location: list-akun.php");
                exit;
            } else {
                $pesan = "❌ Gagal menambahkan user.";
            }
            $stmt->close();
        }
    }
}
?>

<?php include '../templates/header.php'; ?>
<div class="max-w-xl mx-auto mt-10 bg-gray-800 p-6 rounded-lg shadow">
    <h2 class="text-xl font-semibold text-white mb-4">Tambah User Baru</h2>
    <?php if ($pesan): ?>
        <div class="bg-red-500 text-white text-sm p-2 mb-4 rounded"><?= htmlspecialchars($pesan) ?></div>
    <?php endif; ?>
    <form method="POST">
        <label class="block mb-2 text-gray-300">Username</label>
        <input name="username" class="w-full mb-4 p-2 rounded bg-gray-700 text-white" required>

        <label class="block mb-2 text-gray-300">Email</label>
        <input type="email" name="email" class="w-full mb-4 p-2 rounded bg-gray-700 text-white" required>

        <label class="block mb-2 text-gray-300">Password</label>
        <input type="password" name="password" class="w-full mb-4 p-2 rounded bg-gray-700 text-white" required>

        <label class="block mb-2 text-gray-300">Role</label>
        <select name="role" class="w-full mb-4 p-2 rounded bg-gray-700 text-white">
            <option value="reseller">Reseller</option>
            <option value="admin">Admin</option>
        </select>

        <label class="block mb-2 text-gray-300">Saldo Awal (Rp)</label>
        <input type="number" name="saldo" value="0" class="w-full mb-4 p-2 rounded bg-gray-700 text-white" min="0">

        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Tambah</button>
        <a href="list-user.php" class="ml-4 text-gray-400 hover:text-white">Kembali</a>
    </form>
</div>
<?php include '../templates/footer.php'; ?>

