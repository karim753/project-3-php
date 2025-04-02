<?php
// Verbinding maken met de database
$host = 'localhost';
$dbname = 'twitter_clone';
$username = 'root';
$password = '';

session_start();
require 'database.php'; // Zorg ervoor dat $pdo juist is geïnitieerd

// Controleer of de gebruiker is ingelogd
if (!isset($_SESSION['user_id'])) {
    header("Location: register.php"); // Verwijs naar de inlogpagina als de gebruiker niet ingelogd is
    exit();
}

// CSRF-token genereren en opslaan in de sessie
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Haal de tweets op uit de database
try {
    $stmt = $pdo->query("SELECT tweets.content, users.username, tweets.created_at 
                         FROM tweets 
                         JOIN users ON tweets.user_id = users.id 
                         ORDER BY tweets.created_at DESC");
    $tweets = $stmt->fetchAll();

    // Debug: Check of tweets correct worden opgehaald
    if (!$tweets) {
        error_log("Geen tweets gevonden in de database.");
    }
} catch (PDOException $e) {
    error_log("Databasefout: " . $e->getMessage());
    $tweets = []; // Zet een lege array zodat foreach niet crasht
}

// Verwerk de tweet wanneer het formulier is ingediend
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['content'])) {
        $content = trim($_POST['content']);
        if (empty($content)) {
            die("Tweet mag niet leeg zijn.");
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO tweets (user_id, content, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$_SESSION['user_id'], $content]);

            // Debug: Controleer of de tweet correct is toegevoegd
            if ($stmt->rowCount() > 0) {
                error_log("Tweet succesvol opgeslagen.");
            } else {
                error_log("Tweet is niet opgeslagen.");
            }

            header("Location: index.php");
            exit();
        } catch (PDOException $e) {
            error_log("Databasefout: " . $e->getMessage());
            echo "Er ging iets fout, probeer het later opnieuw.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Twitter Clone</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="menu">
    <div class="logo-container">
        <img src="twitterlogo.png" alt="Logo" class="logo">
    </div>
    <ul>
        <li><a href="#">Home</a></li>
        <li><a href="#">Ontdekken</a></li>
        <li><a href="#">Meldingen</a></li>
        <li><a href="#">Berichten</a></li>
        <li><a href="#">Profiel</a></li>
    </ul>
    <button class="tweet-btn">Tweet</button>
</div>

<div class="content">
    <h1> Welkom bij Twitter Clone </h1>
    <h2>Plaats een nieuwe tweet</h2>
    <form action="Index.php" method="POST" class="tweet-form">
        <div class="tweet-input">
            <img src="blank-pfp.webp" alt="Profiel" class="profile-pic">
            <textarea name="content" required placeholder="Wat gebeurt er?"></textarea>
        </div>
        <button type="submit">Tweet</button>
    </form>
    <h2>Recente tweets</h2>
    <?php foreach ($tweets as $tweet): ?>
        <div class="tweet">
            <p>
                <strong>
                    <?= htmlspecialchars($tweet['username']) ?>
                </strong>: <?= htmlspecialchars($tweet['content']) ?>
            </p>
            <small><?= $tweet['created_at'] ?></small>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>
