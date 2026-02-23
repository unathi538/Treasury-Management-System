<?php

declare(strict_types=1);

require_once __DIR__ . '/../../lib/auth.php';

$user = require_auth(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$csrfToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
if (!verify_csrf_token(is_string($csrfToken) ? $csrfToken : null)) {
    json_response(['error' => 'Invalid CSRF token'], 419);
}

$poolId = trim((string) ($_POST['pool_id'] ?? ''));
$txType = trim((string) ($_POST['tx_type'] ?? ''));
$amountRaw = (string) ($_POST['amount'] ?? '');
$currency = strtoupper(trim((string) ($_POST['currency'] ?? 'ADA')));
$onchainTxHash = trim((string) ($_POST['onchain_tx_hash'] ?? ''));
$status = trim((string) ($_POST['status'] ?? 'pending'));
$metadataJson = $_POST['metadata_json'] ?? null;

$allowedTypes = ['deposit', 'withdraw', 'transfer'];
$allowedStatus = ['pending', 'confirmed', 'failed'];

if ($poolId === '' || strlen($poolId) > 64) {
    json_response(['error' => 'pool_id is required and must be <= 64 chars'], 422);
}

if (!in_array($txType, $allowedTypes, true)) {
    json_response(['error' => 'Invalid tx_type'], 422);
}

if (!is_numeric($amountRaw) || (float) $amountRaw <= 0) {
    json_response(['error' => 'Amount must be a positive number'], 422);
}

if (!in_array($status, $allowedStatus, true)) {
    json_response(['error' => 'Invalid status'], 422);
}

if ($metadataJson !== null && $metadataJson !== '' && json_decode((string) $metadataJson, true) === null && json_last_error() !== JSON_ERROR_NONE) {
    json_response(['error' => 'metadata_json must be valid JSON'], 422);
}

try {
    $stmt = db()->prepare('INSERT INTO pool_transactions (user_id, pool_id, tx_type, amount, currency, onchain_tx_hash, status, metadata_json) VALUES (:user_id, :pool_id, :tx_type, :amount, :currency, :onchain_tx_hash, :status, :metadata_json)');
    $stmt->execute([
        'user_id' => $user['id'],
        'pool_id' => $poolId,
        'tx_type' => $txType,
        'amount' => number_format((float) $amountRaw, 8, '.', ''),
        'currency' => $currency !== '' ? substr($currency, 0, 10) : 'ADA',
        'onchain_tx_hash' => $onchainTxHash !== '' ? substr($onchainTxHash, 0, 120) : null,
        'status' => $status,
        'metadata_json' => $metadataJson !== '' ? $metadataJson : null,
    ]);

    json_response(['success' => true, 'transaction_id' => (int) db()->lastInsertId()], 201);
} catch (Throwable $e) {
    error_log('Create transaction failure: ' . $e->getMessage());
    json_response(['error' => 'Unable to create transaction'], 500);
}
