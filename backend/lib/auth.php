<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/response.php';

function app_config(): array
{
    static $config;
    if (!is_array($config)) {
        $config = require __DIR__ . '/../config/config.php';
    }
    return $config;
}

function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    return (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
}

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $config = app_config();
    $sessionName = !empty($config['session_name']) ? (string)$config['session_name'] : 'commvault_session';

    session_name($sessionName);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', is_https() ? '1' : '0');

    session_start();
}

function generate_csrf_token(): string
{
    start_secure_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool
{
    start_secure_session();
    if (!isset($_SESSION['csrf_token']) || !$token) {
        return false;
    }
    return hash_equals((string)$_SESSION['csrf_token'], (string)$token);
}

function current_user(): ?array
{
    start_secure_session();

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    // ✅ Only select columns that exist in your current users table
    $stmt = db()->prepare('
        SELECT
          id,
          email,
          name,
          wallet_address,
          wallet_bound_at,
          is_active,
          created_at
        FROM users
        WHERE id = :id
        LIMIT 1
    ');
    $stmt->execute(['id' => (int)$_SESSION['user_id']]);
    $user = $stmt->fetch();

    // Optional: if the account is deactivated, treat as logged out
    if (!$user || (isset($user['is_active']) && (int)$user['is_active'] !== 1)) {
        return null;
    }

    return $user ?: null;
}

function require_auth(bool $asJson = false): array
{
    $user = current_user();

    if ($user) {
        return $user;
    }

    if ($asJson) {
        json_response(['error' => 'Unauthorized'], 401);
    }

    header('Location: /backend/login.php');
    exit;
}

function apply_rate_limit(string $key, int $maxAttempts = 5, int $windowSeconds = 300): void
{
    start_secure_session();

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $slot = sprintf('rate_%s_%s', $key, md5($ip));
    $now = time();

    if (!isset($_SESSION[$slot])) {
        $_SESSION[$slot] = ['count' => 0, 'first' => $now];
    }

    if (($now - (int)$_SESSION[$slot]['first']) > $windowSeconds) {
        $_SESSION[$slot] = ['count' => 0, 'first' => $now];
    }

    if ((int)$_SESSION[$slot]['count'] >= $maxAttempts) {
        http_response_code(429);
        echo 'Too many attempts. Please wait and try again.';
        exit;
    }

    $_SESSION[$slot]['count'] = (int)$_SESSION[$slot]['count'] + 1;
}

function login_user(int $userId): void
{
    start_secure_session();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
}

function logout_user(): void
{
    start_secure_session();
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

    session_destroy();
}

function require_auth_json(): void
{
    start_secure_session();
    if (empty($_SESSION['user_id'])) {
        json_response(['error' => 'Unauthorized'], 401);
    }
}