<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

try {
  $pdo = db();

  $poolId = trim($_GET['pool_id'] ?? 'main');
  $limit  = (int)($_GET['limit'] ?? 5);
  if ($limit < 1) $limit = 5;
  if ($limit > 20) $limit = 20;

  $stmt = $pdo->prepare("
    SELECT
      id,
      pool_id,
      full_name,
      amount_ada,
      recipient_address,
      purpose_category,
      status,
      created_at
    FROM withdrawal_requests
    WHERE pool_id = :pool_id AND status = 'approved'
    ORDER BY created_at DESC
    LIMIT {$limit}
  ");
  $stmt->execute([':pool_id' => $poolId]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Normalize keys for frontend
  $items = array_map(function($r) {
    return [
      'id' => (string)$r['id'],
      'pool_id' => $r['pool_id'],
      'fullName' => $r['full_name'],
      'amountAda' => (int)$r['amount_ada'],
      'recipientAddress' => $r['recipient_address'],
      'category' => $r['purpose_category'],
      'status' => $r['status'],
      'createdAt' => $r['created_at'],
    ];
  }, $rows);

  echo json_encode(['ok' => true, 'items' => $items]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}