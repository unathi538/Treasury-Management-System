<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

use Google\Client as GoogleClient;
use Google\Service\Oauth2 as GoogleOauth2;

function google_config(): array
{
    $config = require __DIR__ . '/../config/config.php';
    if (!is_array($config)) {
        throw new RuntimeException('Config did not return an array.');
    }

    $g = $config['google'] ?? [];
    $clientId = (string)($g['client_id'] ?? '');
    $clientSecret = (string)($g['client_secret'] ?? '');
    $redirectUri = (string)($g['redirect_uri'] ?? '');

    if ($clientId === '' || $clientSecret === '' || $redirectUri === '') {
        throw new RuntimeException(
            'Google OAuth is not configured. Check GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI.'
        );
    }

    return [$clientId, $clientSecret, $redirectUri];
}

function google_client(): GoogleClient
{
    // Ensure composer deps exist
    if (!class_exists(GoogleClient::class)) {
        throw new RuntimeException('Google Client library not found. Run composer require google/apiclient.');
    }

    [$clientId, $clientSecret, $redirectUri] = google_config();

    $client = new GoogleClient();
    $client->setClientId($clientId);
    $client->setClientSecret($clientSecret);
    $client->setRedirectUri($redirectUri);

    // OpenID Connect scopes (best practice)
    $client->setScopes(['openid', 'email', 'profile']);

    // Helps get refresh token sometimes; ok to keep
    $client->setAccessType('offline');
    $client->setPrompt('select_account');

    return $client;
}

/**
 * Exchange auth code for profile info.
 * Returns:
 *  [
 *    'email' => string,
 *    'google_id' => string,
 *    'name' => ?string,
 *    'avatar_url' => ?string,
 *    'verified_email' => bool
 *  ]
 */
function fetch_google_profile(string $code): array
{
    $client = google_client();

    $token = $client->fetchAccessTokenWithAuthCode($code);

    if (!is_array($token) || isset($token['error'])) {
        $msg = is_array($token) ? ($token['error_description'] ?? $token['error'] ?? 'Unknown token error') : 'Token error';
        throw new RuntimeException('Google token exchange failed: ' . $msg);
    }

    // Prefer id_token verification
    $idToken = $token['id_token'] ?? null;
    if (is_string($idToken) && $idToken !== '') {
        $payload = $client->verifyIdToken($idToken);
        if (is_array($payload)) {
            $email = (string)($payload['email'] ?? '');
            $sub = (string)($payload['sub'] ?? '');
            $name = isset($payload['name']) ? (string)$payload['name'] : null;
            $picture = isset($payload['picture']) ? (string)$payload['picture'] : null;
            $verified = (bool)($payload['email_verified'] ?? false);

            return [
                'email' => $email,
                'google_id' => $sub,
                'name' => $name,
                'avatar_url' => $picture,
                'verified_email' => $verified,
            ];
        }
    }

    // Fallback: userinfo endpoint via Google API
    $client->setAccessToken($token);
    $oauth2 = new GoogleOauth2($client);
    $me = $oauth2->userinfo->get();

    $email = (string)($me->getEmail() ?? '');
    $gid = (string)($me->getId() ?? '');

    return [
        'email' => $email,
        'google_id' => $gid,
        'name' => $me->getName() ?: null,
        'avatar_url' => $me->getPicture() ?: null,
        'verified_email' => (bool)($me->getVerifiedEmail() ?? false),
    ];
}
