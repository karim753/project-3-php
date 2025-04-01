<?php
session_start();
require 'database.php'; // Zorg ervoor dat $pdo juist is geïnitieerd

// Haal de tweets op uit de database
try {
    $stmt = $pdo->query("SELECT tweets.content, users.username, tweets.created_at 
                         FROM tweets 
                         JOIN users ON tweets.user_id = users.id 
                         ORDER BY tweets.created_at DESC");
    $tweets = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Databasefout: " . $e->getMessage());
    $tweets = []; // Zet een lege array zodat foreach niet crasht
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: tweet.php");
        exit();
    }

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Ongeldige aanvraag.");
    }

    $content = htmlspecialchars(trim($_POST['content']), ENT_QUOTES, 'UTF-8');
    if (empty($content)) {
        die("Tweet mag niet leeg zijn.");
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO tweets (user_id, content) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $content]);

        header("Location: home.php");
        exit();
    } catch (PDOException $e) {
        error_log("Databasefout: " . $e->getMessage());
        echo "Er ging iets fout, probeer het later opnieuw.";
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
    <form action="tweet.php" method="POST" class="tweet-form">
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
