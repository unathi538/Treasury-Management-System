<?php
declare(strict_types=1);

// Adjust this include to wherever your db() function is
require_once __DIR__ . '/../../config/database.php'; 
// If yours is different, use the correct path, e.g.
// require_once __DIR__ . '/../../config.php';
// require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

try {
  $pdo = db();

  // Accept JSON body or form POST
  $raw = file_get_contents('php://input');
  $json = json_decode($raw ?: '', true);

  $id = (int)($_POST['id'] ?? ($json['id'] ?? 0));
  $status = trim((string)($_POST['status'] ?? ($json['status'] ?? '')));
  $poolId = trim((string)($_POST['pool_id'] ?? ($json['pool_id'] ?? 'main')));

  if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing/invalid id']);
    exit;
  }

  $allowed = ['approved', 'rejected'];
  if (!in_array($status, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid status']);
    exit;
  }

  if ($poolId === '') $poolId = 'main';

  // Only update if currently pending (prevents double-approval)
  $stmt = $pdo->prepare("
    UPDATE withdrawal_requests
    SET status = :status
    WHERE id = :id AND pool_id = :pool_id AND status = 'pending'
  ");
  $stmt->execute([
    ':status' => $status,
    ':id' => $id,
    ':pool_id' => $poolId,
  ]);

  if ($stmt->rowCount() === 0) {
    echo json_encode([
      'ok' => false,
      'error' => 'No pending request updated (maybe already processed or wrong id/pool_id).'
    ]);
    exit;
  }

  echo json_encode([
    'ok' => true,
    'id' => $id,
    'pool_id' => $poolId,
    'status' => $status
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}