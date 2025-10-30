<?php
/*
 * Admin interface om gebruikers en hun rollen te beheren.
 *
 * de file laat alle accounts zien in een tabel met hun rollen
 * alleen admins kunnen hier iets doen en überhaupt komen
 */
session_start();
require_once '4everToolsDB.php';

// kijken of de user admin is ingelogd, als geen admin dan terug naar index
if (empty($_SESSION['user_id']) || empty($_SESSION['admin'])) {
    header('Location: index.php');
    exit();
}

// laat alle gebruikers zien en handel rol wissel acties af
// dit stuk verandert medewerker status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_medewerker') {
        $uid = (int)$_POST['user_id'];
        $new = isset($_POST['set']) && $_POST['set'] == '1' ? 1 : 0;
        // We gebruiken try/catch omdat database-operaties fouten kunnen geven.
        // PDO is ingesteld op ERRMODE_EXCEPTION, dus bij een fout wordt
        // er een PDOException gegooid. Hier vangen we die op zodat we de
        // gebruiker een nette boodschap kunnen geven en niet de raw
        // database-fout op het scherm tonen.
        try {
            $stmt = $pdo->prepare("UPDATE klant SET medewerker = ? WHERE id = ?");
            $stmt->execute([$new, $uid]);
            $message = 'User updated.';
        } catch (PDOException $e) {
            // technische fout loggen (hier eenvoudig via session); in
            // productie zou je deze fout naar een logfile sturen.
            $error = 'Error updating: ' . $e->getMessage();
        }
    }
}

// fetch gebruikers
try {
    $stmt = $pdo->query("SELECT id, voornaam, achternaam, admin, medewerker FROM klant ORDER BY id DESC");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    // Als het laden van gebruikers faalt, vangen we de PDOException.
    // Hierdoor blijft de pagina werken (we tonen een foutbericht) en
    // kunnen we beslissen wat we loggen of laten zien aan de admin.
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
    <header class="nav-header">
        <div class="nav-content">
            <a href="dashboard.php" class="nav-title">Forever Tools</a>
            <div class="nav-links">
                <?php if (!empty($_SESSION['admin'])): ?>
                    <a href="showAccounts.php">Manage Accounts</a>
                    <a href="createFactory.php">Manage Locations</a>
                <?php endif; ?>
                <?php if (!empty($_SESSION['admin']) || !empty($_SESSION['medewerker'])): ?>
                    <a href="showOrders.php">Manage Orders</a>
                <?php endif; ?>
                <a href="logout.php">Log Out</a>
            </div>
        </div>
    </header>
    
    <div class="container">
        <h2>Manage Accounts</h2>
        <?php if (!empty($error)): ?><div class="message error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if (!empty($message)): ?><div class="message success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

        <table class="product-table">
            <thead><tr><th>ID</th><th>Name</th><th>Admin</th><th>Employee</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?php echo $u['id']; ?></td>
                    <td><?php echo htmlspecialchars($u['voornaam'] . ' ' . $u['achternaam']); ?></td>
                    <td><?php echo $u['admin'] ? 'Yes' : '-'; ?></td>
                    <td><?php echo $u['medewerker'] ? 'Yes' : '-'; ?></td>
                    <td>
                        <?php if (!$u['admin']): // laat geen admin status verandering toe ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="toggle_medewerker">
                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                            <?php if ($u['medewerker']): ?>
                                <input type="hidden" name="set" value="0">
                                <button class="small-button" type="submit">Remove as Employee</button>
                            <?php else: ?>
                                <input type="hidden" name="set" value="1">
                                <button class="small-button" type="submit">Turn Employee</button>
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
