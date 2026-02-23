<?php
declare(strict_types=1);

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../config/database.php';

start_secure_session();
require_auth_json(); // must be logged in

header('Content-Type: application/json');

try {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '[]', true, 512, JSON_THROW_ON_ERROR);

    $poolId = isset($data['pool_id']) ? trim((string)$data['pool_id']) : 'main';
    $fullName = trim((string)($data['fullName'] ?? ''));
    $amount = (string)($data['amount'] ?? '');
    $addr = trim((string)($data['addr'] ?? ''));
    $category = trim((string)($data['category'] ?? ''));
    $reason = trim((string)($data['reason'] ?? ''));

    if ($fullName === '' || $amount === '' || $addr === '' || $category === '' || $reason === '') {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Missing required fields']);
        exit;
    }

    $amountAda = (int)$amount;
    if ($amountAda <= 0) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Invalid amount']);
        exit;
    }

    $userId = (int)$_SESSION['user_id'];

    $pdo = db();
    $stmt = $pdo->prepare("
      INSERT INTO withdrawal_requests
        (user_id, pool_id, full_name, amount_ada, recipient_address, purpose_category, justification, status)
      VALUES
        (:user_id, :pool_id, :full_name, :amount_ada, :recipient_address, :purpose_category, :justification, 'pending')
    ");

    $stmt->execute([
        'user_id' => $userId,
        'pool_id' => $poolId,
        'full_name' => $fullName,
        'amount_ada' => $amountAda,
        'recipient_address' => $addr,
        'purpose_category' => $category,
        'justification' => $reason,
    ]);

    echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
} catch (Throwable $e) {
    error_log("withdraw_requests/create.php error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error']);
}