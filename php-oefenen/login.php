<?php
// Verbinding maken met de database
$host = 'localhost';
$dbname = 'twitter_clone';
$username = 'root';
$password = '';

$error = null; // Foutmelding standaard op null

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Database verbinding mislukt: " . $e->getMessage());
}

// login.php - Inloggen van gebruikers
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login']); // Kan een e-mail of gebruikersnaam zijn
    $password = $_POST['password'];

    if (!$login || !$password) {
        $error = '<div class="login-error">Vul alstublieft een geldige gebruikersnaam/e-mail en wachtwoord in.</div>';
    } else {
        try {
            // Query om te zoeken naar de gebruiker op gebruikersnaam of e-mail
            $stmt = $pdo->prepare("SELECT id, username, password, is_admin FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$login, $login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['is_admin']; // ✅ Adminstatus opslaan in sessie

                header("Location: index.php");
                exit();
            } else {
                $error = '<div class="login-error">Ongeldige gebruikersnaam/e-mail of wachtwoord.</div>';
            }
        } catch (PDOException $e) {
            error_log("Database fout: " . $e->getMessage());
            $error = '<div class="login-error">Er is een fout opgetreden. Neem contact op met de beheerder.</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inloggen - Twitter Clone</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="login-left-section">
    <img src="twitterlogo.png" alt="Twitter Clone Logo" class="login-logo">
</div>
<div class="login-right-section">
    <h2>Inloggen</h2>

    <!--verkeerde wachtwoord of email -->
    <?php if ($error) echo $error; ?>

    <form action="login.php" method="POST" class="login-form">
        <div class="login-input-group">
            <label for="login" class="login-label">Gebruikersnaam of e-mail:</label>
            <input type="text" id="login" name="login" class="login-input" required>
        </div>
        <div class="login-input-group">
            <label for="password" class="login-label">Wachtwoord:</label>
            <input type="password" id="password" name="password" class="login-input" required>
        </div>
        <button type="submit" class="login-submit-btn">Inloggen</button>
    </form>
    <p class="register-link">Geen account? <a href="register.php">Registreer hier</a></p>
</div>
</body>
</html>
