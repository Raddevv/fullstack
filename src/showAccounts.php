<?php
session_start();
require_once '4everToolsDB.php';

if (empty($_SESSION['user_id']) || empty($_SESSION['admin'])) {
    header('Location: index.php');
    exit();
}

// handle role toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_medewerker') {
        $uid = (int)$_POST['user_id'];
        $new = isset($_POST['set']) && $_POST['set'] == '1' ? 1 : 0;
        try {
            $stmt = $pdo->prepare("UPDATE klant SET medewerker = ? WHERE id = ?");
            $stmt->execute([$new, $uid]);
            $message = 'User updated.';
        } catch (PDOException $e) {
            $error = 'Error updating: ' . $e->getMessage();
        }
    }
}

// fetch users
try {
    $stmt = $pdo->query("SELECT id, voornaam, achternaam, admin, medewerker FROM klant ORDER BY id DESC");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $users = [];
    $error = 'Could not load users';
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Show Accounts</title>
    <link rel="stylesheet" href="background/siteStyling.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <h2>Users</h2>
        <?php if (!empty($error)): ?><div class="message error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if (!empty($message)): ?><div class="message success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

        <table class="product-table">
            <thead><tr><th>ID</th><th>Name</th><th>Admin</th><th>Medewerker</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?php echo $u['id']; ?></td>
                    <td><?php echo htmlspecialchars($u['voornaam'] . ' ' . $u['achternaam']); ?></td>
                    <td><?php echo $u['admin'] ? 'Yes' : '-'; ?></td>
                    <td><?php echo $u['medewerker'] ? 'Yes' : '-'; ?></td>
                    <td>
                        <?php if (!$u['admin']): // don't allow changing admin status here ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="toggle_medewerker">
                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                            <?php if ($u['medewerker']): ?>
                                <input type="hidden" name="set" value="0">
                                <button class="small-button" type="submit">Revoke medewerker</button>
                            <?php else: ?>
                                <input type="hidden" name="set" value="1">
                                <button class="small-button" type="submit">Make medewerker</button>
                            <?php endif; ?>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p><a href="dashboard.php">Back to dashboard</a></p>
    </div>
</body>
</html>
