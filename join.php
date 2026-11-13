<?php
declare(strict_types=1);
session_start();

if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
    header('Location: join.php');
    exit;
}

if (!empty($_SESSION['logged_in'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/pdo.php';
require_once __DIR__ . '/filter.php';

$pdo = get_db();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['join_anon'])) {
        $_SESSION['logged_in'] = true;
        $_SESSION['user'] = 'Anon';
        $_SESSION['is_admin'] = false;
        header('Location: index.php');
        exit;
    }

    if (isset($_POST['login'])) {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($violation = ContentFilter::check($username)) {
            $error = 'Nom d\'utilisateur interdit.';
        } elseif ($username !== '' && $password !== '') {
            $stmt = $pdo->prepare('SELECT `id`, `username`, `password_hash`, `role` FROM `users` WHERE `username` = :username LIMIT 1');
            $stmt->execute(['username' => $username]);
            $userData = $stmt->fetch();

            if ($userData && password_verify($password, $userData['password_hash'])) {
                $_SESSION['logged_in'] = true;
                $_SESSION['user'] = $userData['username'];
                $_SESSION['is_admin'] = ($userData['role'] === 'admin');
                header('Location: index.php');
                exit;
            }
            $error = 'Identifiants invalides.';
        }
    }
}

require_once __DIR__ . '/pdo.php';
require_once __DIR__ . '/filter.php';

$pdo = get_db();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['join_anon'])) {
        $_SESSION['logged_in'] = true;
        $_SESSION['user'] = 'Anon';
        $_SESSION['is_admin'] = false;
        header('Location: index.php');
        exit;
    }

    if (isset($_POST['login'])) {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($violation = ContentFilter::check($username)) {
            $error = 'Nom d\'utilisateur interdit.';
        } elseif ($username !== '' && $password !== '') {
            $stmt = $pdo->prepare('SELECT `id`, `username`, `password_hash`, `role` FROM `users` WHERE `username` = :username LIMIT 1');
            $stmt->execute(['username' => $username]);
            $userData = $stmt->fetch();

            if ($userData && password_verify($password, $userData['password_hash'])) {
                $_SESSION['logged_in'] = true;
                $_SESSION['user'] = $userData['username'];
                $_SESSION['is_admin'] = ($userData['role'] === 'admin');
                header('Location: index.php');
                exit;
            }
            $error = 'Identifiants invalides.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>5chan - Auth</title>
    <!-- Use root-relative path or exact path based on your folder structure -->
    <link rel="stylesheet" href="src/style/style.css?v=<?= time() ?>">
    <title>5chan - Auth</title>
    <!-- Use root-relative path or exact path based on your folder structure -->
    <link rel="stylesheet" href="src/style/style.css?v=<?= time() ?>">
</head>
<body class="tui-root">
    <?php require_once __DIR__ . '/header.php'; ?>

    <main class="tui-workspace auth-center-wrap">
        <div class="tui-window auth-window">
            <!-- Retro Window Titlebar with Close [X] -->
            <div class="window-titlebar">
                <span class="window-title">SYS::AUTH</span>
                <a href="../" class="window-btn-close" title="Exit / Home">[X]</a>
            </div>

            <!-- Mode Switcher Tabs (Sign in / Sign up) -->
            <div class="tui-tab-bar">
                <button type="button" class="tui-tab active" id="tab-signin">[ SIGN IN ]</button>
                <button type="button" class="tui-tab" id="tab-signup">[ SIGN UP ]</button>
            </div>

            <?php if (!empty($error)): ?>
                <div class="tui-alert">[ERR: <?= htmlspecialchars($error) ?>]</div>
            <?php endif; ?>

            <form method="POST" action="join.php" class="tui-auth-form" id="auth-form">
                <div class="auth-group">
                    <label for="username" class="auth-label">USER:</label>
                    <input type="text" id="username" name="username" class="tui-field" autocomplete="off" required autofocus>
                </div>

                <div class="auth-group">
                    <label for="password" class="auth-label">PASS:</label>
                    <input type="password" id="password" name="password" class="tui-field" required>
                </div>

                <!-- Utilities row: Remember me & Need Help -->
                <div class="auth-options-row">
                    <label class="tui-checkbox-container">
                        <input type="checkbox" name="remember" value="1" class="tui-checkbox">
                        <span class="checkbox-label">REMEMBER ME</span>
                    </label>
                    <button type="button" class="tui-inline-btn" id="btn-help">[?] NEED HELP</button>
                </div>

                <!-- Action Buttons: Authenticate & Register -->
                <div class="auth-action-grid">
                    <button type="submit" name="login" value="1" class="tui-btn auth-main-btn">[AUTHENTICATE]</button>
                    <button type="button" class="tui-btn" id="btn-register-action">[REGISTER]</button>
                </div>
            </form>

            <div class="tui-full-divider"></div>

            <form method="POST" action="join.php" class="anon-form">
                <button type="submit" name="join_anon" value="1" class="tui-btn full-w">[CONTINUE AS GUEST / ANON]</button>
            </form>
        </div>
    </main>
</body>
</html>