<?php
declare(strict_types=1);

session_start();

if (empty($_SESSION['user'])) {
    header('Location: join.php');
    exit;
}

require_once __DIR__ . '/pdo.php';
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    if ($user === '') {
        $user = 'Anon';
    }

    $message = trim($_POST['message'] ?? '');

    if ($message !== '') {
        $stmt = $pdo->prepare('INSERT INTO `messages` (`date`, `user`, `message`) VALUES (NOW(), :user, :message)');
        $stmt->execute([
            'user'    => $user,
            'message' => $message,
        ]);

        $_SESSION['user'] = $user;

        header('Location: index.php');
        exit;
    }
}

$stmt = $pdo->query('
    SELECT 
        `user`, 
        `message`, 
        DATE_FORMAT(`date`, "%H:%i:%s") AS `heure` 
    FROM `messages` 
    ORDER BY `date` ASC
');
$messages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>5chan</title>
    <link rel="stylesheet" href="src/style/style.css">
</head>
<body>
    <div class="container">
        <section id="auth-panel" class="card">
            <h2>Welcome <?= htmlspecialchars($_SESSION['user'] ?? 'Anonymous') ?></h2>
            <form id="login-form" method="POST" action="index.php">
                <label for="username">Nick :</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username"
                    placeholder="username" 
                    value="<?= htmlspecialchars($_SESSION['user'] ?? '') ?>"
                    >

                <label for="chat-message">Message :</label>
                <input 
                    type="text" 
                    id="chat-message" 
                    name="message" 
                    placeholder="type something" 
                    autocomplete="off" 
                    required
                >
                <button type="submit" name="submit" value="1">Send</button>
            </form>
        </section>

        <section id="chat-panel" class="card">
            <h2>/g/</h2>

            <div id="messages-box">
                <?php if (empty($messages)): ?>
                    <p style="color: #888;">Aucun message pour l'instant.</p>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <div class="msg">
                            <span class="time">[<?= htmlspecialchars($msg['heure']) ?>]</span>
                            <span class="author"><?= htmlspecialchars($msg['user']) ?>:</span>
                            <span class="text"><?= htmlspecialchars($msg['message']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <script>
        const msgBox = document.getElementById('messages-box');
        if (msgBox) {
            msgBox.scrollTop = msgBox.scrollHeight;
        }
    </script>
</body>
</html>