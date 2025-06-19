<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

require '/koneksi.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("❌ ID tidak valid.");
}

$id = (int)$_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];

    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
    $stmt->bind_param("sssi", $username, $email, $role, $id);
    $stmt->execute();

    header("Location: list-akun.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("❌ User tidak ditemukan.");
}
?>

<?php include '../templates/header.php'; ?>
<div class="max-w-xl mx-auto mt-10 bg-gray-800 p-6 rounded-lg shadow">
  <h2 class="text-xl font-semibold text-white mb-4">Edit User</h2>
  <form method="POST">
    <label class="block mb-2 text-gray-300">Username</label>
    <input name="username" value="<?= htmlspecialchars($user['username']) ?>" class="w-full mb-4 p-2 rounded bg-gray-700 text-white" required>

    <label class="block mb-2 text-gray-300">Email</label>
    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="w-full mb-4 p-2 rounded bg-gray-700 text-white" required>

    <label class="block mb-2 text-gray-300">Role</label>
    <select name="role" class="w-full mb-4 p-2 rounded bg-gray-700 text-white">
      <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
      <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
    </select>

    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Simpan</button>
    <a href="list-akun.php" class="ml-4 text-gray-400 hover:text-white">Kembali</a>
  </form>
</div>
<?php include '/templates/footer.php'; ?>

