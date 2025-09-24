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

// form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
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
        <h1>Product Management Dashboard</h1>

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

        <!-- product form -->
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
                            <td>
                                <form method="post" action="" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_product">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" onclick="return confirm('Are you sure you want to delete this product?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- klant -->
        <!--<div class="klant-data">
            <h2>Klant Data (Top 5 Rows)</h2>
            <pre>
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
            //       move this code later into account reception      //
            ############################################################
            ?>
            </pre>
        </div>
    </div>
</body>
</html>