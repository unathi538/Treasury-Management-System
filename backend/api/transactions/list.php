<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php'; // adjust if your db() is elsewhere
// If your db() is in a different path, change the line above to the correct file.
// Example alternatives you might have used before:
// require_once __DIR__ . '/../../config/db.php';
// require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');

try {
    $pdo = db();

    $poolId = trim((string)($_GET['pool_id'] ?? 'main'));
    $status = trim((string)($_GET['status'] ?? 'pending'));
    $limit  = (int)($_GET['limit'] ?? 50);

    if ($poolId === '') $poolId = 'main';

    $allowedStatus = ['pending','approved','rejected','all'];
    if (!in_array($status, $allowedStatus, true)) {
        $status = 'pending';
    }

    if ($limit < 1) $limit = 50;
    if ($limit > 200) $limit = 200;

    if ($status === 'all') {
        $sql = "
            SELECT
                id,
                user_id,
                pool_id,
                full_name,
                amount_ada,
                recipient_address,
                purpose_category,
                justification,
                status,
                created_at
            FROM withdrawal_requests
            WHERE pool_id = :pool_id
            ORDER BY id DESC
            LIMIT :limit
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':pool_id', $poolId, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $sql = "
            SELECT
                id,
                user_id,
                pool_id,
                full_name,
                amount_ada,
                recipient_address,
                purpose_category,
                justification,
                status,
                created_at
            FROM withdrawal_requests
            WHERE pool_id = :pool_id
              AND status = :status
            ORDER BY id DESC
            LIMIT :limit
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':pool_id', $poolId, PDO::PARAM_STR);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Optional: format a couple of fields for easier frontend use
    // (keep raw fields too)
    foreach ($rows as &$r) {
        $r['amount'] = (int)$r['amount_ada'];                 // frontend-friendly alias
        $r['category'] = $r['purpose_category'];             // alias
        $r['date'] = $r['created_at'];                       // alias
        $r['purpose'] = $r['purpose_category'];              // alias
    }
    unset($r);

    echo json_encode([
        'ok' => true,
        'pool_id' => $poolId,
        'status' => $status,
        'count' => count($rows),
        'requests' => $rows,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
    ]);
}