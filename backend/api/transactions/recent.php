<?php
declare(strict_types=1);

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../config/database.php';

start_secure_session();
require_auth_json();

$poolId = (string)($_GET['pool_id'] ?? 'main');
$limit = (int)($_GET['limit'] ?? 10);
if ($limit < 1) $limit = 10;
if ($limit > 50) $limit = 50;

$pdo = db();
$stmt = $pdo->prepare("
  SELECT id, user_id, tx_type, amount_lovelace, onchain_tx_hash, status, created_at
  FROM pool_transactions
  WHERE pool_id = :pool_id
  ORDER BY id DESC
  LIMIT {$limit}
");
$stmt->execute([':pool_id' => $poolId]);

echo json_encode([
  'ok' => true,
  'items' => $stmt->fetchAll(),
]);
