<?php
declare(strict_types=1);

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../config/database.php';

start_secure_session();
require_auth_json(); // assumes this exists in lib/auth.php

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
  exit;
}

$raw = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);

if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid JSON body']);
  exit;
}

$txType = (string)($data['tx_type'] ?? '');
$amount = $data['amount_lovelace'] ?? null;
$txHash = (string)($data['onchain_tx_hash'] ?? '');
$poolId = (string)($data['pool_id'] ?? 'main');
$status = (string)($data['status'] ?? 'submitted');

// ✅ NEW: actor wallet address (sent from frontend)
$actorWallet = trim((string)($data['actor_wallet_address'] ?? ''));

if (!in_array($txType, ['deposit', 'withdraw'], true)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid tx_type']);
  exit;
}

if (!is_int($amount) && !is_string($amount)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid amount_lovelace']);
  exit;
}

// handle big strings safely-ish (still inserts as int; adjust DB to BIGINT if needed)
$amountInt = (int)$amount;
if ($amountInt <= 0) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Amount must be > 0']);
  exit;
}

if ($txHash !== '' && !preg_match('/^[0-9a-fA-F]{20,200}$/', $txHash)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid tx hash']);
  exit;
}

if (!in_array($status, ['submitted', 'confirmed', 'failed'], true)) {
  $status = 'submitted';
}

// ✅ validate actor wallet address if provided
if ($actorWallet !== '') {
  if (strlen($actorWallet) < 20 || !preg_match('/^(addr|addr_test)[a-z0-9]+$/i', $actorWallet)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid actor_wallet_address format']);
    exit;
  }
} else {
  $actorWallet = null; // store NULL when not provided
}

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
  exit;
}

try {
  $pdo = db();

  // ✅ IMPORTANT:
  // This INSERT assumes your pool_transactions table has the column:
  // actor_wallet_address VARCHAR(255) NULL
  // If it doesn't exist yet, run:
  // ALTER TABLE pool_transactions ADD COLUMN actor_wallet_address VARCHAR(255) NULL AFTER pool_id;

  $stmt = $pdo->prepare("
    INSERT INTO pool_transactions
      (user_id, pool_id, actor_wallet_address, tx_type, amount_lovelace, onchain_tx_hash, status)
    VALUES
      (:user_id, :pool_id, :actor_wallet_address, :tx_type, :amount_lovelace, :tx_hash, :status)
  ");

  $stmt->execute([
    ':user_id' => $userId,
    ':pool_id' => $poolId,
    ':actor_wallet_address' => $actorWallet,
    ':tx_type' => $txType,
    ':amount_lovelace' => $amountInt,
    ':tx_hash' => ($txHash !== '' ? $txHash : null),
    ':status' => $status,
  ]);

  echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
} catch (Throwable $e) {
  error_log('transactions/log.php error: ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Server error']);
}