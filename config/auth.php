<?php

declare(strict_types=1);

$defaultConnection = $_ENV['DB_CONNECTION'] ?? 'sqlite';
$cacheStore = $_ENV['CACHE_STORE'] ?? 'local';

return [
    'drivers' => [
        'storage' => $_ENV['AUTH_STORAGE'] ?? 'memory',
        'cache' => $_ENV['AUTH_CACHE'] ?? 'array',
        'passwords' => $_ENV['AUTH_PASSWORDS'] ?? 'native',
        'tokens' => $_ENV['AUTH_TOKENS'] ?? 'simple',
        'mfa' => $_ENV['AUTH_MFA'] ?? 'simple',
        'notifications' => $_ENV['AUTH_NOTIFICATIONS'] ?? 'collect',
        'passkey' => $_ENV['AUTH_PASSKEY'] ?? 'disabled',
    ],
    'token_secret' => $_ENV['AUTH_TOKEN_SECRET'] ?? 'foundation-dev-secret',
    'cachelayer' => [
        'store' => $_ENV['AUTH_CACHE_STORE'] ?? $cacheStore,
    ],
    'dblayer' => [
        'connection' => $_ENV['AUTH_DB_CONNECTION'] ?? $defaultConnection,
    ],
    'webauthn' => [
        'rp_id' => $_ENV['WEBAUTHN_RP_ID'] ?? null,
        'rp_name' => $_ENV['WEBAUTHN_RP_NAME'] ?? 'Infbyte',
        'origin' => $_ENV['WEBAUTHN_ORIGIN'] ?? null,
        'attestation' => 'none',
        'user_verification' => $_ENV['WEBAUTHN_USER_VERIFICATION'] ?? 'preferred',
        'resident_key' => $_ENV['WEBAUTHN_RESIDENT_KEY'] ?? 'preferred',
        'algorithms' => ['ES256', 'RS256'],
        'transports' => ['internal', 'hybrid', 'usb', 'nfc', 'ble'],
    ],
];
