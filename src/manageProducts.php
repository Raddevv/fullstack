<?php
/*
 * Product management interface
 * - Filter products by factory
 * - Edit product details
 * - Update prices and information
 */
session_start();
require_once '4everToolsDB.php';

// Only staff can manage products
if (empty($_SESSION['user_id']) || (empty($_SESSION['admin']) && empty($_SESSION['medewerker']))) {
    header('Location: index.php');
    exit();
}

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'update_product':
                    $id = (int)$_POST['product_id'];
                    $type = trim($_POST['type']);
                    $prijs = (float)$_POST['prijs'];
                    $waardeinkoop = (float)$_POST['waardeinkoop'];
                    $waardeverkoop = (float)$_POST['waardeverkoop'];
                    $factory_id = (int)$_POST['factory_id'];
                    
                    $fname = '';
                    if ($factory_id > 0) {
                        $fstmt = $pdo->prepare("SELECT name FROM factories WHERE id = ?");
                        $fstmt->execute([$factory_id]);
                        $frow = $fstmt->fetch();
                        $fname = $frow ? $frow['name'] : '';
                    }

                    $stmt = $pdo->prepare("
                        UPDATE product 
                        SET type = ?, 
                            fabriekherkomst = ?, 
                            prijs = ?, 
                            waardeinkoop = ?, 
                            waardeverkoop = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$type, $fname, $prijs, $waardeinkoop, $waardeverkoop, $id]);
                    $message = "Product updated successfully";
                    break;
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

$selected_factory = isset($_GET['factory']) ? (int)$_GET['factory'] : 0;

try {
    $fstmt = $pdo->query("SELECT DISTINCT id, name FROM factories ORDER BY name ASC");
    $factories = $fstmt->fetchAll();
} catch (PDOException $e) {
    $factories = [];
    $error = "Error loading factories";
}

try {
    if ($selected_factory > 0) {
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.* 
            FROM product p 
            JOIN factories f ON p.fabriekherkomst = f.name 
            WHERE f.id = ?
            ORDER BY p.id DESC
        ");
        $stmt->execute([$selected_factory]);
    } else {
        $stmt = $pdo->query("SELECT DISTINCT * FROM product ORDER BY id DESC");
    }
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
    $error = "Error loading products";
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Manage Products - Forever Tools</title>
    <link rel="stylesheet" href="background/siteStyling.css?v=<?php echo time(); ?>">
    <style>
        .edit-form {
            display: none;
        }
        .edit-form.active {
            display: block;
        }
        .product-row.editing {
            background-color: #f0f0f0;
        }
    </style>
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
        <h2>Manage Products</h2>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Factory filter -->
        <div class="filter-section">
            <h3>Filter by Factory</h3>
            <form method="get" action="">
                <select name="factory" class="input" onchange="this.form.submit()">
                    <option value="0">All Factories</option>
                    <?php foreach ($factories as $f): ?>
                        <option value="<?php echo $f['id']; ?>" <?php echo $selected_factory == $f['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($f['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <!-- Products table -->
        <table class="product-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Factory</th>
                    <th>Price</th>
                    <th>Purchase Value</th>
                    <th>Sale Value</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr class="product-row" id="row-<?php echo $p['id']; ?>">
                        <td><?php echo htmlspecialchars($p['id']); ?></td>
                        <td><?php echo htmlspecialchars($p['type']); ?></td>
                        <td><?php echo htmlspecialchars($p['fabriekherkomst']); ?></td>
                        <td>€<?php echo htmlspecialchars($p['prijs']); ?></td>
                        <td>€<?php echo htmlspecialchars($p['waardeinkoop']); ?></td>
                        <td>€<?php echo htmlspecialchars($p['waardeverkoop']); ?></td>
                        <td><?php echo htmlspecialchars($p['stock'] ?? 0); ?></td>
                        <td>
                            <button onclick="showEditForm(<?php echo $p['id']; ?>)" class="small-button">Edit</button>
                        </td>
                    </tr>
                    <tr id="edit-<?php echo $p['id']; ?>" class="edit-form">
                        <td colspan="8">
                            <form method="post" action="" class="edit-product-form">
                                <input type="hidden" name="action" value="update_product">
                                <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                
                                <div class="form-row">
                                    <label>Type:</label>
                                    <input type="text" name="type" value="<?php echo htmlspecialchars($p['type']); ?>" required class="input">
                                </div>
                                
                                <div class="form-row">
                                    <label>Factory:</label>
                                    <select name="factory_id" class="input">
                                        <option value="0">-- Select Factory --</option>
                                        <?php foreach ($factories as $f): ?>
                                            <option value="<?php echo $f['id']; ?>" <?php echo $f['name'] === $p['fabriekherkomst'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($f['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-row">
                                    <label>Price:</label>
                                    <input type="number" name="prijs" value="<?php echo htmlspecialchars($p['prijs']); ?>" step="0.01" required class="input">
                                </div>
                                
                                <div class="form-row">
                                    <label>Purchase Value:</label>
                                    <input type="number" name="waardeinkoop" value="<?php echo htmlspecialchars($p['waardeinkoop']); ?>" step="0.01" required class="input">
                                </div>
                                
                                <div class="form-row">
                                    <label>Sale Value:</label>
                                    <input type="number" name="waardeverkoop" value="<?php echo htmlspecialchars($p['waardeverkoop']); ?>" step="0.01" required class="input">
                                </div>
                                
                                <div class="button-row">
                                    <button type="submit" class="small-button">Save</button>
                                    <button type="button" onclick="hideEditForm(<?php echo $p['id']; ?>)" class="small-button">Cancel</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>

    <script>
        function showEditForm(id) {
            // Hide any other open forms
            document.querySelectorAll('.edit-form').forEach(form => {
                form.classList.remove('active');
            });
            document.querySelectorAll('.product-row').forEach(row => {
                row.classList.remove('editing');
            });
            
            // Show the selected form
            document.getElementById('edit-' + id).classList.add('active');
            document.getElementById('row-' + id).classList.add('editing');
        }

        function hideEditForm(id) {
            document.getElementById('edit-' + id).classList.remove('active');
            document.getElementById('row-' + id).classList.remove('editing');
        }
    </script>
</body>
</html>