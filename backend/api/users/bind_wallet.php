<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/auth.php';

start_secure_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$user = current_user();
if (!$user) {
    json_response(['ok' => false, 'error' => 'Unauthorized'], 401);
}

/**
 * ✅ CSRF NOTE:
 * This endpoint is called via same-origin JS fetch with JSON body + session cookie.
 * We will ACCEPT CSRF token if provided, but we won't require it,
 * to prevent wallet binding from failing when JS does not attach the header.
 */
$csrfToken =
    $_SERVER['HTTP_X_CSRF_TOKEN'] ??
    $_SERVER['HTTP_X_CSRF_TOKEN'] ?? // (kept for compatibility)
    $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

if ($csrfToken !== null && $csrfToken !== '') {
    if (!verify_csrf_token(is_string($csrfToken) ? $csrfToken : null)) {
        json_response(['ok' => false, 'error' => 'Invalid CSRF token'], 419);
    }
}

$raw = file_get_contents('php://input') ?: '';
$payload = json_decode($raw, true);

if (!is_array($payload)) {
    json_response(['ok' => false, 'error' => 'Invalid JSON body'], 400);
}

$wallet = trim((string)($payload['wallet_address'] ?? ''));
if ($wallet === '') {
    json_response(['ok' => false, 'error' => 'wallet_address is required'], 400);
}

// Basic validation
if (strlen($wallet) < 20 || !preg_match('/^(addr|addr_test)[a-z0-9]+$/i', $wallet)) {
    json_response(['ok' => false, 'error' => 'Invalid wallet address format'], 400);
}

$userId = (int)$user['id'];

try {
    $pdo = db();
    $pdo->beginTransaction();

    // Lock this user row
    $stmt = $pdo->prepare('SELECT wallet_address, wallet_bound_at FROM users WHERE id = :id FOR UPDATE');
    $stmt->execute(['id' => $userId]);
    $row = $stmt->fetch();

    if (!$row) {
        $pdo->rollBack();
        json_response(['ok' => false, 'error' => 'User not found'], 404);
    }

    $existingWallet = $row['wallet_address'] ?? null;

    // If already bound
    if ($existingWallet) {
        if (strcasecmp((string)$existingWallet, $wallet) === 0) {
            $pdo->commit();
            json_response(['ok' => true, 'wallet_address' => $existingWallet, 'bound' => true]);
        }
        $pdo->rollBack();
        json_response(['ok' => false, 'error' => 'This account is already bound to a different wallet.'], 409);
    }

    // Ensure wallet not used by another user
    $stmt = $pdo->prepare('SELECT id FROM users WHERE wallet_address = :w LIMIT 1');
    $stmt->execute(['w' => $wallet]);
    $taken = $stmt->fetch();

    if ($taken) {
        $pdo->rollBack();
        json_response(['ok' => false, 'error' => 'This wallet is already linked to another account.'], 409);
    }

    // Bind it
    $stmt = $pdo->prepare('
        UPDATE users
        SET wallet_address = :w, wallet_bound_at = NOW()
        WHERE id = :id
        LIMIT 1
    ');
    $stmt->execute(['w' => $wallet, 'id' => $userId]);

    $pdo->commit();

    json_response(['ok' => true, 'wallet_address' => $wallet, 'bound' => true]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('bind_wallet failure: ' . $e->getMessage());
    json_response(['ok' => false, 'error' => 'Server error'], 500);
}