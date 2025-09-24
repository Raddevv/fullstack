<?php
session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// 4everToolsDB require
require_once '4everToolsDB.php';

// Add admin and wachtwoord columns if they don't exist
try {
    $pdo->exec("ALTER TABLE klant ADD COLUMN IF NOT EXISTS admin TINYINT(1) NOT NULL DEFAULT 0");
    $pdo->exec("ALTER TABLE klant ADD COLUMN IF NOT EXISTS wachtwoord VARCHAR(255)");
    
    // Set default password for all accounts that don't have one
    $default_password = password_hash('Password123', PASSWORD_DEFAULT);
    $pdo->exec("UPDATE klant SET wachtwoord = '$default_password' WHERE wachtwoord IS NULL");
    
    // Insert or update Jordy Meijer as admin with default password
    $default_admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO klant (voornaam, achternaam, wachtwoord, admin) 
                          SELECT 'Jordy', 'Meijer', ?, 1 
                          WHERE NOT EXISTS (
                              SELECT 1 FROM klant 
                              WHERE voornaam = 'Jordy' AND achternaam = 'Meijer'
                          ) LIMIT 1");
    $stmt->execute([$default_admin_password]);
    $stmt->execute();
    
    $stmt = $pdo->prepare("UPDATE klant SET admin = 1 
                          WHERE voornaam = 'Jordy' AND achternaam = 'Meijer'");
    $stmt->execute();
} catch (PDOException $e) {
    // Silently handle the error if column already exists
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voornaam = $_POST['voornaam'];
    $achternaam = $_POST['achternaam'];
    $wachtwoord = $_POST['wachtwoord'];
    
    try {
        // Query to check user credentials
        $stmt = $pdo->prepare("SELECT id, voornaam, achternaam, wachtwoord, admin FROM klant WHERE voornaam = ? AND achternaam = ?");
        $stmt->execute([$voornaam, $achternaam]);
        $user = $stmt->fetch();

        // Verify user exists and password is correct
        if ($user && password_verify($wachtwoord, $user['wachtwoord'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['voornaam'] = $user['voornaam'];
            $_SESSION['admin'] = $user['admin'];
            
            // Redirect to dashboard
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
            Need an account? <a href="register.php" style="color: #007bff; text-decoration: none;">Register here</a>
        </div>
    </div>
</body>
</html>