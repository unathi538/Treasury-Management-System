<?php
declare(strict_types=1);

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../config/database.php';

start_secure_session();
require_auth_json();

$userId = (int)$_SESSION['user_id'];
$poolId = (string)($_GET['pool_id'] ?? 'main');

$pdo = db();
$stmt = $pdo->prepare("
  SELECT id, tx_type, amount_lovelace, onchain_tx_hash, status, created_at
  FROM pool_transactions
  WHERE pool_id = :pool_id AND user_id = :user_id
  ORDER BY id DESC
  LIMIT 500
");
$stmt->execute([':pool_id' => $poolId, ':user_id' => $userId]);

echo json_encode([
  'ok' => true,
  'items' => $stmt->fetchAll(),
]);
