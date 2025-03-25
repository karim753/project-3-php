<?php
//Verbinding maken met de database
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
    require 'database.php';

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
            header("Location:tweet.html");
            exit();
        } else {
            echo "Registratie mislukt. Probeer het opnieuw.";
        }
    } catch (PDOException $e) {
        error_log("Database fout: " . $e->getMessage());
        echo "Er is een fout opgetreden. Neem contact op met de beheerder.";
    }
}

?>
