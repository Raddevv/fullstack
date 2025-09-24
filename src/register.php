<?php
session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// 4everToolsDB require
require_once '4everToolsDB.php';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voornaam = trim($_POST['voornaam']);
    $achternaam = trim($_POST['achternaam']);
    
    $wachtwoord = $_POST['wachtwoord'];
    $wachtwoord_confirm = $_POST['wachtwoord_confirm'];

    try {
        // Validate password
        if (strlen($wachtwoord) < 8) {
            $error_message = "Password must be at least 8 characters long";
        } elseif ($wachtwoord !== $wachtwoord_confirm) {
            $error_message = "Passwords do not match";
        } else {
            // Hash the password
            $hashed_password = password_hash($wachtwoord, PASSWORD_DEFAULT);
            
            // Insert new user with hashed password
            $stmt = $pdo->prepare("INSERT INTO klant (voornaam, achternaam, wachtwoord, admin) VALUES (?, ?, ?, 0)");
            $stmt->execute([$voornaam, $achternaam, $hashed_password]);
            
            // Set success message
            $success_message = "Account successfully created! You can now login.";
        }
    } catch (PDOException $e) {
        $error_message = "Registration error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Forever Tools</title>
    <link rel="stylesheet" href="background/siteStyling.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <h2>Create Account - Forever Tools</h2>
        
        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success_message)): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label for="voornaam">Voornaam:</label>
                <input type="text" id="voornaam" name="voornaam" required 
                       value="<?php echo isset($_POST['voornaam']) ? htmlspecialchars($_POST['voornaam']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="achternaam">Achternaam:</label>
                <input type="text" id="achternaam" name="achternaam" required
                       value="<?php echo isset($_POST['achternaam']) ? htmlspecialchars($_POST['achternaam']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="wachtwoord">Wachtwoord:</label>
                <input type="password" id="wachtwoord" name="wachtwoord" required
                       minlength="8" placeholder="At least 8 characters">
            </div>

            <div class="form-group">
                <label for="wachtwoord_confirm">Bevestig Wachtwoord:</label>
                <input type="password" id="wachtwoord_confirm" name="wachtwoord_confirm" required
                       minlength="8" placeholder="Repeat your password">
            </div>
            
            <button class="buttonGood" type="submit">Create Account</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="index.php">Login here</a>
        </div>
    </div>
</body>
</html>
