<?php

declare(strict_types=1);

return [
    'default' => env('CACHE_STORE', env('APP_ENV', 'local') === 'production' ? 'tiered' : 'local'),
    'prefix' => env_string('CACHE_PREFIX', 'infbyte:cache:'),
    'compression' => [
        'threshold_bytes' => env_int('CACHE_COMPRESSION_THRESHOLD_BYTES', 0) > 0
            ? env_int('CACHE_COMPRESSION_THRESHOLD_BYTES', 0)
            : null,
        'level' => env_int('CACHE_COMPRESSION_LEVEL', 6),
    ],
    'security' => [
        'integrity_key' => env('CACHE_INTEGRITY_KEY'),
        'max_payload_bytes' => env_int('CACHE_MAX_PAYLOAD_BYTES', 8_388_608),
    ],
    'serialization' => [
        'allow_closure_payloads' => env_bool('CACHE_ALLOW_CLOSURE_PAYLOADS', true),
        'allow_object_payloads' => env_bool('CACHE_ALLOW_OBJECT_PAYLOADS', true),
    ],
    'stores' => [
        'auth' => [
            'driver' => 'local',
            'path' => storage_path('cache/auth'),
        ],
        'file' => [
            'driver' => 'file',
            'path' => storage_path('cache/file'),
        ],
        'local' => [
            'driver' => 'local',
            'path' => storage_path('cache/local'),
        ],
        'memory' => [
            'driver' => 'memory',
        ],
        'null' => [
            'driver' => 'null',
        ],
        'php_files' => [
            'driver' => 'php_files',
            'path' => storage_path('cache/php-files'),
        ],
        'sqlite' => [
            'driver' => 'sqlite',
            'path' => env_string('CACHE_SQLITE_PATH', 'database/cachelayer.sqlite'),
            'table' => env_string('CACHE_SQLITE_TABLE', 'cachelayer_entries'),
            'lock' => [
                'driver' => 'pdo',
                'prefix' => env_string('CACHE_LOCK_PREFIX', 'infbyte:cache:lock:'),
            ],
        ],
        'database' => [
            'driver' => 'pdo',
            'connection' => env_string('CACHE_DB_CONNECTION', env_string('DB_CONNECTION', env_string('DB_DRIVER', 'sqlite'))),
            'table' => env_string('CACHE_TABLE', 'cachelayer_entries'),
            'lock' => [
                'driver' => 'pdo',
                'prefix' => env_string('CACHE_LOCK_PREFIX', 'infbyte:cache:lock:'),
            ],
        ],
        'redis' => [
            'driver' => 'redis',
            'dsn' => env_string('CACHE_REDIS_DSN', 'redis://127.0.0.1:6379'),
        ],
        'redis_cluster' => [
            'driver' => 'redis_cluster',
            'seeds' => array_values(array_filter(array_map(
                trim(...),
                explode(',', env_string('CACHE_REDIS_CLUSTER_SEEDS', '127.0.0.1:6379')),
            ))),
            'timeout' => env('CACHE_REDIS_CLUSTER_TIMEOUT', 1.0),
            'read_timeout' => env('CACHE_REDIS_CLUSTER_READ_TIMEOUT', 1.0),
            'persistent' => env_bool('CACHE_REDIS_CLUSTER_PERSISTENT', false),
        ],
        'valkey' => [
            'driver' => 'valkey',
            'dsn' => env_string('CACHE_VALKEY_DSN', 'valkey://127.0.0.1:6379'),
        ],
        'memcached' => [
            'driver' => 'memcached',
            'servers' => [
                [
                    'host' => env_string('CACHE_MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env_int('CACHE_MEMCACHED_PORT', 11211),
                    'weight' => env_int('CACHE_MEMCACHED_WEIGHT', 0),
                ],
            ],
        ],
        'shared_memory' => [
            'driver' => 'shared_memory',
            'segment_size' => env_int('CACHE_SHARED_MEMORY_SEGMENT_SIZE', 16_777_216),
        ],
        'weak_map' => [
            'driver' => 'weak_map',
        ],
        'tiered' => [
            'driver' => 'tiered',
            'write_to_l1' => env_bool('CACHE_TIERED_WRITE_TO_L1', true),
            'tiers' => [
                ['store' => 'memory'],
                ['store' => env_string('CACHE_TIERED_BACKING_STORE', 'sqlite')],
            ],
            'lock' => [
                'driver' => 'file',
                'path' => storage_path('cache/locks'),
                'retry_sleep_micros' => env_int('CACHE_LOCK_RETRY_SLEEP_MICROS', 50_000),
            ],
        ],
    ],
];
