<?php
/** @var PDO $pdo */
require 'database.php';
// register.php - Registratie van gebruikers

// Controleren of het een POST-verzoek is
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require 'database.php';

    // Input validatie
    $username = trim($_POST['username']);
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];

    if (!$username || !$email || !$password) {
        // Controleer of de vereiste velden zijn ingevuld
        die("Vul alstublieft een geldige gebruikersnaam, e-mailadres en wachtwoord in.");
    }

    // Gehashte wachtwoord maken
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Query voorbereiden en uitvoeren
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        if ($stmt->execute([$username, $email, $hashed_password])) {
            echo "Registratie succesvol!";
        } else {
            echo "Registratie mislukt. Probeer het opnieuw.";
        }
    } catch (PDOException $e) {
        // Foutrapportage (Log intern voor debugging, laat aan gebruiker een melding)
        error_log("Database fout: " . $e->getMessage());
        echo "Er is een fout opgetreden. Neem contact op met de beheerder.";
    }
}
?>