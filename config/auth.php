<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Drivers
    |--------------------------------------------------------------------------
    |
    | Each key selects the implementation for one authentication capability.
    | IDs: `random|uid`. Storage: `memory|database`. Cache:
    | `array|cache`. Passwords: `native|security`. Tokens:
    | `simple|security`. MFA: `simple|otp`. Notifications:
    | `collect|talkingbytes`. Passkeys: `disabled|memory|webauthn`.
    |
    | The self-contained defaults keep auth adapters optional. Production auth
    | must install and explicitly select the durable modules it requires.
    |
    */
    'drivers' => [
        'ids' => env('AUTH_IDS', 'random'),
        'storage' => env('AUTH_STORAGE', 'memory'),
        'cache' => env('AUTH_CACHE', 'array'),
        'passwords' => env('AUTH_PASSWORDS', 'native'),
        'tokens' => env('AUTH_TOKENS', 'simple'),
        'mfa' => env('AUTH_MFA', 'simple'),
        'notifications' => env('AUTH_NOTIFICATIONS', 'collect'),
        'passkey' => env('AUTH_PASSKEY', 'disabled'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Signing Secret
    |--------------------------------------------------------------------------
    |
    | This secret protects authentication tokens. It has no config-file
    | default. Local runtime may supply its development-only fallback, while
    | production rejects missing or short values. Set AUTH_TOKEN_SECRET to a
    | unique high-entropy secret of at least 32 bytes and never commit it.
    | Example format: a randomly generated 64-character hexadecimal string.
    |
    */
    'token_secret' => env('AUTH_TOKEN_SECRET'),

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
