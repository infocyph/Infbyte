<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Drivers
    |--------------------------------------------------------------------------
    |
    | Foundation owns identity generation through UID. These switches select
    | only replaceable authentication capabilities. The dependency-light
    | defaults work without optional modules.
    |
    | Optional choices map to purpose-level modules:
    |   storage=database             -> module:install database
    |   cache=cache                  -> module:install cache
    |   passwords/tokens=security    -> module:install security
    |   mfa=otp or passkey=webauthn  -> module:install auth
    |   notifications=talkingbytes   -> module:install communication
    |
    | Module installation also synchronizes database schemas required by the
    | active configuration. Database-backed authentication uses the auth schema;
    | inspect or provision it explicitly with `module:schema:status auth` and
    | `module:schema:install auth` when preparing persistence ahead of activation.
    |
    | OTP and WebAuthn remain independently selectable even though they share
    | the same extended-auth module and installation bundle.
    |
    */
    'drivers' => [
        'storage' => env_string('AUTH_STORAGE', 'memory'),
        'cache' => env_string('AUTH_CACHE', 'array'),
        'passwords' => env_string('AUTH_PASSWORDS', 'native'),
        'tokens' => env_string('AUTH_TOKENS', 'simple'),
        'mfa' => env_string('AUTH_MFA', 'simple'),
        'notifications' => env_string('AUTH_NOTIFICATIONS', 'collect'),
        'passkey' => env_string('AUTH_PASSKEY', 'disabled'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Signing Secret
    |--------------------------------------------------------------------------
    |
    | app:install generates this value for a new application. Production must
    | use unique high-entropy secret material and must never commit it.
    |
    */
    'token_secret' => env('AUTH_TOKEN_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | One-Time Passwords
    |--------------------------------------------------------------------------
    |
    | OTP-backed MFA is provided by the auth module. Enable it with AUTH_MFA=otp
    | after installing the module. Replay-safe production use also requires the
    | configured shared cache/coordination capability.
    |
    */
    'otp' => [
        'issuer' => env_string('AUTH_OTP_ISSUER', env_string('APP_NAME', 'Infbyte')),
        'hotp' => [
            'look_ahead' => env_int('AUTH_OTP_HOTP_LOOK_AHEAD', 5),
        ],
        'totp' => [
            'algorithm' => env_string('AUTH_OTP_ALGORITHM', 'sha1'),
            'digits' => env_int('AUTH_OTP_DIGITS', 6),
            'period' => env_int('AUTH_OTP_PERIOD', 30),
            'secret_bytes' => env_int('AUTH_OTP_SECRET_BYTES', 20),
            'window' => env_int('AUTH_OTP_WINDOW', 1),
        ],
        'recovery_codes' => [
            'count' => env_int('AUTH_OTP_RECOVERY_CODES', 10),
            'length' => env_int('AUTH_OTP_RECOVERY_LENGTH', 12),
        ],
        'replay' => [
            'store' => env('AUTH_OTP_REPLAY_STORE'),
            'ttl' => env_int('AUTH_OTP_REPLAY_TTL', 90),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WebAuthn / Passkeys
    |--------------------------------------------------------------------------
    |
    | WebAuthn is provided by the auth module. Enable it with
    | AUTH_PASSKEY=webauthn after installing that module.
    |
    */
    'webauthn' => [
        'rp_id' => env('WEBAUTHN_RP_ID'),
        'rp_name' => env_string('WEBAUTHN_RP_NAME', env_string('APP_NAME', 'Infbyte')),
        'origin' => env('WEBAUTHN_ORIGIN'),
        'attestation' => env_string('WEBAUTHN_ATTESTATION', 'none'),
        'user_verification' => env_string('WEBAUTHN_USER_VERIFICATION', 'preferred'),
        'resident_key' => env_string('WEBAUTHN_RESIDENT_KEY', 'preferred'),
        'algorithms' => ['ES256', 'RS256'],
        'transports' => ['internal', 'hybrid', 'usb', 'nfc', 'ble'],
    ],
];
