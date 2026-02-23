<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';

start_secure_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /backend/login.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
if (!verify_csrf_token(is_string($csrfToken) ? $csrfToken : null)) {
    http_response_code(419);
    echo 'Invalid CSRF token.';
    exit;
}

logout_user();
header('Location: /backend/login.php');
exit;