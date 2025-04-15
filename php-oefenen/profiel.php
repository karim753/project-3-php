<?php
session_start();
require 'database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: register.php");
    exit();
}

// CSRF-token maken
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Bepaal welk profiel wordt bekeken
$viewed_user_id = isset($_GET['id']) ? intval($_GET['id']) : $_SESSION['user_id'];
$is_own_profile = $viewed_user_id === $_SESSION['user_id'];
$is_admin = $_SESSION['is_admin'] ?? false; // Zorg dat 'is_admin' bij login wordt gezet

// Als het CSRF-token ontbreekt of niet goed is stop dan het script dit is  een aanval.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Ongeldige CSRF-token.");
    }

    // Profiel bijwerken alleen eigen profiel)
    if ($is_own_profile && isset($_POST['update_profile'])) {
        $newUsername = trim($_POST['username']);
        $newBio = trim($_POST['bio']);

        if (!empty($newUsername)) {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, bio = ? WHERE id = ?");
            $stmt->execute([$newUsername, $newBio, $_SESSION['user_id']]);
            header("Location: profiel.php");
            exit();
        }
    }

    // Tweet liken  unliken
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

        header("Location: profiel.php?id=" . $viewed_user_id);
        exit();
    }

    // Tweet verwijderen alleen eigen profiel
    if ($is_own_profile && isset($_POST['delete_tweet']) && isset($_POST['created_at'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM tweets WHERE user_id = ? AND created_at = ?");
            $stmt->execute([$_SESSION['user_id'], $_POST['created_at']]);
            header("Location: profiel.php");
            exit();
        } catch (PDOException $e) {
            error_log("Verwijderfout (profiel): " . $e->getMessage());
            echo "Tweet verwijderen is mislukt.";
        }
    }

    // Admin: gebruiker verwijderen
    if ($is_admin && isset($_POST['delete_user']) && isset($_POST['user_id'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$_POST['user_id']]);
            header("Location: index.php");
            exit();
        } catch (PDOException $e) {
            error_log("Fout bij verwijderen gebruiker: " . $e->getMessage());
            echo "Gebruiker verwijderen is mislukt.";
        }
    }
}

// Gebruikersinfo ophalen
$stmt = $pdo->prepare("SELECT username, bio, created_at FROM users WHERE id = ?");
$stmt->execute([$viewed_user_id]);
$user = $stmt->fetch();

// Tweets ophalen
$stmt = $pdo->prepare("
    SELECT 
        tweets.id AS tweet_id,
        tweets.content, 
        tweets.created_at,
        tweets.user_id,
        COUNT(likes.id) AS like_count,
        MAX(likes.user_id = ?) AS liked_by_user
    FROM tweets
    LEFT JOIN likes ON tweets.id = likes.tweet_id
    WHERE tweets.user_id = ?
    GROUP BY tweets.id
    ORDER BY tweets.created_at DESC
");
$stmt->execute([$_SESSION['user_id'], $viewed_user_id]);
$userTweets = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profiel - Twitter Clone</title>
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
    <div class="profiel">
        <img src="blank-pfp.webp" alt="Profiel foto" class="profile-pic">
        <h2><?= htmlspecialchars($user['username']) ?></h2>
        <p>Aangemaakt op: <?= date('d-m-Y', strtotime($user['created_at'])) ?></p>
        <?php if (!empty($user['bio'])): ?>
            <p><?= htmlspecialchars($user['bio']) ?></p>
        <?php endif; ?>

        <?php if ($is_own_profile): ?>
            <button id="editProfileBtn" class="bewerk-knop">Bewerk Profiel</button>
        <?php endif; ?>

        <?php if ($is_admin && !$is_own_profile): ?>
            <form method="POST" action="profiel.php?id=<?= $viewed_user_id ?>" onsubmit="return confirm('Weet je zeker dat je deze gebruiker wilt verwijderen? Dit kan niet ongedaan worden.')">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="delete_user" value="1">
                <input type="hidden" name="user_id" value="<?= $viewed_user_id ?>">
                <button type="submit" class="delete-button">Verwijder gebruiker</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($is_own_profile): ?>
        <!-- Modal -->
        <div id="editProfileModal" class="modal">
            <div class="modal-content">
                <span class="close" id="closeModalBtn">&times;</span>
                <h3>Bewerk Profiel</h3>
                <form method="POST" action="profiel.php">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="update_profile" value="1">

                    <label for="username" class="registreer-label">Gebruikersnaam:</label>
                    <input type="text" id="username" name="username" class="registreer-input" value="<?= htmlspecialchars($user['username']) ?>" required>

                    <label for="bio" class="registreer-label">Bio:</label>
                    <textarea id="bio" name="bio" class="registreer-input" rows="4"><?= htmlspecialchars($user['bio']) ?></textarea>

                    <button type="submit" class="bewerk-knop">Opslaan</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="tweets">
        <h3><?= $is_own_profile ? 'Jouw Tweets' : 'Tweets van ' . htmlspecialchars($user['username']) ?></h3>
        <?php if (empty($userTweets)): ?>
            <p>Geen tweets gevonden.</p>
        <?php else: ?>
            <?php foreach ($userTweets as $tweet): ?>
                <div class="tweet">
                    <p><?= htmlspecialchars($tweet['content']) ?></p>
                    <span><?= date('d-m-Y H:i', strtotime($tweet['created_at'])) ?></span>

                    <!-- Like -->
                    <form action="profiel.php?id=<?= $viewed_user_id ?>" method="POST" style="display:inline;">
                        <input type="hidden" name="tweet_id" value="<?= $tweet['tweet_id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <button type="submit" style="background:none;border:none;cursor:pointer;display:inline-flex;align-items:center;">
                            <?php if ($tweet['liked_by_user']): ?>
                                <i class="fa-solid fa-heart" style="color:#c43231;"></i>
                                <span style="color:#c43231;margin-left:5px;"><?= $tweet['like_count'] ?></span>
                            <?php else: ?>
                                <i class="fa-regular fa-heart" style="color:#888;"></i>
                                <span style="color:#888;margin-left:5px;"><?= $tweet['like_count'] ?></span>
                            <?php endif; ?>
                        </button>
                    </form>

                    <!-- Verwijder alleen eigen tweet -->
                    <?php if ($is_own_profile): ?>
                        <form method="POST" action="profiel.php" onsubmit="return confirm('Weet je zeker dat je deze tweet wilt verwijderen?');" style="display:inline;">
                            <input type="hidden" name="delete_tweet" value="1">
                            <input type="hidden" name="created_at" value="<?= htmlspecialchars($tweet['created_at']) ?>">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <button type="submit" class="delete-button" style="margin-left:10px;">Verwijder</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    const modal = document.getElementById('editProfileModal');
    const btn = document.getElementById('editProfileBtn');
    const span = document.getElementById('closeModalBtn');

    if (btn) {
        btn.onclick = () => modal.style.display = "flex";
    }

    if (span) {
        span.onclick = () => modal.style.display = "none";
    }

    window.onclick = function (event) {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    }
</script>
</body>
</html>
