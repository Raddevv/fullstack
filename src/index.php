<?php
session_start();

// if user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// 4everToolsDB require
require_once '4everToolsDB.php';

// Simple migration runner: applies .sql files from src/background/migrations
function run_migrations(PDO $pdo)
{
    $migrationsDir = __DIR__ . DIRECTORY_SEPARATOR . 'background' . DIRECTORY_SEPARATOR . 'migrations';
    if (!is_dir($migrationsDir)) return;

    // ensure migrations table
    $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $applied = [];
    $stmt = $pdo->query("SELECT name FROM migrations");
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $r) {
        $applied[$r] = true;
    }

    $files = glob($migrationsDir . DIRECTORY_SEPARATOR . '*.sql');
    sort($files, SORT_STRING);
    foreach ($files as $file) {
        $name = basename($file);
        if (!empty($applied[$name])) continue;
        $sql = file_get_contents($file);
        if ($sql === false) continue;
        // split statements on semicolon followed by newline
        $parts = preg_split('/;\s*\n/', $sql);
        try {
            $pdo->beginTransaction();
            foreach ($parts as $part) {
                $s = trim($part);
                if ($s === '') continue;
                $pdo->exec($s);
            }
            $ins = $pdo->prepare("INSERT INTO migrations (name) VALUES (?)");
            $ins->execute([$name]);
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            // surface error to session so it's visible on load
            $_SESSION['error'] = 'Migration failed (' . $name . '): ' . $e->getMessage();
            break;
        }
    }
}

run_migrations($pdo);

// add admin and password column if not exists
try {
    $pdo->exec("ALTER TABLE klant ADD COLUMN IF NOT EXISTS admin TINYINT(1) NOT NULL DEFAULT 0");
    $pdo->exec("ALTER TABLE klant ADD COLUMN IF NOT EXISTS medewerker TINYINT(1) NOT NULL DEFAULT 0");
    $pdo->exec("ALTER TABLE klant ADD COLUMN IF NOT EXISTS wachtwoord VARCHAR(255)");
    
    // set default pass for accounts made before password
    $default_password = password_hash('Password123', PASSWORD_DEFAULT);
    $pdo->exec("UPDATE klant SET wachtwoord = '$default_password' WHERE wachtwoord IS NULL");
    
    // insert/update admin
    $default_admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO klant (voornaam, achternaam, wachtwoord, admin) 
                          SELECT 'Jordy', 'Meijer', ?, 1 
                          WHERE NOT EXISTS (
                              SELECT 1 FROM klant 
                              WHERE voornaam = 'Jordy' AND achternaam = 'Meijer'
                          ) LIMIT 1");
    $stmt->execute([$default_admin_password]);
    
    $stmt = $pdo->prepare("UPDATE klant SET admin = 1 
                          WHERE voornaam = 'Jordy' AND achternaam = 'Meijer'");
    $stmt->execute();

    // ensure products have a stock column
    $pdo->exec("ALTER TABLE product ADD COLUMN IF NOT EXISTS stock INT NOT NULL DEFAULT 0");

    // create audit and purchase order tables if they don't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS stock_audit (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        user_id INT NULL,
        change_amount INT NOT NULL,
        reason VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS purchase_order (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        amount INT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'ordered',
        created_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        received_at TIMESTAMP NULL
    ) ENGINE=InnoDB");
    // factories table
    $pdo->exec("CREATE TABLE IF NOT EXISTS factories (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        country VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (PDOException $e) { // <-- exception 
    // if column exists, silence error
}

// form login handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voornaam = $_POST['voornaam'];
    $achternaam = $_POST['achternaam'];
    $wachtwoord = $_POST['wachtwoord'];
    
    try {
        // credential check
        $stmt = $pdo->prepare("SELECT id, voornaam, achternaam, wachtwoord, admin FROM klant WHERE voornaam = ? AND achternaam = ?");
        $stmt->execute([$voornaam, $achternaam]);
        $user = $stmt->fetch();

        // verify existence
        if ($user && password_verify($wachtwoord, $user['wachtwoord'])) {
            // set session vars
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['voornaam'] = $user['voornaam'];
            $_SESSION['admin'] = $user['admin'];
            
            // dashboard redirect
            header('Location: dashboard.php');
            exit();
        } else {
            $error_message = "Invalid voornaam or achternaam";
        }
    } catch (PDOException $e) {
        $error_message = "Login error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tools Forever</title>
    <link rel="stylesheet" href="background/siteStyling.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="login-container">
        <h2>Login to Tools Forever</h2>
        
        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label for="voornaam">Voornaam:</label>
                <input type="text" id="voornaam" name="voornaam" required>
            </div>
            
            <div class="form-group">
                <label for="achternaam">Achternaam:</label>
                <input type="text" id="achternaam" name="achternaam" required>
            </div>

            <div class="form-group">
                <label for="wachtwoord">Wachtwoord:</label>
                <input type="password" id="wachtwoord" name="wachtwoord" required>
            </div>
            
            <button type="submit">Login</button>
        </form>
        
        <div class="login-link" style="text-align: center; margin-top: 15px;">
            Need an account? <a href="register.php" style="color: #4e18e4ff; text-decoration: none;">Registration</a>
        </div>
    </div>
</body>
</html>