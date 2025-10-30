<?php
/*
 * Dashboard met productoverzicht en voorraadwaarde.
 * Toont unieke producten met voorraad en prijzen.
 * - Voor directie: totale voorraadwaarde
 * - Voor medewerkers: voorraad bijwerken
 * - Voor klanten: producten bestellen
 */
session_start();
require_once '4everToolsDB.php';

// check login status
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Get selected factory filter
$selected_factory = isset($_GET['factory']) ? (int)$_GET['factory'] : 0;

// fetch unique products with their details
try {
    if ($selected_factory > 0) {
        $stmt = $pdo->prepare("
            SELECT DISTINCT 
                p.id,
                p.type,
                p.fabriekherkomst,
                p.prijs,
                p.waardeinkoop,
                p.waardeverkoop,
                p.stock,
                p.bestelling_id
            FROM product p
            JOIN factories f ON p.fabriekherkomst = f.name
            WHERE f.id = ?
            ORDER BY p.id DESC
        ");
        $stmt->execute([$selected_factory]);
    } else {
        $stmt = $pdo->query("
            SELECT DISTINCT 
                p.id,
                p.type,
                p.fabriekherkomst,
                p.prijs,
                p.waardeinkoop,
                p.waardeverkoop,
                p.stock,
                p.bestelling_id
            FROM product p
            ORDER BY p.id DESC
        ");
    }
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching products: " . $e->getMessage();
    $products = [];
}

// Calculate total inventory value for management insight
$total_inventory_value = 0.0;
foreach ($products as $p) {
    $stock = isset($p['stock']) ? (int)$p['stock'] : 0;
    $purchaseValue = isset($p['waardeinkoop']) ? (float)$p['waardeinkoop'] : 0.0;
    $total_inventory_value += ($stock * $purchaseValue);
}

// Check for low stock items
$low_stock_threshold = 5;
$low_stock_items = [];
foreach ($products as $p) {
    $stock = isset($p['stock']) ? (int)$p['stock'] : 0;
    if ($stock < $low_stock_threshold) {
        $low_stock_items[] = $p;
    }
}

try {
    $fstmt = $pdo->query("SELECT DISTINCT * FROM factories ORDER BY name ASC");
    $factories = $fstmt->fetchAll();
} catch (PDOException $e) {
    $factories = [];
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Dashboard - Forever Tools</title>
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
        <h1>Dashboard</h1>

            <!-- Factory filter -->
            <div class="filter-section" style="margin-bottom:20px;">
                <form method="get" action="" class="factory-filter">
                    <label for="factory">Filter by Factory:</label>
                    <select name="factory" id="factory" class="input" onchange="this.form.submit()">
                        <option value="0">All Factories</option>
                        <?php foreach ($factories as $f): ?>
                            <option value="<?php echo $f['id']; ?>" <?php echo $selected_factory == $f['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($f['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <div style="margin-bottom:20px;">
            <strong>Total stock value:</strong>
            <span>€<?php echo number_format($total_inventory_value, 2, ',', '.'); ?></span>
            <small style="color:#666; display:block;">(Calculated on basis of stock value & stock amount)</small>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message success">
                <?php 
                    echo $_SESSION['message']; 
                    unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="message error">
                <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($low_stock_items)): ?>
            <div class="message warning">
                <strong>Stock warning:</strong>
                <ul>
                <?php foreach ($low_stock_items as $li): ?>
                    <li><?php echo htmlspecialchars($li['type']); ?> (ID <?php echo htmlspecialchars($li['id']); ?>) - stock: <?php echo htmlspecialchars($li['stock'] ?? 0); ?></li>
                <!-- ?? in dit geval, stock aantal, anders 0 -->
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="products-list">
            <h2>Producten Overzicht</h2>
            <table class="product-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Factory</th>
                        <th>Price</th>
                        <th>Purchase Value</th>
                        <th>Sale Value</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['id']); ?></td>
                            <td><?php echo htmlspecialchars($product['type']); ?></td>
                            <td><?php echo htmlspecialchars($product['fabriekherkomst']); ?></td>
                            <td>€<?php echo htmlspecialchars($product['prijs']); ?></td>
                            <td>€<?php echo htmlspecialchars($product['waardeinkoop']); ?></td>
                            <td>€<?php echo htmlspecialchars($product['waardeverkoop']); ?></td>
                            <td><?php echo htmlspecialchars($product['stock'] ?? 0); ?></td>
                            <td>
                                <?php if (!empty($_SESSION['admin']) || !empty($_SESSION['medewerker'])): ?>
                                    <!-- Staff actions -->
                                    <div class="action-buttons">
                                        <a href="orderInfo.php?action=edit&id=<?php echo $product['id']; ?>" class="button">Edit</a>
                                        <a href="orderInfo.php?action=stock&id=<?php echo $product['id']; ?>" class="button">Stock</a>
                                    </div>
                                <?php else: ?>
                                    <!-- Customer actions -->
                                    <form action="orderInfo.php" method="post">
                                        <input type="hidden" name="action" value="add_to_cart">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="number" name="quantity" value="1" min="1" class="small-input">
                                        <button type="submit" class="button">Order</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($_SESSION['admin']) || !empty($_SESSION['medewerker'])): ?>
        <div class="management-links">
            <h3>Management Links</h3>
            <ul>
                <li><a href="orderInfo.php">Manage Stock</a></li>
                <li><a href="createFactory.php">Manage Factories</a></li>
                <li><a href="showOrders.php">Order List</a></li>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
