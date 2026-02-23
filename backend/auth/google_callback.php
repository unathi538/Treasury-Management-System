<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/google_oauth.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../config/database.php';

start_secure_session();

if (!empty($_GET['error'])) {
    $_SESSION['auth_error'] = 'Google sign-in was cancelled or failed. Please try again.';
    header('Location: /backend/login.php');
    exit;
}

$state = (string)($_GET['state'] ?? '');
$code  = (string)($_GET['code'] ?? '');

if ($state === '' || !hash_equals((string)($_SESSION['google_oauth_state'] ?? ''), $state)) {
    $_SESSION['auth_error'] = 'OAuth state validation failed.';
    header('Location: /backend/login.php');
    exit;
}

unset($_SESSION['google_oauth_state']);

if ($code === '') {
    $_SESSION['auth_error'] = 'Google authorization code missing.';
    header('Location: /backend/login.php');
    exit;
}

try {
    $profile = fetch_google_profile($code);

    if (!filter_var($profile['email'] ?? '', FILTER_VALIDATE_EMAIL) || empty($profile['google_id'])) {
        throw new RuntimeException('Invalid Google profile data.');
    }

    $pdo = db();
    $pdo->beginTransaction();

    // 1) Existing by google_id
    $stmtByGoogle = $pdo->prepare('SELECT id FROM users WHERE google_id = :google_id LIMIT 1');
    $stmtByGoogle->execute([':google_id' => $profile['google_id']]);
    $existingGoogle = $stmtByGoogle->fetch();

    if ($existingGoogle) {
        $userId = (int)$existingGoogle['id'];

        $update = $pdo->prepare(
            'UPDATE users
             SET name = :name,
                 avatar_url = :avatar_url,
                 email_verified_at = :email_verified_at
             WHERE id = :id'
        );
        $update->execute([
            ':name' => $profile['name'] ?: null,
            ':avatar_url' => $profile['avatar_url'] ?: null,
            ':email_verified_at' => ($profile['verified_email'] ? date('Y-m-d H:i:s') : null),
            ':id' => $userId,
        ]);
    } else {
        // 2) Existing by email
        $stmtByEmail = $pdo->prepare('SELECT id, google_id FROM users WHERE email = :email LIMIT 1');
        $stmtByEmail->execute([':email' => $profile['email']]);
        $existingEmail = $stmtByEmail->fetch();

        if ($existingEmail) {
            $userId = (int)$existingEmail['id'];

            // Link google_id if not linked
            if (empty($existingEmail['google_id'])) {
                $link = $pdo->prepare(
                    'UPDATE users
                     SET google_id = :google_id,
                         name = :name,
                         avatar_url = :avatar_url,
                         email_verified_at = :email_verified_at
                     WHERE id = :id'
                );
                $link->execute([
                    ':google_id' => $profile['google_id'],
                    ':name' => $profile['name'] ?: null,
                    ':avatar_url' => $profile['avatar_url'] ?: null,
                    ':email_verified_at' => ($profile['verified_email'] ? date('Y-m-d H:i:s') : null),
                    ':id' => $userId,
                ]);
            }
        } else {
            // 3) Create new
            $create = $pdo->prepare(
                'INSERT INTO users (email, google_id, name, avatar_url, email_verified_at, created_at)
                 VALUES (:email, :google_id, :name, :avatar_url, :email_verified_at, NOW())'
            );
            $create->execute([
                ':email' => $profile['email'],
                ':google_id' => $profile['google_id'],
                ':name' => $profile['name'] ?: null,
                ':avatar_url' => $profile['avatar_url'] ?: null,
                ':email_verified_at' => ($profile['verified_email'] ? date('Y-m-d H:i:s') : null),
            ]);
            $userId = (int)$pdo->lastInsertId();
        }
    }

    $pdo->commit();

    login_user($userId);
    header('Location: /backend/app.php');
    exit;

} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Google callback failure: ' . $e->getMessage());
    $_SESSION['auth_error'] = 'Google login failed. Please try again.';
    header('Location: /backend/login.php');
    exit;
}
