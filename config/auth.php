<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Drivers
    |--------------------------------------------------------------------------
    |
    | Each key selects the implementation for one authentication capability.
    | Storage: `memory|dblayer`. Cache: `array|cachelayer`. Passwords:
    | `native|epicrypt`. Tokens: `simple|epicrypt`. MFA: `simple|otp`.
    | Notifications: `collect|talkingbytes`. Passkeys:
    | `disabled|memory|webauthn`. Production defaults select durable,
    | security-oriented drivers while local defaults remain self-contained.
    |
    */
    'drivers' => [
        'storage' => env('AUTH_STORAGE', env('APP_ENV', 'local') === 'production' ? 'dblayer' : 'memory'),
        'cache' => env('AUTH_CACHE', env('APP_ENV', 'local') === 'production' ? 'cachelayer' : 'array'),
        'passwords' => env('AUTH_PASSWORDS', env('APP_ENV', 'local') === 'production' ? 'epicrypt' : 'native'),
        'tokens' => env('AUTH_TOKENS', env('APP_ENV', 'local') === 'production' ? 'epicrypt' : 'simple'),
        'mfa' => env('AUTH_MFA', env('APP_ENV', 'local') === 'production' ? 'otp' : 'simple'),
        'notifications' => env('AUTH_NOTIFICATIONS', env('APP_ENV', 'local') === 'production' ? 'talkingbytes' : 'collect'),
        'passkey' => env('AUTH_PASSKEY', 'disabled'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Signing Secret
    |--------------------------------------------------------------------------
    |
    | This secret protects authentication tokens. Replace the placeholder with
    | a unique high-entropy production secret and never commit the real value.
    | Example format: a randomly generated 32-or-more-byte Base64 string.
    |
    */
    'token_secret' => env(
        'AUTH_TOKEN_SECRET',
        env('APP_ENV', 'local') === 'production'
            ? 'replace-with-a-production-token-secret'
            : 'foundation-dev-secret',
    ),

    /*
    |--------------------------------------------------------------------------
    | Persistent Auth Adapters
    |--------------------------------------------------------------------------
    |
    | "cachelayer.store" names the cache store used for auth state and
    | lockouts. "dblayer.connection" names the database connection used by
    | persistent accounts, sessions, grants, and audit records. Store names are
    | keys from cache.stores, for example `auth`; connection examples are
    | `sqlite`, `mysql`, and `pgsql` from database.connections.
    |
    */
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

    /*
    |--------------------------------------------------------------------------
    | One-Time Passwords
    |--------------------------------------------------------------------------
    |
    | "issuer" is shown by authenticator applications. "freshness_window" is
    | the number of seconds an MFA result remains recent. TOTP "algorithm",
    | `digits`, `period`, and `secret_bytes` define provisioning parameters.
    | "window" is the accepted number of adjacent time steps.
    |
    | Recovery-code "count" and "length" control the generated backup set.
    | Replay protection records consumed codes when "enabled" and retains each
    | marker for "ttl" seconds. Keep replay protection enabled in production.
    |
    | Issuer example: `Acme`. Algorithm: `sha1|sha256|sha512`. Digits: `4..10`.
    | Period: `1..86400` seconds. Window: `0..100` time steps. Secret bytes,
    | recovery count/length, freshness, and replay TTL are positive integers.
    | Typical values respectively are 64, 10, 10, 900, and 90. Replay accepts
    | `true|false`.
    |
    */
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

    /*
    |--------------------------------------------------------------------------
    | WebAuthn / Passkeys
    |--------------------------------------------------------------------------
    |
    | "rp_id", "rp_name", and "origin" identify the relying party and must
    | match the deployed domain. `attestation` controls authenticator evidence.
    | "user_verification" and "resident_key" use WebAuthn preference values.
    | "algorithms" lists accepted COSE algorithms and "transports" lists the
    | authenticator transports advertised during registration.
    |
    | RP ID example: `example.com`; RP name example: `Acme`; origin example:
    | `https://example.com`. Attestation: `none|direct|indirect|enterprise`.
    | User verification and resident key: `required|preferred|discouraged`.
    | Algorithms: `ES256|RS256`. Transports: `internal|hybrid|usb|nfc|ble`.
    |
    */
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
