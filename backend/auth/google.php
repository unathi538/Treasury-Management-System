<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/google_oauth.php';

start_secure_session();

try {
    $client = google_client();
    $state = bin2hex(random_bytes(16));
    $_SESSION['google_oauth_state'] = $state;
    $client->setState($state);

    header('Location: ' . $client->createAuthUrl());
    exit;
} catch (Throwable $e) {
    error_log('Google OAuth init failure: ' . $e->getMessage());
    $_SESSION['auth_error'] = 'Google login is unavailable right now.';
    header('Location: /backend/login.php');
    exit;
}
