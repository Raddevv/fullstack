<?php
session_start();
require_once '4everToolsDB.php';

#debug
//try {
//    $testQuery = $pdo->query("SELECT 1");
    // echo "db";
//} catch (PDOException $e) {
//    die("Database connection failed: " . $e->getMessage());
//}

// log in check
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Ensure customer order tables exist (safe to run on every request)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS customer_order (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        status VARCHAR(32) NOT NULL DEFAULT 'placed',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS customer_order_item (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        price_at_order DECIMAL(10,2) DEFAULT 0,
        FOREIGN KEY (order_id) REFERENCES customer_order(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (PDOException $e) {
    // non-fatal, surface to user later if needed
    $_SESSION['error'] = "Error ensuring order tables: " . $e->getMessage();
}

// form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_customer_order':
                // regular users can create orders containing multiple products
                $items = $_POST['items'] ?? [];
                if (!is_array($items) || count($items) === 0) {
                    $_SESSION['error'] = 'No items in order';
                    break;
                }
                try {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare("INSERT INTO customer_order (user_id, status) VALUES (?, 'placed')");
                    $stmt->execute([$_SESSION['user_id']]);
                    $order_id = $pdo->lastInsertId();
                    $itemStmt = $pdo->prepare("INSERT INTO customer_order_item (order_id, product_id, quantity, price_at_order) VALUES (?, ?, ?, ?)");
                    $updateStock = $pdo->prepare("UPDATE product SET stock = GREATEST(0, stock - ?) WHERE id = ?");
                    $audit = $pdo->prepare("INSERT INTO stock_audit (product_id, user_id, change_amount, reason) VALUES (?, ?, ?, ?)");
                    foreach ($items as $it) {
                        $pid = (int)$it['product_id'];
                        $qty = max(0, (int)$it['quantity']);
                        if ($qty <= 0) continue;
                        // fetch price for record
                        $pstmt = $pdo->prepare("SELECT prijs FROM product WHERE id = ?");
                        $pstmt->execute([$pid]);
                        $p = $pstmt->fetch();
                        $price = $p ? $p['prijs'] : 0;
                        $itemStmt->execute([$order_id, $pid, $qty, $price]);
                        // decrement stock
                        $updateStock->execute([$qty, $pid]);
                        // audit negative change
                        $audit->execute([$pid, $_SESSION['user_id'] ?? null, -$qty, 'customer_order']);
                    }
                    $pdo->commit();
                    $_SESSION['message'] = 'Customer order placed.';
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $_SESSION['error'] = 'Error creating order: ' . $e->getMessage();
                }
                break;

            case 'cancel_customer_order':
                // only medewerker or admin can cancel and restore stock
                if (empty($_SESSION['admin']) && empty($_SESSION['medewerker'])) {
                    $_SESSION['error'] = "Permission denied";
                    break;
                }
                $order_id = (int)$_POST['order_id'];
                try {
                    // fetch items
                    $stmt = $pdo->prepare("SELECT * FROM customer_order_item WHERE order_id = ?");
                    $stmt->execute([$order_id]);
                    $items = $stmt->fetchAll();
                    $pdo->beginTransaction();
                    foreach ($items as $it) {
                        $qid = (int)$it['quantity'];
                        $pid = (int)$it['product_id'];
                        // restore stock
                        $up = $pdo->prepare("UPDATE product SET stock = stock + ? WHERE id = ?");
                        $up->execute([$qid, $pid]);
                        // audit restore
                        $audit = $pdo->prepare("INSERT INTO stock_audit (product_id, user_id, change_amount, reason) VALUES (?, ?, ?, ?)");
                        $audit->execute([$pid, $_SESSION['user_id'] ?? null, $qid, 'cancel_customer_order']);
                    }
                    // mark order cancelled
                    $u = $pdo->prepare("UPDATE customer_order SET status = 'cancelled' WHERE id = ?");
                    $u->execute([$order_id]);
                    $pdo->commit();
                    $_SESSION['message'] = 'Order cancelled and stock restored.';
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $_SESSION['error'] = 'Error cancelling order: ' . $e->getMessage();
                }
                break;
            case 'add_product':
                // adding a product
                $type = $_POST['type'];
                $fabriekherkomst = $_POST['fabriekherkomst'];
                $prijs = $_POST['prijs'];
                $waardeinkoop = $_POST['waardeinkoop'];
                $waardeverkoop = $_POST['waardeverkoop'];
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO product (type, fabriekherkomst, prijs, waardeinkoop, waardeverkoop, bestelling_id) VALUES (?, ?, ?, ?, ?, 1)");
                    $stmt->execute([$type, $fabriekherkomst, $prijs, $waardeinkoop, $waardeverkoop]);
                    $_SESSION['message'] = "Product successfully added!";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Error adding product: " . $e->getMessage();
                }
                break;

            case 'delete_product':
                // product deletion
                $product_id = $_POST['product_id'];
                try {
                    $stmt = $pdo->prepare("DELETE FROM product WHERE id = ?");
                    $stmt->execute([$product_id]);
                    $_SESSION['message'] = "Product successfully deleted!";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Error deleting product: " . $e->getMessage();
                }
                break;
            
            case 'set_stock':
                // only medewerker or admin can set absolute stock
                if (empty($_SESSION['admin']) && empty($_SESSION['medewerker'])) {
                    $_SESSION['error'] = "Permission denied";
                    break;
                }
                $product_id = (int)$_POST['product_id'];
                $new_stock = (int)$_POST['stock'];
                try {
                    $stmt = $pdo->prepare("UPDATE product SET stock = ? WHERE id = ?");
                    $stmt->execute([$new_stock, $product_id]);
                    // audit
                    $audit = $pdo->prepare("INSERT INTO stock_audit (product_id, user_id, change_amount, reason) VALUES (?, ?, ?, ?)");
                    $audit->execute([$product_id, $_SESSION['user_id'] ?? null, $new_stock, 'set_stock']);
                    $_SESSION['message'] = "Stock updated.";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Error updating stock: " . $e->getMessage();
                }
                break;

            case 'adjust_stock':
                // adjust by delta (can be negative) - only medewerker/admin
                if (empty($_SESSION['admin']) && empty($_SESSION['medewerker'])) {
                    $_SESSION['error'] = "Permission denied";
                    break;
                }
                $product_id = (int)$_POST['product_id'];
                $delta = (int)$_POST['delta'];
                try {
                    // update ensuring stock doesn't go below 0
                    $stmt = $pdo->prepare("UPDATE product SET stock = GREATEST(0, stock + ?) WHERE id = ?");
                    $stmt->execute([$delta, $product_id]);
                    // audit
                    $audit = $pdo->prepare("INSERT INTO stock_audit (product_id, user_id, change_amount, reason) VALUES (?, ?, ?, ?)");
                    $audit->execute([$product_id, $_SESSION['user_id'] ?? null, $delta, 'adjust_stock']);
                    $_SESSION['message'] = "Stock adjusted.";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Error adjusting stock: " . $e->getMessage();
                }
                break;

            case 'place_order':
                // Single-product customer order: allow all users
                $product_id = (int)$_POST['product_id'];
                $amount = max(0, (int)$_POST['amount']);
                if ($amount <= 0) {
                    $_SESSION['error'] = "Invalid order amount";
                    break;
                }
                try {
                    $pdo->beginTransaction();
                    // create customer order record
                    $ostmt = $pdo->prepare("INSERT INTO customer_order (user_id, status) VALUES (?, 'placed')");
                    $ostmt->execute([$_SESSION['user_id']]);
                    $order_id = $pdo->lastInsertId();
                    // record item (capture price)
                    $pstmt = $pdo->prepare("SELECT prijs FROM product WHERE id = ?");
                    $pstmt->execute([$product_id]);
                    $p = $pstmt->fetch();
                    $price = $p ? $p['prijs'] : 0;
                    $itemStmt = $pdo->prepare("INSERT INTO customer_order_item (order_id, product_id, quantity, price_at_order) VALUES (?, ?, ?, ?)");
                    $itemStmt->execute([$order_id, $product_id, $amount, $price]);

                    // decrement stock but not below 0
                    $stmt = $pdo->prepare("UPDATE product SET stock = GREATEST(0, stock - ?) WHERE id = ?");
                    $stmt->execute([$amount, $product_id]);
                    // audit
                    $audit = $pdo->prepare("INSERT INTO stock_audit (product_id, user_id, change_amount, reason) VALUES (?, ?, ?, ?)");
                    $audit->execute([$product_id, $_SESSION['user_id'] ?? null, -$amount, 'place_order']);
                    $pdo->commit();
                    $_SESSION['message'] = "Order placed and stock updated.";
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $_SESSION['error'] = "Error placing order: " . $e->getMessage();
                }
                break;

            case 'create_po':
                if (empty($_SESSION['admin']) && empty($_SESSION['medewerker'])) {
                    $_SESSION['error'] = "Permission denied";
                    break;
                }
                $product_id = (int)$_POST['product_id'];
                $po_amount = max(1, (int)$_POST['po_amount']);
                try {
                    $stmt = $pdo->prepare("INSERT INTO purchase_order (product_id, amount, status, created_by) VALUES (?, ?, 'ordered', ?)");
                    $stmt->execute([$product_id, $po_amount, $_SESSION['user_id'] ?? null]);
                    $_SESSION['message'] = "Purchase order created.";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Error creating purchase order: " . $e->getMessage();
                }
                break;

            case 'receive_po':
                if (empty($_SESSION['admin']) && empty($_SESSION['medewerker'])) {
                    $_SESSION['error'] = "Permission denied";
                    break;
                }
                $po_id = (int)$_POST['po_id'];
                try {
                    // fetch PO
                    $stmt = $pdo->prepare("SELECT * FROM purchase_order WHERE id = ?");
                    $stmt->execute([$po_id]);
                    $po = $stmt->fetch();
                    if (!$po) {
                        $_SESSION['error'] = "PO not found";
                        break;
                    }
                    if ($po['status'] === 'received') {
                        $_SESSION['error'] = "PO already received";
                        break;
                    }
                    // update product stock
                    $stmt = $pdo->prepare("UPDATE product SET stock = stock + ? WHERE id = ?");
                    $stmt->execute([$po['amount'], $po['product_id']]);
                    // mark PO received
                    $stmt = $pdo->prepare("UPDATE purchase_order SET status = 'received', received_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $stmt->execute([$po_id]);
                    // audit
                    $audit = $pdo->prepare("INSERT INTO stock_audit (product_id, user_id, change_amount, reason) VALUES (?, ?, ?, ?)");
                    $audit->execute([$po['product_id'], $_SESSION['user_id'] ?? null, $po['amount'], 'receive_po']);
                    $_SESSION['message'] = "PO received and stock updated.";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Error receiving PO: " . $e->getMessage();
                }
                break;
        }
    }
}

