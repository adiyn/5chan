<?php
declare(strict_types=1);

session_start();

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>5chan</title>
</head>

<body>
    <div class="container">
        <!-- connection -->
        <section id="auth-panel" class="card">
            <h2>Welcome <?php $_SESSION['user'] ?? '' ?></h2>
            <form id="login-form" method="POST" action="index.php">
                <label for="username">Nick :</label>
                <input type="text" id="username" name="username" required placeholder="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">

                <input type="text" id="chat-message" name="message" placeholder="type something" autocomplete="off" required>
                <button type="submit" name="submit" value="1">Send</button>
            </form>
        </section>

        <!-- chat -->
        <section id="chat-panel" class="card">
            <h2>/g/</h2>

            <div id="messages-box">
                <!-- messages -->
                <div class="msg">
                    <?php
                    require __DIR__ . '/pdo.php';
                    $pdo = get_db();

                    if (isset($_POST['submit']) && !empty($_POST['message']) && !empty($_POST['username'])) {
                        $message = trim($_POST['message']);
                        $user = trim($_POST['username']);

                        $stmt = $pdo->prepare('INSERT INTO messages (date, user, message) VALUES (NOW(), :user, :message)');
                        $stmt->execute([
                            'user'    => $user,
                            'message' => $message,
                        ]);
                    }
                    ?>
                </div>
            </div>
        </section>
    </div>

</body>

</html>