<?php
declare(strict_types=1);
session_start();

$forum = "/B";
$page_name = "random";

$currentUser = $_SESSION['user'] ?? 'Anon';
$is_admin = !empty($_SESSION['is_admin']);

require_once __DIR__ . '/pdo.php';
require_once __DIR__ . '/filter.php';

$pdo = get_db();

$rawSettings = $pdo->query("SELECT `setting_key`, `setting_value` FROM `site_settings`")->fetchAll(PDO::FETCH_KEY_PAIR);
$is_site_locked = ($rawSettings['site_locked'] ?? '0') === '1';
$is_chat_locked = ($rawSettings['chat_locked'] ?? '0') === '1';


$ip_hash = hash_hmac('sha256', $_SERVER['REMOTE_ADDR'], IP_PEPPER);

$currentUser = $_SESSION['user'] ?? 'Anon';
$is_admin = !empty($_SESSION['is_admin']);
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    if ($is_chat_locked && !$is_admin) {
        http_response_code(403);
        die('Chat locked by operator.');
    }

    $user = trim($_POST['username'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($violation = ContentFilter::check($message)) {
        $error = $violation;
    } elseif ($currentUser === 'Anon' && $user !== '' && ($violationUser = ContentFilter::check($user))) {
        $error = 'Invalid nick: ' . $violationUser;
    } elseif ($message !== '') {
        $displayName = $currentUser !== 'Anon' ? $currentUser : ($user !== '' ? $user : 'Anon');

        $stmt = $pdo->prepare('
            INSERT INTO `messages` (`date`, `user`, `message`, `ip_hash`) 
            VALUES (NOW(), :user, :message, :ip_hash)
        ');
        $stmt->execute([
            'user' => $displayName,
            'message' => $message,
            'ip_hash' => $ip_hash,
        ]);

        if ($currentUser === 'Anon') {
            $_SESSION['user'] = $user !== '' ? $user : null;
        }

        header('Location: forum_1.php');
        exit;
    }
}

if ($is_site_locked && !$is_admin) {
    http_response_code(503);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>5chan - System Locked</title>
        <link rel="stylesheet" href="src/style/style.css?v=<?= time() ?>">
    </head>
    <body class="tui-root">
        <div class="tui-window" style="margin: auto; max-width: 400px; padding: 20px;">
			<div class="window-titlebar">
				<span class="window-title">SYS::LOCKED</span>
				<a href="join.php?logout=1" class="window-btn-close" title="Exit / Home">[X]</a>
			</div>
            <p style="padding-top: 10px;">ERR: Site locked for maintenance.</p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$banCheck = $pdo->prepare('
    SELECT `reason` FROM `chan_bans` 
    WHERE `ip_hash` = :ip_hash 
      AND (`expires_at` IS NULL OR `expires_at` > NOW())
    LIMIT 1
');

$banCheck->execute(['ip_hash' => $ip_hash]);
if ($ban = $banCheck->fetch()) {
    http_response_code(403);
    die('ACCESS DENIED: ' . htmlspecialchars($ban['reason'] ?? 'banned'));
}

$mailto_link = '#';
if ($currentUser !== 'Anon') {
    $rgpdStmt = $pdo->prepare('SELECT `date`, `message` FROM `messages` WHERE `user` = :user ORDER BY `date` ASC');
    $rgpdStmt->execute(['user' => $currentUser]);
    $userMessages = $rgpdStmt->fetchAll();

    $exportLines = ["=== EXPORT RGPD DES MESSAGES (5chan) ===", "Utilisateur : " . $currentUser, "Date : " . date('Y-m-d H:i:s'), ""];
    foreach ($userMessages as $m) {
        $exportLines[] = "[{$m['date']}] {$m['message']}";
    }
    $exportBody = implode("\r\n", $exportLines);
    $mailto_link = 'mailto:?subject=' . rawurlencode('[5chan] Export donnees') . '&body=' . rawurlencode($exportBody);
}

$stmt = $pdo->query('
    SELECT 
        `user`, 
        `message`, 
        DATE_FORMAT(`date`, "%H:%i:%s") AS `hour` 
    FROM `messages` 
    ORDER BY `date` ASC
');

$messages = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>5chan terminal</title>
    <link rel="stylesheet" href="src/style/style.css?v=<?= time() ?>">
</head>
<body class="tui-root">
    <?php require_once __DIR__ . '/header.php'; ?>

    <main class="tui-workspace">
        <?php if ($is_admin): ?>
            <section class="tui-admin-bar">
                <span class="bar-tag">[SYSADM]</span>
                <form method="POST" action="admin_actions.php">
                    <input type="hidden" name="action" value="toggle_site_lock">
                    <button type="submit" class="tui-btn <?= $is_site_locked ? 'active' : '' ?>">
                        [SITE_LOCK:<?= $is_site_locked ? 'ON' : 'OFF' ?>]
                    </button>
                </form>
                <form method="POST" action="admin_actions.php">
                    <input type="hidden" name="action" value="toggle_chat_lock">
                    <button type="submit" class="tui-btn <?= $is_chat_locked ? 'active' : '' ?>">
                        [CHAT_LOCK:<?= $is_chat_locked ? 'ON' : 'OFF' ?>]
                    </button>
                </form>
                <form method="POST" action="admin_actions.php" onsubmit="return confirm('Purge ?');">
                    <input type="hidden" name="action" value="clear_chat">
                    <button type="submit" class="tui-btn">[TRUNCATE]</button>
                </form>
            </section>
        <?php endif; ?>

        <!-- Chat -->
        <section class="tui-buffer-container">
            <div id="messages-box" class="tui-buffer">
                <?php if (empty($messages)): ?>
                    <div class="system-line">* Buffer empty. Ready for transmission.</div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <div class="msg-row">
                            <span class="col-time"><?= htmlspecialchars($msg['hour']) ?></span>
                            <span class="col-user">&lt;<?= htmlspecialchars($msg['user']) ?>&gt;</span>
                            <span class="col-text"><?= htmlspecialchars($msg['message']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- Status / Notice Bar -->
        <div class="tui-status-sub">
            <div class="sub-left">
                <span>USER: <?= htmlspecialchars($currentUser) ?></span>
                <?php if ($is_chat_locked): ?>
                    <span class="badge-notice">[CHAT_LOCKED]</span>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <span class="badge-alert">[ERR: <?= htmlspecialchars($error) ?>]</span>
                <?php endif; ?>
            </div>
            <div class="sub-right">
                <?php if ($currentUser !== 'Anon'): ?>
                    <a href="<?= $mailto_link ?>" class="tui-link">[RGPD]</a>
                <?php endif; ?>
                <a href="join.php?logout=1" class="tui-link">[DISCONNECT]</a>
            </div>
        </div>

        <!-- Input Line -->
        <form id="chat-input-line" class="tui-input-line" method="POST" action="index.php">
            <div class="prompt-prefix">
                <?php if ($currentUser === 'Anon'): ?>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        placeholder="nick" 
                        value="Anon"
                        class="tui-nick-input"
                        autocomplete="off"
                        <?= $is_chat_locked && !$is_admin ? 'disabled' : '' ?>
                    >
                <?php else: ?>
                    <span class="static-nick"><?= htmlspecialchars($currentUser) ?></span>
                <?php endif; ?>
                <span class="prompt-cursor">&gt;</span>
            </div>

            <input 
                type="text" 
                id="chat-message" 
                name="message" 
                placeholder="<?= $is_chat_locked && !$is_admin ? 'buffer read-only' : 'enter message...' ?>" 
                autocomplete="off" 
                autofocus
                class="tui-msg-input"
                <?= $is_chat_locked && !$is_admin ? 'disabled' : 'required' ?>
            >

            <button type="submit" name="submit" value="1" class="tui-submit-btn" <?= $is_chat_locked && !$is_admin ? 'disabled' : '' ?>>
                SEND
            </button>
        </form>
    </main>

    <script>
        const msgBox = document.getElementById('messages-box');
        if (msgBox) {
            msgBox.scrollTop = msgBox.scrollHeight;
        }
    </script>
</body>
</html>
