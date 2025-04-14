<?php
// Verbinding maken met de database
$host = 'localhost';
$dbname = 'twitter_clone';
$username = 'root';
$password = '';

$error = '';
$error2 = null;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Database verbinding mislukt: " . $e->getMessage());
}

// register.php - Registratie van gebruikers
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Input validatie
    $username = trim($_POST['username']);
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];

    if (!$username || !$email || !$password) {
        $error = "Vul alstublieft een geldige gebruikersnaam, e-mailadres en wachtwoord in.";
    } else {
        // Gehashte wachtwoord maken
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Query voorbereiden en uitvoeren (met is_admin standaard op 0)
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, 0)");
            if ($stmt->execute([$username, $email, $hashed_password])) {
                // Haal de laatst ingevoegde gebruiker op (om in te loggen)
                $user_id = $pdo->lastInsertId();

                // Start sessie en stel user_id in voor inloggen
                session_start();
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['is_admin'] = 0; // ✅ Standaard geen admin

                // Redirect naar index.php na registratie en inloggen
                header("Location: index.php");
                exit(); // Zorg ervoor dat het script stopt na de redirect
            } else {
                echo "Registratie mislukt. Probeer het opnieuw.";
            }
        } catch (PDOException $e) {
            error_log("Database fout: " . $e->getMessage());
            $error2 = "Deze e-mail/gebruikersnaam is al in gebruik";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registreren - Twitter Clone</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="registreer-left-section">
    <img src="twitterlogo.png" alt="Twitter Clone Logo" class="registreer-logo">
</div>
<div class="registreer-right-section">
    <h2>Nu registreren</h2>
    <?php if (!empty($error)): ?>
        <div class="error-message">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    <?php if ($error2) echo $error2; ?>

    <form action="register.php" method="POST" class="registreer-form">
        <div class="registreer-input-group">
            <label for="username" class="registreer-label">Gebruikersnaam:</label>
            <input type="text" id="username" name="username" class="registreer-input" required>
        </div>
        <div class="registreer-input-group">
            <label for="email" class="registreer-label">E-mail:</label>
            <input type="email" id="email" name="email" class="registreer-input" required>
        </div>
        <div class="registreer-input-group">
            <label for="password" class="registreer-label">Wachtwoord:</label>
            <input type="password" id="password" name="password" class="registreer-input" required>
        </div>
        <button type="submit" class="registreer-submit-btn">Account aanmaken</button>
    </form>
    <p class="register-link">Heb je al een account? <a href="login.php">Inloggen</a></p>
</div>
</body>
</html>
