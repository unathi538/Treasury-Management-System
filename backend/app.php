<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/auth.php';

$user = require_auth(false);
$csrf = generate_csrf_token();

$frontendFile = dirname(__DIR__) . '/frontend/features.html';
if (!file_exists($frontendFile)) {
    http_response_code(500);
    echo 'Frontend app page (new.html) not found.';
    exit;
}

$content = file_get_contents($frontendFile);
if ($content === false) {
    http_response_code(500);
    echo 'Unable to load protected app page.';
    exit;
}

$inject = sprintf(
    "<base href=\"/\">\n<meta name=\"current-user\" content='%s'>\n<meta name=\"csrf-token\" content='%s'>\n<div style=\"position:fixed;top:12px;right:12px;z-index:9999;background:#111;color:#fff;padding:10px;border-radius:8px;font-family:sans-serif;\">Logged in as %s <form style=\"display:inline\" method=\"POST\" action=\"/backend/auth/logout.php\"><input type=\"hidden\" name=\"csrf_token\" value=\"%s\"><button type=\"submit\">Logout</button></form></div>",
    htmlspecialchars(json_encode($user, JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8'),
    htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'),
    htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'),
    htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8')
);

$content = preg_replace('/<head>/', "<head>\n" . $inject, $content, 1);

echo $content;
