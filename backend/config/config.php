<?php

declare(strict_types=1);

use Dotenv\Dotenv;

$rootPath = dirname(__DIR__);
$autoloadPath = $rootPath . '/vendor/autoload.php';

if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

if (class_exists(Dotenv::class) && file_exists($rootPath . '/.env')) {
    $dotenv = Dotenv::createImmutable($rootPath);
    $dotenv->safeLoad();
}

if (!function_exists('env_or_default')) {
    function env_or_default(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }
        return (string) $value;
    }
}

return [
    'app_url' => rtrim((string) env_or_default('APP_URL', 'http://localhost:8000'), '/'),
    'session_name' => env_or_default('SESSION_NAME', 'commvault_session'),
    'db' => [
        'host' => env_or_default('DB_HOST', '127.0.0.1'),
        'name' => env_or_default('DB_NAME', 'community-savings-pool'),
        'user' => env_or_default('DB_USER', 'root'),
        'pass' => env_or_default('DB_PASS', ''),
        'charset' => 'utf8mb4',
    ],
    'google' => [
        'client_id' => env_or_default('GOOGLE_CLIENT_ID', ''),
        'client_secret' => env_or_default('GOOGLE_CLIENT_SECRET', ''),
        'redirect_uri' => env_or_default('GOOGLE_REDIRECT_URI', ''),
    ],
];
