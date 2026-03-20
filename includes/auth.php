<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function start_session_if_needed(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name(app_env('SESSION_NAME', 'mlbtscoop_session'));
        session_start();
    }
}

function is_logged_in(): bool
{
    start_session_if_needed();
    return isset($_SESSION['user']);
}

function current_user(): ?array
{
    start_session_if_needed();
    return $_SESSION['user'] ?? null;
}

function attempt_login(string $username, string $password): bool
{
    start_session_if_needed();

    $stmt = db()->prepare('SELECT id, username, password_hash, is_admin FROM users WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'username' => $user['username'],
        'is_admin' => (bool) $user['is_admin'],
    ];

    return true;
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: /login.php');
        exit;
    }
}

function logout_user(): void
{
    start_session_if_needed();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function flash_set(string $type, string $message): void
{
    start_session_if_needed();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    start_session_if_needed();
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}
