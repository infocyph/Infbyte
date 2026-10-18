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
    | Foundation 4 groups optional capabilities into qualified features:
    |   storage=database             -> data.database
    |   passwords/tokens=security    -> security.crypto
    |   mfa=otp                      -> security.mfa + security.crypto
    |   passkey=webauthn             -> security.passkey
    |   notifications=talkingbytes  -> execution.communication
    |
    | CacheLayer is Foundation Core, never an optional module. Install each
    | selected feature explicitly and enable it in config/modules.php.
    | Installing a package does not implicitly enable that feature.
    | Core auth/session schema is managed by Foundation, not by the
    | optional module schema commands. Optional schema belongs to its
    | selected specialist.
    |
    */
    'drivers' => [
        'storage' => env_string('AUTH_STORAGE', 'memory'),
        'cache' => env_string('AUTH_CACHE', 'array'),
        'passwords' => env_string('AUTH_PASSWORDS', 'native'),
        'tokens' => env_string('AUTH_TOKENS', 'simple'),
        'mfa' => env_string('AUTH_MFA', 'simple'),
        'notifications' => env_string('AUTH_NOTIFICATIONS', 'collect'),
        'passkey' => env_string('AUTH_PASSKEY', 'memory'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Signing Secret
    |--------------------------------------------------------------------------
    |
    | app:install generates AUTH_TOKEN_SECRET for a new application. Foundation
    | resolves it by environment name so the raw secret never enters compiled
    | application configuration. Override only the environment-variable name.
    |
    */
    'token_secret_environment' => env_string('AUTH_TOKEN_SECRET_ENVIRONMENT', 'AUTH_TOKEN_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | One-Time Passwords
    |--------------------------------------------------------------------------
    |
    | OTP-backed MFA selects security.mfa plus security.crypto. Enable it
    | explicitly before setting AUTH_MFA=otp. Replay-safe production use also requires the
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
    | WebAuthn requires security.passkey. Select and enable that feature
    | explicitly before setting AUTH_PASSKEY=webauthn.
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
