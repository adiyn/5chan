<?php
declare(strict_types=1);
session_start();

if (empty($_SESSION['logged_in'])) {
    header('Location: join.php');
    exit;
}

require_once __DIR__ . '/pdo.php';

header('Location: forum_1.php');
?>
