<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';

start_secure_session();
apply_rate_limit('login');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /backend/login.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? null;
if (!verify_csrf_token(is_string($csrfToken) ? $csrfToken : null)) {
    $_SESSION['auth_error'] = 'Invalid CSRF token.';
    header('Location: /backend/login.php');
    exit;
}

$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    $_SESSION['auth_error'] = 'Invalid email or password.';
    header('Location: /backend/login.php');
    exit;
}

try {
    $stmt = db()->prepare('
        SELECT id, password_hash, wallet_address, wallet_bound_at
        FROM users
        WHERE email = :email
        LIMIT 1
    ');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
        $_SESSION['auth_error'] = 'Invalid email or password.';
        header('Location: /backend/login.php');
        exit;
    }

    login_user((int) $user['id']);

    // Optional: store wallet info in session for convenience (if you want)
    $_SESSION['wallet_address'] = $user['wallet_address'] ?? null;
    $_SESSION['wallet_bound_at'] = $user['wallet_bound_at'] ?? null;

    header('Location: /backend/app.php');
    exit;
} catch (Throwable $e) {
    error_log('Login failure: ' . $e->getMessage());
    $_SESSION['auth_error'] = 'Login failed. Please try again.';
    header('Location: /backend/login.php');
    exit;
}