<?php

declare(strict_types=1);

return [
    'drivers' => [
        'storage' => env('AUTH_STORAGE', env('APP_ENV', 'local') === 'production' ? 'dblayer' : 'memory'),
        'cache' => env('AUTH_CACHE', env('APP_ENV', 'local') === 'production' ? 'cachelayer' : 'array'),
        'passwords' => env('AUTH_PASSWORDS', env('APP_ENV', 'local') === 'production' ? 'epicrypt' : 'native'),
        'tokens' => env('AUTH_TOKENS', env('APP_ENV', 'local') === 'production' ? 'epicrypt' : 'simple'),
        'mfa' => env('AUTH_MFA', env('APP_ENV', 'local') === 'production' ? 'otp' : 'simple'),
        'notifications' => env('AUTH_NOTIFICATIONS', env('APP_ENV', 'local') === 'production' ? 'talkingbytes' : 'collect'),
        'passkey' => env('AUTH_PASSKEY', 'disabled'),
    ],
    'token_secret' => env(
        'AUTH_TOKEN_SECRET',
        env('APP_ENV', 'local') === 'production'
            ? 'replace-with-a-production-token-secret'
            : 'foundation-dev-secret',
    ),
    'cachelayer' => [
        'store' => env(
            'AUTH_CACHE_STORE',
            env('APP_ENV', 'local') === 'production'
                ? 'auth'
                : env('CACHE_STORE', 'local'),
        ),
    ],
    'dblayer' => [
        'connection' => env('AUTH_DB_CONNECTION', env('DB_CONNECTION', env('DB_DRIVER', 'sqlite'))),
    ],
    'otp' => [
        'issuer' => env('AUTH_OTP_ISSUER', env('APP_NAME', 'Infbyte')),
        'freshness_window' => env_int('AUTH_OTP_FRESHNESS_WINDOW', 900),
        'totp' => [
            'algorithm' => env('AUTH_OTP_ALGORITHM', 'sha1'),
            'digits' => env_int('AUTH_OTP_DIGITS', 6),
            'period' => env_int('AUTH_OTP_PERIOD', 30),
            'secret_bytes' => env_int('AUTH_OTP_SECRET_BYTES', 64),
            'window' => env_int('AUTH_OTP_WINDOW', 1),
        ],
        'recovery_codes' => [
            'count' => env_int('AUTH_OTP_RECOVERY_CODES', 10),
            'length' => env_int('AUTH_OTP_RECOVERY_LENGTH', 10),
        ],
        'replay' => [
            'enabled' => env('AUTH_OTP_REPLAY', true),
            'ttl' => env_int('AUTH_OTP_REPLAY_TTL', 90),
        ],
    ],
    'webauthn' => [
        'rp_id' => env('WEBAUTHN_RP_ID'),
        'rp_name' => env('WEBAUTHN_RP_NAME', 'Infbyte'),
        'origin' => env('WEBAUTHN_ORIGIN'),
        'attestation' => 'none',
        'user_verification' => env('WEBAUTHN_USER_VERIFICATION', 'preferred'),
        'resident_key' => env('WEBAUTHN_RESIDENT_KEY', 'preferred'),
        'algorithms' => ['ES256', 'RS256'],
        'transports' => ['internal', 'hybrid', 'usb', 'nfc', 'ble'],
    ],
];
