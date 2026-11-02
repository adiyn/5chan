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
            <form id="login-form">
                <label for="username">Nick :</label>
                <input type="text" id="username" name="username" required placeholder="username">

                <input type="text" id="chat-message" placeholder="type something" autocomplete="off" required>
                <button type="submit">Send</button>
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
                    if(isset($_POST['submit']) && isset($_POST['message'])) {
                        $message = $_POST['message'];
                        $date = new DateTimeImmutable('now', $timezone);
                        $user = $_POST['user'] ?? null;
                        $stmt = $pdo->prepare('insert into messages (date, user, message) values (sysdate, username, mess)');
                        $stmt->execute(['user' => $_POST['user']]);
                    }
                    ?>
                </div>
            </div>
        </section>
    </div>

</body>

</html>