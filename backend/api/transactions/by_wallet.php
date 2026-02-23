<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

try {
  $pdo = db();

  $wallet = trim($_GET['wallet'] ?? '');
  $poolId  = trim($_GET['pool_id'] ?? 'main');
  $status  = strtolower(trim($_GET['status'] ?? '')); // optional: pending|approved|rejected

  if ($wallet === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'wallet is required']);
    exit;
  }

  $where = "pool_id = :pool_id AND recipient_address = :wallet";
  $params = [
    ':pool_id' => $poolId,
    ':wallet'  => $wallet
  ];

  if (in_array($status, ['pending','approved','rejected'], true)) {
    $where .= " AND status = :status";
    $params[':status'] = $status;
  }

  $sql = "
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
    WHERE {$where}
    ORDER BY created_at DESC
    LIMIT 200
  ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $items = array_map(function($r) {
    return [
      'type' => 'withdrawal_request',
      'id' => (string)$r['id'],
      'pool_id' => $r['pool_id'],
      'fullName' => $r['full_name'],
      'amountAda' => (int)$r['amount_ada'],
      'requesterAddress' => $r['recipient_address'], // requester == recipient in your app
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