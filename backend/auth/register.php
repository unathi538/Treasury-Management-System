<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';

start_secure_session();
apply_rate_limit('register');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /backend/register.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? null;
if (!verify_csrf_token(is_string($csrfToken) ? $csrfToken : null)) {
    $_SESSION['auth_error'] = 'Invalid CSRF token.';
    header('Location: /backend/register.php');
    exit;
}

$name = trim((string)($_POST['name'] ?? ''));
$email = strtolower(trim((string)($_POST['email'] ?? '')));
$password = (string)($_POST['password'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['auth_error'] = 'Please provide a valid email.';
    header('Location: /backend/register.php');
    exit;
}

if (strlen($password) < 8) {
    $_SESSION['auth_error'] = 'Password must be at least 8 characters.';
    header('Location: /backend/register.php');
    exit;
}

try {
    $pdo = db();

    // Ensure email not taken
    $check = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $check->execute(['email' => $email]);

    if ($check->fetch()) {
        $_SESSION['auth_error'] = 'Email is already registered.';
        header('Location: /backend/register.php');
        exit;
    }

    // ✅ Match your actual table columns (NO email_verified_at)
    $insert = $pdo->prepare('
        INSERT INTO users (email, password_hash, name, wallet_address, wallet_bound_at, is_active)
        VALUES (:email, :password_hash, :name, NULL, NULL, 1)
    ');

    $insert->execute([
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'name' => ($name !== '') ? $name : null,
    ]);

    login_user((int)$pdo->lastInsertId());
    header('Location: /backend/app.php');
    exit;

} catch (Throwable $e) {
    error_log('Register failure: ' . $e->getMessage());
    $_SESSION['auth_error'] = 'Registration failed. Please try again.';
    header('Location: /backend/register.php');
    exit;
}