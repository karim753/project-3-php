<?php
// database.php - Verbinding maken met de database
$host = 'localhost';
$dbname = 'twitter_clone';
$username = 'root'; // Pas dit aan als nodig
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Database verbinding mislukt: " . $e->getMessage());
}
?>

<!-- register.php -->
<?php
session_start();
require 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];

    if (!$username || !$email || !$password) {
        die("Vul een geldige gebruikersnaam, e-mailadres en wachtwoord in.");
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        if ($stmt->execute([$username, $email, $hashed_password])) {
            header("Location: login.php");
            exit();
        }
    } catch (PDOException $e) {
        error_log("Database fout: " . $e->getMessage());
        echo "Er is een fout opgetreden. Probeer opnieuw.";
    }
}
?>

<!-- home.php -->
<?php
session_start();
require 'database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $pdo->query("SELECT tweets.content, users.username, tweets.created_at FROM tweets JOIN users ON tweets.user_id = users.id ORDER BY tweets.created_at DESC");
$tweets = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Home - Twitter Clone</title>
</head>
<body>
<h1>Welkom, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
<form action="tweet.php" method="POST">
    <textarea name="content" required></textarea>
    <button type="submit">Tweet</button>
</form>
<h2>Laatste tweets</h2>
<?php foreach ($tweets as $tweet): ?>
    <p><strong><?php echo htmlspecialchars($tweet['username']); ?>:</strong> <?php echo htmlspecialchars($tweet['content']); ?> (<?php echo $tweet['created_at']; ?>)</p>
<?php endforeach; ?>
</body>
</html>

<!-- tweet.php -->
<?php
session_start();
require 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $content = trim($_POST['content']);
    if (empty($content)) {
        die("Tweet mag niet leeg zijn.");
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO tweets (user_id, content, created_at) VALUES (?, ?, NOW())");
        if ($stmt->execute([$_SESSION['user_id'], $content])) {
            // Controleer of de tweet correct in de database staat
            $lastInsertId = $pdo->lastInsertId();
            $checkStmt = $pdo->prepare("SELECT * FROM tweets WHERE id = ?");
            $checkStmt->execute([$lastInsertId]);
            $tweet = $checkStmt->fetch();

            if ($tweet) {
                header("Location: home.php");
                exit();
            } else {
                echo "Tweet plaatsen mislukt. Probeer opnieuw.";
            }
        }
    } catch (PDOException $e) {
        error_log("Database fout: " . $e->getMessage());
        echo "Er is een fout opgetreden. Probeer opnieuw.";
    }
}
?>