// products
try {
    $stmt = $pdo->query("SELECT * FROM product ORDER BY id DESC");
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching products: " . $e->getMessage();
    $products = [];
}

// Low-stock detection (threshold can be adjusted)
$low_stock_threshold = 5;
$low_stock_items = [];
foreach ($products as $p) {
    $stock = isset($p['stock']) ? (int)$p['stock'] : 0;
    if ($stock < $low_stock_threshold) {
        $low_stock_items[] = $p;
    }
}

// fetch purchase orders
try {
    $stmt = $pdo->query("SELECT po.*, p.type FROM purchase_order po JOIN product p ON p.id = po.product_id ORDER BY po.id DESC");
    $purchase_orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $purchase_orders = [];
}

// fetch customer orders (for listing)
try {
    if (!empty($_SESSION['admin']) || !empty($_SESSION['medewerker'])) {
        // staff see all orders
        $stmt = $pdo->query("SELECT co.*, k.voornaam, k.achternaam FROM customer_order co JOIN klant k ON k.id = co.user_id ORDER BY co.id DESC");
        $customer_orders = $stmt->fetchAll();
    } else {
        // regular users see their own orders
        $stmt = $pdo->prepare("SELECT co.*, k.voornaam, k.achternaam FROM customer_order co JOIN klant k ON k.id = co.user_id WHERE co.user_id = ? ORDER BY co.id DESC");
        $stmt->execute([$_SESSION['user_id']]);
        $customer_orders = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $customer_orders = [];
}

#ignore
//try {
//    $stmt = $pdo->query("SELECT * FROM klant LIMIT 5");
//    $klant_rows = $stmt->fetchAll();
//} catch (Exception $e) {
//    $klant_rows = [];
//    $_SESSION['error'] = "Query failed: " . $e->getMessage();
//}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Dashboard - Forever Tools</title>
    <link rel="stylesheet" href="background/siteStyling.css">
</head>
<body>
    <div class="container">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h1>Product Management Dashboard</h1>
            <div>
                <?php if (!empty($_SESSION['voornaam'])): ?>
                    Logged in as: <?php echo htmlspecialchars($_SESSION['voornaam']); ?>
                    <a href="logout.php" style="margin-left:10px;">Logout</a>
                <?php endif; ?>
            </div>
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

        <!-- low-stock warnings -->
        <?php if (!empty($low_stock_items)): ?>
            <div class="message error">
                <strong>Low stock warning:</strong>
                <ul>
                <?php foreach ($low_stock_items as $li): ?>
                    <li><?php echo htmlspecialchars($li['type']); ?> (ID <?php echo htmlspecialchars($li['id']); ?>) - stock: <?php echo htmlspecialchars($li['stock'] ?? 0); ?></li>
                <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- purchase orders -->
        <div class="purchase-orders" style="margin-bottom:20px;">
            <h2>Purchase Orders</h2>
            <?php if (empty($purchase_orders)): ?>
                <p>No purchase orders.</p>
            <?php else: ?>
                <table class="product-table">
                    <thead>
                        <tr><th>ID</th><th>Product</th><th>Amount</th><th>Status</th><th>Created</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($purchase_orders as $po): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($po['id']); ?></td>
                            <td><?php echo htmlspecialchars($po['type']); ?></td>
                            <td><?php echo htmlspecialchars($po['amount']); ?></td>
                            <td><?php echo htmlspecialchars($po['status']); ?></td>
                            <td><?php echo htmlspecialchars($po['created_at']); ?></td>
                            <td>
                                <?php if (($po['status'] !== 'received') && (!empty($_SESSION['admin']) || !empty($_SESSION['medewerker']))): ?>
                                    <form method="post" action="" style="display:inline;">
                                        <input type="hidden" name="action" value="receive_po">
                                        <input type="hidden" name="po_id" value="<?php echo $po['id']; ?>">
                                        <button type="submit">Receive</button>
                                    </form>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- product form (only for medewerkers/admins) -->
        <?php if (!empty($_SESSION['admin']) || !empty($_SESSION['medewerker'])): ?>
        <div class="product-form">
            <h2>Add New Product</h2>
            <form method="post" action="">
                <input type="hidden" name="action" value="add_product">
                
                <div>
                    <label for="type">Product Type:</label>
                    <input class="input" type="text" id="type" name="type" required>
                </div>

                <div>
                    <label for="fabriekherkomst">Factory Origin:</label>
                    <input class="input" type="text" id="fabriekherkomst" name="fabriekherkomst" required>
                </div>

                <div>
                    <label for="prijs">Price:</label>
                    <input class="input" type="number" id="prijs" name="prijs" step="0.01" required>
                </div>

                <div>
                    <label for="waardeinkoop">Purchase Value:</label>
                    <input class="input" type="number" id="waardeinkoop" name="waardeinkoop" step="0.01" required>
                </div>

                <div>
                    <label for="waardeverkoop">Sale Value:</label>
                    <input class="input" type="number" id="waardeverkoop" name="waardeverkoop" step="0.01" required>
                </div>

                <button type="submit">Add Product</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- product table -->
        <div class="products-list">
            <h2>Current Products</h2>
            <table class="product-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Factory Origin</th>
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
                                    <form method="post" action="" class="compact-row">
                                        <input type="hidden" name="action" value="delete_product">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button class="small-button" type="submit" onclick="return confirm('Are you sure you want to delete this product?')">Delete</button>
                                    </form>

                                    <!-- set absolute stock -->
                                    <form method="post" action="" class="compact-row">
                                        <input type="hidden" name="action" value="set_stock">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input class="small-input" type="number" name="stock" value="<?php echo htmlspecialchars($product['stock'] ?? 0); ?>">
                                        <button class="small-button" type="submit">Set</button>
                                    </form>

                                    <!-- adjust stock by delta -->
                                    <form method="post" action="" class="compact-row">
                                        <input type="hidden" name="action" value="adjust_stock">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input class="small-input" type="number" name="delta" value="0">
                                        <button class="small-button" type="submit">Adjust</button>
                                    </form>

                                    <!-- place customer order (decrease stock) -->
                                    <form method="post" action="" class="compact-row">
                                        <input type="hidden" name="action" value="place_order">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input class="small-input" type="number" name="amount" value="1" min="1">
                                        <button class="small-button" type="submit">Order</button>
                                    </form>

                                    <!-- quick create purchase order -->
                                    <form method="post" action="" class="compact-row">
                                        <input type="hidden" name="action" value="create_po">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input class="small-input" type="number" name="po_amount" value="10" min="1">
                                        <button class="small-button" type="submit">Purchase Order</button>
                                    </form>
                                <?php else: ?>
                                    <!-- regular users: quick order button only (multi-order form below available) -->
                                    <form method="post" action="" class="compact-row">
                                        <input type="hidden" name="action" value="place_order">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input class="small-input" type="number" name="amount" value="1" min="1">
                                        <button class="small-button" type="submit">Order</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- klant -->
        <!--<div class="klant-data">
            <h2>klant data</h2>
            <pre> -->
            <?php
            //try {
            //    $stmt = $pdo->query("SELECT * FROM klant LIMIT 5");
            //    $klant_rows = $stmt->fetchAll();
            //    echo "<pre>";
            //    print_r($klant_rows);
            //    echo "</pre>";
            //} catch (Exception $e) {
            //    echo "Query failed: " . $e->getMessage();
            //}
            ############################################################
            //                  move this code later                  //
            ############################################################
            ?>
            </pre>
        </div>
        
        <!-- multi-product order form for regular users -->
        <?php if (empty($_SESSION['admin']) && empty($_SESSION['medewerker'])): ?>
        <div class="product-form">
            <h2>Create Order (multiple products)</h2>
            <form method="post" action="" id="multi-order-form">
                <input type="hidden" name="action" value="create_customer_order">
                <div id="order-items">
                    <?php foreach ($products as $p): ?>
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                        <label style="width:300px;"><?php echo htmlspecialchars($p['type']); ?> (ID <?php echo $p['id']; ?>) - stock: <?php echo htmlspecialchars($p['stock'] ?? 0); ?></label>
                        <input type="hidden" name="items[<?php echo $p['id']; ?>][product_id]" value="<?php echo $p['id']; ?>">
                        <input class="small-input" type="number" name="items[<?php echo $p['id']; ?>][quantity]" value="0" min="0">
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit">Place Order</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Customer Orders listing -->
        <div class="purchase-orders" style="margin-top:20px;">
            <h2>Customer Orders</h2>
            <?php if (empty($customer_orders)): ?>
                <p>No customer orders.</p>
            <?php else: ?>
                <table class="product-table">
                    <thead>
                        <tr><th>ID</th><th>User</th><th>Status</th><th>Created</th><th>Items</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($customer_orders as $co): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($co['id']); ?></td>
                            <td><?php echo htmlspecialchars($co['voornaam'] . ' ' . $co['achternaam']); ?></td>
                            <td><?php echo htmlspecialchars($co['status']); ?></td>
                            <td><?php echo htmlspecialchars($co['created_at']); ?></td>
                            <td>
                                <?php
                                    // fetch items for this order
                                    $itStmt = $pdo->prepare("SELECT coi.*, p.type FROM customer_order_item coi JOIN product p ON p.id = coi.product_id WHERE coi.order_id = ?");
                                    $itStmt->execute([$co['id']]);
                                    $itms = $itStmt->fetchAll();
                                ?>
                                <ul>
                                <?php foreach ($itms as $it): ?>
                                    <li><?php echo htmlspecialchars($it['type']); ?> x <?php echo htmlspecialchars($it['quantity']); ?></li>
                                <?php endforeach; ?>
                                </ul>
                            </td>
                            <td>
                                <?php if ($co['status'] !== 'cancelled' && (!empty($_SESSION['admin']) || !empty($_SESSION['medewerker']))): ?>
                                    <form method="post" action="" style="display:inline;">
                                        <input type="hidden" name="action" value="cancel_customer_order">
                                        <input type="hidden" name="order_id" value="<?php echo $co['id']; ?>">
                                        <button class="small-button" type="submit" onclick="return confirm('Cancel and restore stock for this order?')">Cancel</button>
                                    </form>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
<script>
// Update button text to include the amount from the nearby input
document.addEventListener('input', function(e) {
    if (!e.target) return;
    var input = e.target;
    var name = input.name;
    if (['stock','delta','amount','po_amount'].includes(name)) {
        // find the sibling button inside the same form
        var form = input.closest('form');
        if (!form) return;
        var btn = form.querySelector('button');
        if (!btn) return;
        var val = input.value || '';
        var label = '';
        switch (name) {
            case 'stock': label = 'Set ' + val; break;
            case 'delta': label = 'Adjust ' + val; break;
            case 'amount': label = 'Order ' + val; break;
            case 'po_amount': label = 'Create PO ' + val; break;
        }
        btn.textContent = label;
    }
});

// initialize button labels on load
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('form.compact-row input').forEach(function(input){
        var event = new Event('input', { bubbles: true });
        input.dispatchEvent(event);
    });
});
</script>
</html>