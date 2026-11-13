<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/pdo.php';

if (empty($_SESSION['is_admin'])) {
    http_response_code(403);
    die('nuh uh, admin privileges required you dum dum.');
}

$pdo = get_db();
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'toggle_site_lock':
        $pdo->query("UPDATE `site_settings` SET `setting_value` = IF(`setting_value` = '1', '0', '1') WHERE `setting_key` = 'site_locked'");
        break;

    case 'toggle_chat_lock':
        $pdo->query("UPDATE `site_settings` SET `setting_value` = IF(`setting_value` = '1', '0', '1') WHERE `setting_key` = 'chat_locked'");
        break;

    case 'clear_chat':
        $pdo->query("TRUNCATE TABLE `messages`");
        break;
}

header('Location: index.php');
exit;