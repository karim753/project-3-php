<?php
// Verbinding maken met de database
$host = 'localhost';
$dbname = 'twitter_clone';
$username = 'root';
$password = '';

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
        die("Vul alstublieft een geldige gebruikersnaam, e-mailadres en wachtwoord in.");
    }

    // Gehashte wachtwoord maken
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Query voorbereiden en uitvoeren
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        if ($stmt->execute([$username, $email, $hashed_password])) {
            // Haal de laatst ingevoegde gebruiker op (om in te loggen)
            $user_id = $pdo->lastInsertId();

            // Start sessie en stel user_id in voor inloggen
            session_start();
            $_SESSION['user_id'] = $user_id;

            // Redirect naar index.php na registratie en inloggen
            header("Location: Index.php");
            exit(); // Zorg ervoor dat het script stopt na de redirect
        } else {
            echo "Registratie mislukt. Probeer het opnieuw.";
        }
    } catch (PDOException $e) {
        error_log("Database fout: " . $e->getMessage());
        echo "Er is een fout opgetreden. Neem contact op met de beheerder.";
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
</div>
</body>
</html>

