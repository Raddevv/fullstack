<?php
session_start();
require_once '4everToolsDB.php';

// Only admins and medewerkers can access this page
if (empty($_SESSION['user_id']) || (empty($_SESSION['admin']) && empty($_SESSION['medewerker']))) {
    header('Location: index.php');
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'send_order') {
                $order_id = (int)$_POST['order_id'];
                $stmt = $pdo->prepare("UPDATE customer_order SET status = 'sent' WHERE id = ? AND status = 'placed'");
                $stmt->execute([$order_id]);
                if ($stmt->rowCount() > 0) {
                    $message = "Order #$order_id marked as sent.";
                } else {
                    $error = "Order #$order_id not marked as sent(maybe already sent???).";
                }
            } elseif ($_POST['action'] === 'delete_order') {
                $order_id = (int)$_POST['order_id'];
                $d = $pdo->prepare("DELETE FROM customer_order WHERE id = ?");
                $d->execute([$order_id]);
                if ($d->rowCount() > 0) {
                    $message = "Order #$order_id deleted.";
                } else {
                    $error = "Order #$order_id could not be deleted (not found).";
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch orders with customer info
try {
    $stmt = $pdo->query("SELECT co.*, k.voornaam, k.achternaam FROM customer_order co JOIN klant k ON k.id = co.user_id ORDER BY co.id DESC");
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
    $error = 'Could not load orders: ' . $e->getMessage();
}

// helper to fetch items for an order
function fetch_order_items($pdo, $order_id) {
    $items = [];
    try {
        $s = $pdo->prepare("SELECT coi.*, p.type as product_type FROM customer_order_item coi JOIN product p ON p.id = coi.product_id WHERE coi.order_id = ?");
        $s->execute([$order_id]);
        $items = $s->fetchAll();
    } catch (PDOException $e) {
        // ignore; caller will show empty list
    }
    return $items;
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Beheer Orders - Forever Tools</title>
    <link rel="stylesheet" href="background/siteStyling.css?v=<?php echo time(); ?>">
</head>
<body>
    <header class="nav-header">
        <div class="nav-content">
            <a href="dashboard.php" class="nav-title">Forever Tools</a>
            <div class="nav-links">
                <?php if (!empty($_SESSION['admin'])): ?>
                    <a href="showAccounts.php">Accounts beheren</a>
                    <a href="createFactory.php">Fabrieken beheren</a>
                <?php endif; ?>
                <?php if (!empty($_SESSION['admin']) || !empty($_SESSION['medewerker'])): ?>
                    <a href="showOrders.php">Orders beheren</a>
                <?php endif; ?>
                <a href="logout.php">Uitloggen</a>
            </div>
        </div>
    </header>
    
    <div class="container">
        <h2>Orders beheren</h2>

        <?php if (!empty($message)): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <table class="product-table" style="width:100%; margin-top:15px;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Klant</th>
                    <th>Status</th>
                    <th>Gemaakt</th>
                    <th>Items</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="6">Geen orders gevonden.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($o['id']); ?></td>
                            <td><?php echo htmlspecialchars($o['voornaam'] . ' ' . $o['achternaam']); ?></td>
                            <td><?php echo htmlspecialchars($o['status']); ?></td>
                            <td><?php echo htmlspecialchars($o['created_at']); ?></td>
                            <td>
                                <?php $items = fetch_order_items($pdo, $o['id']); ?>
                                <?php if (empty($items)): ?>
                                    <em>Geen items</em>
                                <?php else: ?>
                                    <ul style="margin:0; padding-left:18px;">
                                    <?php foreach ($items as $it): ?>
                                        <li><?php echo htmlspecialchars($it['product_type'] . ' × ' . $it['quantity']); ?></li>
                                    <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex; gap:6px; align-items:center;">
                                    <?php if ($o['status'] === 'placed'): ?>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Weet je zeker dat je order #<?php echo $o['id']; ?> wilt versturen? Als je annuleert blijft de order staan.');">
                                            <input type="hidden" name="action" value="send_order">
                                            <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                            <button class="small-button" type="submit">Verstuur</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color:#666; font-size:0.95em;">Al verstuurd</span>
                                    <?php endif; ?>

                                    <form method="post" style="display:inline;" onsubmit="return confirm('Weet je zeker dat je order #<?php echo $o['id']; ?> wilt verwijderen? Deze actie kan niet ongedaan gemaakt worden.');">
                                        <input type="hidden" name="action" value="delete_order">
                                        <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                        <button class="small-button" type="submit">Verwijder</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

     <p><a href="dashboard.php">Back to dashboard</a></p>
</body>
</html>
