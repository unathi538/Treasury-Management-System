<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

$loggedIn = isset($_SESSION['treasurer_id']);

echo json_encode([
  'ok' => true,
  'logged_in' => $loggedIn,
  'treasurer' => $loggedIn ? [
    'id' => (int)$_SESSION['treasurer_id'],
    'email' => (string)($_SESSION['treasurer_email'] ?? ''),
    'full_name' => (string)($_SESSION['treasurer_name'] ?? ''),
  ] : null
]);