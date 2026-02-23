<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../config/database.php';

start_secure_session();
require_auth_json();

$userId = (int)$_SESSION['user_id'];
$poolId = (string)($_GET['pool_id'] ?? 'main');

$pdo = db();

/**
 * If you want global pool stats across all users, remove "AND user_id = :user_id"
 * For now: global pool stats for the pool_id (all users).
 */
$stmt = $pdo->prepare("
  SELECT
    COALESCE(SUM(CASE WHEN tx_type='deposit'  THEN amount_lovelace ELSE 0 END), 0) AS total_deposited,
    COALESCE(SUM(CASE WHEN tx_type='withdraw' THEN amount_lovelace ELSE 0 END), 0) AS total_withdrawn
  FROM pool_transactions
  WHERE pool_id = :pool_id
    AND status IN ('submitted','confirmed')
");
$stmt->execute([':pool_id' => $poolId]);
$row = $stmt->fetch() ?: ['total_deposited' => 0, 'total_withdrawn' => 0];

$totalDeposited = (int)$row['total_deposited'];
$totalWithdrawn = (int)$row['total_withdrawn'];
$poolBalance = $totalDeposited - $totalWithdrawn;

echo json_encode([
  'ok' => true,
  'pool_id' => $poolId,
  'total_deposited_lovelace' => $totalDeposited,
  'total_withdrawn_lovelace' => $totalWithdrawn,
  'pool_balance_lovelace' => $poolBalance,
]);
