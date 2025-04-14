<?php
$host = 'localhost';
$dbname = 'twitter_clone';
$username = 'root';
$password = '';

session_start();
require 'database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: register.php");
    exit();
}

//pdo definen
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    // Optioneel: log als de verbinding succesvol is
    // error_log("Databaseverbinding succesvol!");
} catch (PDOException $e) {
    error_log("Database verbinding mislukt: " . $e->getMessage());  // Log de foutmelding
    die("Database verbinding mislukt: " . $e->getMessage());
}
// Haal admin-status op
$stmt = $pdo->prepare("SELECT id, username, is_admin FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch();
$isAdmin = $currentUser['is_admin'] ?? 0;


if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Ongeldige CSRF-token.");
    }

    if (isset($_POST['delete_tweet']) && isset($_POST['created_at'])) {
        try {
            if ($isAdmin) {
                $stmt = $pdo->prepare("DELETE FROM tweets WHERE created_at = ?");
                $stmt->execute([$_POST['created_at']]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM tweets WHERE user_id = ? AND created_at = ?");
                $stmt->execute([$_SESSION['user_id'], $_POST['created_at']]);
            }
            header("Location: index.php");
            exit();
        } catch (PDOException $e) {
            echo "Verwijderen is mislukt.";
        }
    }


    if (isset($_POST['content'])) {
        $content = trim($_POST['content']);
        if (empty($content)) {
            die("Tweet mag niet leeg zijn.");
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO tweets (user_id, content, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$_SESSION['user_id'], $content]);
            header("Location: index.php");
            exit();
        } catch (PDOException $e) {
            echo "Er ging iets fout, probeer het later opnieuw.";
        }
    }

    if (isset($_POST['tweet_id'])) {
        $tweetId = $_POST['tweet_id'];
        $userId = $_SESSION['user_id'];

        $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND tweet_id = ?");
        $stmt->execute([$userId, $tweetId]);
        $like = $stmt->fetch();

        if ($like) {
            $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND tweet_id = ?");
            $stmt->execute([$userId, $tweetId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO likes (user_id, tweet_id) VALUES (?, ?)");
            $stmt->execute([$userId, $tweetId]);
        }

        header("Location: index.php");
        exit();
    }
}

try {
    $stmt = $pdo->prepare("SELECT 
                            tweets.id AS tweet_id,
                            tweets.content, 
                            tweets.created_at, 
                            tweets.user_id, 
                            users.username,
                            COUNT(likes.id) AS like_count,
                            MAX(likes.user_id = ?) AS liked_by_user
                          FROM tweets
                          JOIN users ON tweets.user_id = users.id
                          LEFT JOIN likes ON tweets.id = likes.tweet_id
                          GROUP BY tweets.id
                          ORDER BY tweets.created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $tweets = $stmt->fetchAll();
} catch (PDOException $e) {
    $tweets = [];
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Home - Twitter Clone</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<div class="menu">
    <div class="logo-container">
        <a href="Index.php"><img src="twitterlogo.png" alt="Logo" class="logo"></a>
    </div>
    <ul>
        <li><a href="Index.php">Home</a></li>
        <li><a href="profiel.php">Profiel</a></li>
        <li><a href="login.php">Uitloggen</a></li>
    </ul>
    <a href="login.php" class="tweet-btn">Uitloggen</a>

</div>

<div class="content">
    <h1>Welkom bij Twitter Clone</h1>

    <h2>Plaats een nieuwe tweet</h2>
    <form action="Index.php" method="POST" class="tweet-form">
        <div class="tweet-input">
            <img src="blank-pfp.webp" alt="Profiel" class="profile-pic">
            <textarea name="content" required placeholder="Wat gebeurt er?"></textarea>
        </div>
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <button type="submit">Tweet</button>
    </form>

    <h2>Recente tweets</h2>
    <?php foreach ($tweets as $tweet): ?>
        <div class="tweet">
            <p>
                <strong>
                    <a href="profiel.php?id=<?= $tweet['user_id'] ?>" style="text-decoration:none; color:inherit;">
                        <?= htmlspecialchars($tweet['username']) ?>
                    </a>
                </strong>

                <?= htmlspecialchars($tweet['content']) ?>
            </p>
            <small><?= $tweet['created_at'] ?></small>

            <!-- Like knop met kleur afhankelijk van like status -->
            <form action="index.php" method="POST" class="like-form" style="display:inline;">
                <input type="hidden" name="tweet_id" value="<?= $tweet['tweet_id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <button type="submit" style="background:none;border:none;cursor:pointer;display:inline-flex;align-items:center;">
                    <?php if ($tweet['liked_by_user']): ?>
                        <i class="fa-solid fa-heart" style="color:#c43231;"></i>
                        <span style="color:#c43231; margin-left: 5px;"><?= $tweet['like_count'] ?></span>
                    <?php else: ?>
                        <i class="fa-regular fa-heart" style="color:#888;"></i>
                        <span style="color:#888; margin-left: 5px;"><?= $tweet['like_count'] ?></span>
                    <?php endif; ?>
                </button>
            </form>

            <!-- Verwijderknop -->
            <?php if ($tweet['user_id'] == $_SESSION['user_id'] || $isAdmin): ?>
                <form method="POST" action="index.php" onsubmit="return confirm('Weet je zeker dat je deze tweet wilt verwijderen?');" style="display:inline;">
                    <input type="hidden" name="delete_tweet" value="1">
                    <input type="hidden" name="created_at" value="<?= htmlspecialchars($tweet['created_at']) ?>">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <button type="submit" class="delete-button" style="margin-left:10px;">Verwijder</button>
                </form>
            <?php endif; ?>

        </div>
    <?php endforeach; ?>
</div>
</body>
</html>
