<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied");
}

$userFile = '../../data/reseller_users.json';
$users = file_exists($userFile) ? json_decode(file_get_contents($userFile), true) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    foreach ($users as &$user) {
        if ($user['username'] === $username) {
            $user['status'] = 'approved';
            break;
        }
    }
    unset($user);
    file_put_contents($userFile, json_encode($users, JSON_PRETTY_PRINT));
    header("Location: approve_reseller.php");
    exit;
}
?>
<h2>Permintaan Join Group</h2>
<ul>
<?php foreach ($users as $user): ?>
    <?php if ($user['status'] === 'pending'): ?>
        <li>
            <?= htmlspecialchars($user['username']) ?>
            <form method="post" style="display:inline">
                <input type="hidden" name="username" value="<?= htmlspecialchars($user['username']) ?>">
                <button type="submit">Setujui</button>
            </form>
        </li>
    <?php endif; ?>
<?php endforeach; ?>
</ul>

