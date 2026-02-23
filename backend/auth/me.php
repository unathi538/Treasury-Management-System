<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';

$user = current_user();

if (!$user) {
    json_response(['ok' => false, 'error' => 'Unauthorized'], 401);
}

// Ensure wallet fields exist (re-fetch from DB if needed)
try {
    $stmt = db()->prepare('
        SELECT id, email, name, wallet_address, wallet_bound_at
        FROM users
        WHERE id = :id
        LIMIT 1
    ');
    $stmt->execute(['id' => (int)$user['id']]);
    $fresh = $stmt->fetch();

    if (!$fresh) {
        json_response(['ok' => false, 'error' => 'Unauthorized'], 401);
    }

    json_response(['ok' => true, 'user' => $fresh]);
} catch (Throwable $e) {
    error_log('me.php failure: ' . $e->getMessage());
    json_response(['ok' => false, 'error' => 'Server error'], 500);
}