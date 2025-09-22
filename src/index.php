<?php
session_start();

// 4everToolsDB require
require_once '4everToolsDB.php';

try {
    $stmt = $pdo->query("SELECT * FROM klant LIMIT 5");
    $rows = $stmt->fetchAll();
    echo "<pre>";
    print_r($rows);
    echo "</pre>";
} catch (Exception $e) {
    echo "Query failed: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
<link rel="stylesheet" href="background/siteStyling.css?v=<?php echo time(); ?>">
</head>
<body>
    
</body>
</html>