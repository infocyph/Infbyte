<?php

declare(strict_types=1);

return [
    'default' => env('IDS_DEFAULT', 'uuid7'),
    'sequence' => [
        'driver' => env('IDS_SEQUENCE_DRIVER', 'filesystem'),
        'directory' => env_string('IDS_SEQUENCE_DIRECTORY', 'cache/ids'),
        'wait_time' => env_int('IDS_SEQUENCE_WAIT_TIME', 1000),
        'max_attempts' => env_int('IDS_SEQUENCE_MAX_ATTEMPTS', 1000),
    ],
    'ulid' => [
        'mode' => env('IDS_ULID_MODE', 'monotonic'),
    ],
    'nanoid' => [
        'length' => env_int('IDS_NANOID_LENGTH', 21),
    ],
    'cuid2' => [
        'length' => env_int('IDS_CUID2_LENGTH', 24),
    ],
    'opaque' => [
        'length' => env_int('IDS_OPAQUE_LENGTH', 12),
        'salt' => env_string('IDS_OPAQUE_SALT', ''),
    ],
    'deterministic' => [
        'length' => env_int('IDS_DETERMINISTIC_LENGTH', 24),
        'namespace' => env_string('IDS_DETERMINISTIC_NAMESPACE', 'default'),
    ],
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
