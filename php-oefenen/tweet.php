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
            header("Location: tweet.html");
            exit();
        } else {
            echo "Registratie mislukt. Probeer het opnieuw.";
        }
    } catch (PDOException $e) {
        error_log("Database fout: " . $e->getMessage());
        echo "Er is een fout opgetreden. Neem contact op met de beheerder.";
    }
}

// home.php - Homepagina van Twitter Clone
session_start();
require 'database.php';

// Controleer of de gebruiker is ingelogd
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Haal tweets op uit de database
$stmt = $pdo->query("SELECT tweets.content, users.username, tweets.created_at FROM tweets JOIN users ON tweets.user_id = users.id ORDER BY tweets.created_at DESC");
tweets = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>


<?php
// tweet.php - Tweet opslaan
session_start();
require 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    if (!isset($_POST['content']) || empty(trim($_POST['content']))) {
        die("Tweet mag niet leeg zijn.");
    }

    $content = trim($_POST['content']);
    try {
        $stmt = $pdo->prepare("INSERT INTO tweets (user_id, content) VALUES (?, ?)");
        if ($stmt->execute([$_SESSION['user_id'], $content])) {
            header("Location: tweet.html");
            exit();
        } else {
            echo "Tweet plaatsen mislukt.";
        }
    } catch (PDOException $e) {
        error_log("Database fout: " . $e->getMessage());
        echo "Er is een fout opgetreden. Probeer het opnieuw.";
    }
}
?>
