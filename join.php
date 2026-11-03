<?php
declare(strict_types=1);

session_start();

$_SESSION['logged_in'] = true;
$_SESSION['user'] = null;

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: join.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join'])) {
    $_SESSION['user'] = 'Anon';
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join 5chan</title>
    <link rel="stylesheet" href="src/style/style.css">
</head>
<body>
    <div class="container join-wrapper">
    <div class="card join-card">
        <h1>5chan</h1>
        <p>Ready to join the board? :3</p>
        <form method="POST" action="join.php">
            <button type="submit" name="join" class="join-btn">Join /g/</button>
        </form>
    </div>
</div>
</body>
</html>