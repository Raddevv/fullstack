<?php
/*
 * Admin UI voor het beheren van fabriek records (CRUD).
 * - Alleen toegankelijk voor admins
 * - Voegt factories toe met naam en land
 */
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
        // We vangen PDOExceptions hier zodat fouten tijdens create/delete
        // niet de hele pagina laten crashen. PDO gooit PDOException
        // bij databaseproblemen en we willen die netjes afhandelen.
        try {
            if ($_POST['action'] === 'create_factory') {
                $name = trim($_POST['name']);
                $country = trim($_POST['country']);
                // Normalize empty country to a known value so duplicates are easier to detect
                $country_norm = $country !== '' ? $country : 'Unknown';
                if ($name === '') {
                    $error = 'Factory name required';
                } else {
                    $check = $pdo->prepare("SELECT COUNT(*) as cnt FROM factories WHERE LOWER(name) = LOWER(?) AND LOWER(COALESCE(country,'')) = LOWER(?)");
                    $check->execute([$name, $country_norm]);
                    $exists = $check->fetch();
                    if ($exists && $exists['cnt'] > 0) {
                        $error = 'Factory already exists with the same name and country.';
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO factories (name, country) VALUES (?, ?)");
                        $stmt->execute([$name, $country_norm]);
                        $success = 'Factory created';
                    }
                }
            } elseif ($_POST['action'] === 'delete_factory') {
                $id = (int)$_POST['factory_id'];
                $stmt = $pdo->prepare("DELETE FROM factories WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'Factory deleted';
            }
        } catch (PDOException $e) {
            // Log technisch detail (bijv. error_log) en geef gebruiker
            // een simpele foutmelding terug.
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// fetch all created factories
// Use DISTINCT to ensure the overview doesn't accidentally surface duplicate rows.
try {
    $stmt = $pdo->query("SELECT DISTINCT id, name, country, created_at FROM factories ORDER BY id DESC");
    $factories = $stmt->fetchAll();
} catch (PDOException $e) {
    // Fout bij ophalen van factories: voorkom crash en toon nette melding
    $factories = [];
    $error = 'Error fetching factories: ' . $e->getMessage();
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
    <header class="nav-header">
        <div class="nav-content">
            <a href="dashboard.php" class="nav-title">Forever Tools</a>
            <div class="nav-links">
                <?php if (!empty($_SESSION['admin'])): ?>
                    <a href="showAccounts.php">Manage Accounts</a>
                    <a href="createFactory.php">Manage Factories</a>
                <?php endif; ?>
                <?php if (!empty($_SESSION['admin']) || !empty($_SESSION['medewerker'])): ?>
                    <a href="showOrders.php">Manage Orders</a>
                <?php endif; ?>
                <a href="logout.php">Log Out</a>
            </div>
        </div>
    </header>
    
    <div class="container">
        <h2>Manage Locations</h2>
        <?php if (!empty($error)): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="message success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <h3>Create Locations</h3>
        <form method="post" action="">
            <input type="hidden" name="action" value="create_factory">
            <div>
                <label for="name">Name</label>
                <input class="input" type="text" id="name" name="name" required>
            </div>
            <div>
                <label for="country">Country(optional)</label>
                <input class="input" type="text" id="country" name="country">
            </div>
            <button type="submit">Create</button>
        </form>

        <h3>Existing Locations</h3>
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
