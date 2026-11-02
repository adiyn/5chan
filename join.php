<?php
declare(strict_types=1);

$_SESSION['user'] = '';

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join 5chan</title>
</head>

<body>
    <a href="index.php">
        join
    </a>
</body>

</html>