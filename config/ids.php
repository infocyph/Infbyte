<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Identifier
    |--------------------------------------------------------------------------
    |
    | This value selects the identifier generator used when a caller does not
    | request a strategy explicitly. UUIDv7 is time ordered and broadly useful.
    | Values: `cuid2|deterministic|ksuid|nanoid|opaque|randflake|`
    | `randflake_string|snowflake|sonyflake|tbsl|ulid|uuid|uuid1|uuid4|`
    | `uuid6|uuid7|uuid8|xid`.
    |
    */
    'default' => env('IDS_DEFAULT', 'uuid7'),

    /*
    |--------------------------------------------------------------------------
    | Shared Sequence Coordination
    |--------------------------------------------------------------------------
    |
    | "driver" selects the sequence backend and "directory" stores filesystem
    | sequence state. "wait_time" is the lock retry delay in microseconds and
    | "max_attempts" bounds acquisition attempts. Distributed deployments must
    | use sequence state that is shared by every participating node.
    | Drivers: `file|filesystem|memory`. Directory example: `cache/ids`.
    | Wait/max-attempt examples: `1000` microseconds and `1000` attempts.
    |
    */
    'sequence' => [
        'driver' => env('IDS_SEQUENCE_DRIVER', 'filesystem'),
        'directory' => env_string('IDS_SEQUENCE_DIRECTORY', 'cache/ids'),
        'wait_time' => env_int('IDS_SEQUENCE_WAIT_TIME', 1000),
        'max_attempts' => env_int('IDS_SEQUENCE_MAX_ATTEMPTS', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | ULID, NanoID And CUID2
    |--------------------------------------------------------------------------
    |
    | ULID "mode" controls random or monotonic ordering. NanoID and CUID2
    | "length" values control their textual output size and collision budget.
    | ULID modes: `monotonic|random`. NanoID length: `1..1048576`, example `21`.
    | CUID2 length: `2..32`, example `24`.
    |
    */
    'ulid' => [
        'mode' => env('IDS_ULID_MODE', 'monotonic'),
    ],
    'nanoid' => [
        'length' => env_int('IDS_NANOID_LENGTH', 21),
    ],
    'cuid2' => [
        'length' => env_int('IDS_CUID2_LENGTH', 24),
    ],

    /*
    |--------------------------------------------------------------------------
    | Opaque And Deterministic Identifiers
    |--------------------------------------------------------------------------
    |
    | Opaque IDs use "length" for output size and "salt" for application-level
    | separation. Deterministic IDs use "length" and "namespace" to keep equal
    | inputs isolated by purpose. Use high-entropy secrets where the selected
    | generator treats a salt as confidential. Opaque length: `1..1024`, example
    | `12`; salt example: a random application-specific string. Deterministic
    | length: `1..43`, example `24`; namespace example: `invoice`.
    |
    */
    'opaque' => [
        'length' => env_int('IDS_OPAQUE_LENGTH', 12),
        'salt' => env_string('IDS_OPAQUE_SALT', ''),
    ],
    'deterministic' => [
        'length' => env_int('IDS_DETERMINISTIC_LENGTH', 24),
        'namespace' => env_string('IDS_DETERMINISTIC_NAMESPACE', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Snowflake Identifiers
    |--------------------------------------------------------------------------
    |
    | "datacenter_id" and "worker_id" must uniquely identify the generator.
    | "custom_epoch" sets the timestamp origin, "clock_backward_policy" controls
    | rollback handling, and "output" selects the returned representation.
    | Sequence "driver" and "directory" override the shared defaults above.
    | Datacenter/worker examples: `3` and `17`; epoch examples: Unix milliseconds
    | or `2024-01-01T00:00:00Z`. Clock policy: `wait|throw`. Output:
    | `string|int|binary`. Sequence drivers: `file|filesystem|memory`.
    |
    */
    'snowflake' => [
        'datacenter_id' => env_int('IDS_SNOWFLAKE_DATACENTER_ID', 0),
        'worker_id' => env_int('IDS_SNOWFLAKE_WORKER_ID', 0),
        'custom_epoch' => env('IDS_SNOWFLAKE_CUSTOM_EPOCH'),
        'clock_backward_policy' => env('IDS_SNOWFLAKE_CLOCK_BACKWARD_POLICY', 'wait'),
        'output' => env('IDS_SNOWFLAKE_OUTPUT', 'string'),
        'sequence' => [
            'driver' => env('IDS_SNOWFLAKE_SEQUENCE_DRIVER', env('IDS_SEQUENCE_DRIVER', 'filesystem')),
            'directory' => env_string('IDS_SNOWFLAKE_SEQUENCE_DIRECTORY', env_string('IDS_SEQUENCE_DIRECTORY', 'cache/ids')),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sonyflake Identifiers
    |--------------------------------------------------------------------------
    |
    | "machine_id" must be unique among active generators. "custom_epoch",
    | "clock_backward_policy", and "output" control time origin, rollback
    | handling, and representation. Sequence keys override shared coordination.
    | Machine example: `42`; epoch example: `2024-01-01T00:00:00Z`; clock policy:
    | `wait|throw`; output: `string|int|binary`; sequence driver:
    | `file|filesystem|memory`; directory example: `cache/ids`.
    |
    */
    'sonyflake' => [
        'machine_id' => env_int('IDS_SONYFLAKE_MACHINE_ID', 0),
        'custom_epoch' => env('IDS_SONYFLAKE_CUSTOM_EPOCH'),
        'clock_backward_policy' => env('IDS_SONYFLAKE_CLOCK_BACKWARD_POLICY', 'wait'),
        'output' => env('IDS_SONYFLAKE_OUTPUT', 'string'),
        'sequence' => [
            'driver' => env('IDS_SONYFLAKE_SEQUENCE_DRIVER', env('IDS_SEQUENCE_DRIVER', 'filesystem')),
            'directory' => env_string('IDS_SONYFLAKE_SEQUENCE_DIRECTORY', env_string('IDS_SEQUENCE_DIRECTORY', 'cache/ids')),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | TBSL Identifiers
    |--------------------------------------------------------------------------
    |
    | "machine_id" identifies the node, "sequenced" enables coordinated values,
    | "clock_backward_policy" defines rollback behavior, and "output" selects
    | representation. Sequence "driver" and "directory" are profile overrides.
    | A machine ID may be 42. Sequenced accepts `true|false`; clock policy accepts
    | `wait|throw`; output accepts `string|int|binary`; and sequence driver accepts
    | `file|filesystem|memory`. A sequence directory may be `cache/ids`.
    |
    */
    'tbsl' => [
        'machine_id' => env_int('IDS_TBSL_MACHINE_ID', 0),
        'sequenced' => env_bool('IDS_TBSL_SEQUENCED', false),
        'clock_backward_policy' => env('IDS_TBSL_CLOCK_BACKWARD_POLICY', 'wait'),
        'output' => env('IDS_TBSL_OUTPUT', 'string'),
        'sequence' => [
            'driver' => env('IDS_TBSL_SEQUENCE_DRIVER', env('IDS_SEQUENCE_DRIVER', 'filesystem')),
            'directory' => env_string('IDS_TBSL_SEQUENCE_DIRECTORY', env_string('IDS_SEQUENCE_DIRECTORY', 'cache/ids')),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Randflake Identifiers
    |--------------------------------------------------------------------------
    |
    | "node_id" identifies the generator. "lease_start" and "lease_end" bound
    | its allocated range; "secret" protects generator-specific derivation and
    | must be replaced in production. "output" selects representation, while
    | sequence "driver" and "directory" override shared coordination settings.
    | Node example: `7`; lease example: `100000..199999`; secret example: a
    | random 32-byte Base64 string; output: `string|int|binary`; sequence driver:
    | `file|filesystem|memory`; directory example: `cache/ids`.
    |
    */
    'randflake' => [
        'node_id' => env_int('IDS_RANDFLAKE_NODE_ID', 0),
        'lease_start' => env_int('IDS_RANDFLAKE_LEASE_START', 0),
        'lease_end' => env_int('IDS_RANDFLAKE_LEASE_END', 0),
        'secret' => env_string('IDS_RANDFLAKE_SECRET', 'change-me'),
        'output' => env('IDS_RANDFLAKE_OUTPUT', 'string'),
        'sequence' => [
            'driver' => env('IDS_RANDFLAKE_SEQUENCE_DRIVER', env('IDS_SEQUENCE_DRIVER', 'filesystem')),
            'directory' => env_string('IDS_RANDFLAKE_SEQUENCE_DIRECTORY', env_string('IDS_SEQUENCE_DIRECTORY', 'cache/ids')),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Identifier Purposes
    |--------------------------------------------------------------------------
    |
    | Each key selects the generator for that auth record type: accounts, audit
    | events, challenges, correlations, credentials, devices, grants,
    | permissions, roles, and sessions. Keep persisted types stable after data
    | exists unless a migration explicitly handles the representation change.
    | Every value accepts one of the generator names listed under Default
    | Identifier. Example: account `uuid7`, correlation `ulid`, session `uuid7`.
    |
    */
    'auth' => [
        'account' => env('IDS_AUTH_ACCOUNT', 'uuid7'),
        'audit_event' => env('IDS_AUTH_AUDIT_EVENT', 'uuid7'),
        'challenge' => env('IDS_AUTH_CHALLENGE', 'uuid7'),
        'correlation' => env('IDS_AUTH_CORRELATION', 'ulid'),
        'credential' => env('IDS_AUTH_CREDENTIAL', 'uuid7'),
        'device' => env('IDS_AUTH_DEVICE', 'uuid7'),
        'grant' => env('IDS_AUTH_GRANT', 'uuid7'),
        'permission' => env('IDS_AUTH_PERMISSION', 'uuid7'),
        'role' => env('IDS_AUTH_ROLE', 'uuid7'),
        'session' => env('IDS_AUTH_SESSION', 'uuid7'),
    ],
];
