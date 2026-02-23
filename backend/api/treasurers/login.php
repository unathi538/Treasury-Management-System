<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if (session_status() !== PHP_SESSION_ACTIVE) {
  // IMPORTANT: prevents cookie issues on shared hosting
  session_start();
}

try {
  $raw = file_get_contents('php://input') ?: '';
  $in  = json_decode($raw, true);

  if (!is_array($in)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON body']);
    exit;
  }

  $email = strtolower(trim((string)($in['email'] ?? '')));
  $password = (string)($in['password'] ?? '');

  if ($email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Email and password are required']);
    exit;
  }

  $pdo = db();
  $stmt = $pdo->prepare("SELECT id, email, password_hash, full_name, is_active FROM treasurers WHERE email = ? LIMIT 1");
  $stmt->execute([$email]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row || (int)$row['is_active'] !== 1) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Invalid credentials']);
    exit;
  }

  if (!password_verify($password, (string)$row['password_hash'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Invalid credentials']);
    exit;
  }

  // Login success
  $_SESSION['treasurer_id'] = (int)$row['id'];
  $_SESSION['treasurer_email'] = (string)$row['email'];
  $_SESSION['treasurer_name'] = (string)($row['full_name'] ?? '');

  // optional audit
  $pdo->prepare("UPDATE treasurers SET last_login_at = NOW() WHERE id = ?")->execute([(int)$row['id']]);

  echo json_encode([
    'ok' => true,
    'treasurer' => [
      'id' => (int)$row['id'],
      'email' => (string)$row['email'],
      'full_name' => (string)($row['full_name'] ?? '')
    ]
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}