<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Drivers
    |--------------------------------------------------------------------------
    |
    | Foundation 2.0 owns identity generation through UID, so authentication no
    | longer exposes an ID driver. These switches select only replaceable auth
    | capabilities. The Infbyte defaults stay dependency-light; production may
    | opt into the durable specialist modules it requires.
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
