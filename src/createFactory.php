<?php
session_start();
require_once '4everToolsDB.php';

// only admins can manage factories
if (empty($_SESSION['user_id']) || empty($_SESSION['admin'])) {
    header('Location: index.php');
    exit();
}

// handle create/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'create_factory') {
                $name = trim($_POST['name']);
                $country = trim($_POST['country']);
                if ($name === '') {
                    $error = 'Factory name required';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO factories (name, country) VALUES (?, ?)");
                    $stmt->execute([$name, $country ?: null]);
                    $success = 'Factory created';
                }
            } elseif ($_POST['action'] === 'delete_factory') {
                $id = (int)$_POST['factory_id'];
                // Optionally check for dependent products before delete
                $p = $pdo->prepare("SELECT COUNT(*) as cnt FROM product WHERE fabriekherkomst = (SELECT name FROM factories WHERE id = ?)");
                $p->execute([$id]);
                $c = $p->fetch();
                if ($c && $c['cnt'] > 0) {
                    $error = 'Factory has products; cannot delete. Reassign or remove products first.';
                } else {
                    $d = $pdo->prepare("DELETE FROM factories WHERE id = ?");
                    $d->execute([$id]);
                    $success = 'Factory deleted';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// fetch factories
try {
    $stmt = $pdo->query("SELECT * FROM factories ORDER BY id DESC");
    $factories = $stmt->fetchAll();
} catch (PDOException $e) {
    $factories = [];
    $error = 'Could not load factories';
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Manage Factories</title>
    <link rel="stylesheet" href="background/siteStyling.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <h2>Factories</h2>
        <?php if (!empty($error)): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="message success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <h3>Create Factory</h3>
        <form method="post" action="">
            <input type="hidden" name="action" value="create_factory">
            <div>
                <label for="name">Name</label>
                <input class="input" type="text" id="name" name="name" required>
            </div>
            <div>
                <label for="country">Country (optional)</label>
                <input class="input" type="text" id="country" name="country">
            </div>
            <button type="submit">Create</button>
        </form>

        <h3>Existing Factories</h3>
        <table class="product-table">
            <thead><tr><th>ID</th><th>Name</th><th>Country</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($factories as $f): ?>
                <tr>
                    <td><?php echo htmlspecialchars($f['id']); ?></td>
                    <td><?php echo htmlspecialchars($f['name']); ?></td>
                    <td><?php echo htmlspecialchars($f['country']); ?></td>
                    <td><?php echo htmlspecialchars($f['created_at']); ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="delete_factory">
                            <input type="hidden" name="factory_id" value="<?php echo $f['id']; ?>">
                            <button class="small-button" type="submit" onclick="return confirm('Delete factory?')">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p><a href="dashboard.php">Back to dashboard</a></p>
    </div>
</body>
</html>
