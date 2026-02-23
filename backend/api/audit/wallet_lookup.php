<?php
declare(strict_types=1);

require_once __DIR__ . '/../../lib/auth.php';

start_secure_session();

// OPTIONAL (recommended): ensure only treasurer can use this.
// If you already have treasurer auth, enforce it here.
// For now, require login:
$user = current_user();
if (!$user) json_response(['ok' => false, 'error' => 'Unauthorized'], 401);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$addr = trim((string)($_GET['address'] ?? ''));
if ($addr === '') json_response(['ok' => false, 'error' => 'address is required'], 400);

if (strlen($addr) < 20 || !preg_match('/^(addr|addr_test)[a-z0-9]+$/i', $addr)) {
  json_response(['ok' => false, 'error' => 'Invalid wallet address format'], 400);
}

$poolId = trim((string)($_GET['pool_id'] ?? 'main'));

try {
  $pdo = db();

  // 1) Find the registered user for this wallet
  $u = $pdo->prepare("
    SELECT id, email, name, wallet_address, wallet_bound_at, is_active, created_at
    FROM users
    WHERE wallet_address = :w
    LIMIT 1
  ");
  $u->execute(['w' => $addr]);
  $userRow = $u->fetch() ?: null;

  // 2) Fetch transactions for this wallet (requires actor_wallet_address in your tx table)
  // Change table name here if yours differs.
  $t = $pdo->prepare("
    SELECT tx_type, amount_lovelace, onchain_tx_hash, status, created_at
    FROM pool_transactions
    WHERE pool_id = :pool_id
      AND actor_wallet_address = :w
    ORDER BY created_at DESC
    LIMIT 200
  ");
  $t->execute(['pool_id' => $poolId, 'w' => $addr]);
  $txs = $t->fetchAll() ?: [];

  // 3) Deposit stats (how active is this member)
  $s = $pdo->prepare("
    SELECT
      COUNT(*) AS total_txs,
      SUM(CASE WHEN tx_type='deposit' THEN 1 ELSE 0 END) AS deposit_count,
      SUM(CASE WHEN tx_type='withdraw' THEN 1 ELSE 0 END) AS withdraw_count,
      COALESCE(SUM(CASE WHEN tx_type='deposit' THEN CAST(amount_lovelace AS SIGNED) ELSE 0 END), 0) AS total_deposit_lovelace,
      COALESCE(SUM(CASE WHEN tx_type='withdraw' THEN CAST(amount_lovelace AS SIGNED) ELSE 0 END), 0) AS total_withdraw_lovelace,
      MAX(CASE WHEN tx_type='deposit' THEN created_at ELSE NULL END) AS last_deposit_at
    FROM pool_transactions
    WHERE pool_id = :pool_id
      AND actor_wallet_address = :w
  ");
  $s->execute(['pool_id' => $poolId, 'w' => $addr]);
  $stats = $s->fetch() ?: [];

  json_response([
    'ok' => true,
    'address' => $addr,
    'pool_id' => $poolId,
    'is_registered' => (bool)$userRow,
    'user' => $userRow,
    'member_active' => $userRow ? ((int)$userRow['is_active'] === 1) : false,
    'stats' => [
      'total_txs' => (int)($stats['total_txs'] ?? 0),
      'deposit_count' => (int)($stats['deposit_count'] ?? 0),
      'withdraw_count' => (int)($stats['withdraw_count'] ?? 0),
      'total_deposit_lovelace' => (string)($stats['total_deposit_lovelace'] ?? '0'),
      'total_withdraw_lovelace' => (string)($stats['total_withdraw_lovelace'] ?? '0'),
      'last_deposit_at' => $stats['last_deposit_at'] ?? null,
    ],
    'transactions' => array_map(function($r) {
      return [
        'type' => $r['tx_type'],
        'amount_lovelace' => (string)$r['amount_lovelace'],
        'txHash' => $r['onchain_tx_hash'] ?? null,
        'status' => $r['status'] ?? null,
        'date' => $r['created_at'] ?? null,
      ];
    }, $txs),
  ]);

} catch (Throwable $e) {
  error_log("wallet_lookup error: " . $e->getMessage());
  json_response(['ok' => false, 'error' => 'Server error'], 500);
}