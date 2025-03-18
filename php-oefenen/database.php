<?php
// database.php - Verbinding maken met de database
$host = 'localhost';
$dbname = 'twitter_clone';
$username = 'root'; // Pas dit aan als nodig
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Database verbinding mislukt: " . $e->getMessage());
}
?>
