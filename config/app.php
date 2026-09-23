<?php

declare(strict_types=1);

$environment = env_string('APP_ENV', 'local');
$production = $environment === 'production';
$capabilities = array_values(array_unique(array_filter(
    array_map(
        static fn(string $capability): string => trim($capability),
        explode(',', env_string('APP_CAPABILITIES', '')),
    ),
    static fn(string $capability): bool => $capability !== '',
)));

return [
    'name' => env_string('APP_NAME', 'Infbyte'),
    'env' => $environment,
    'debug' => env_bool('APP_DEBUG', !$production),
    'url' => env_string('APP_URL', 'http://localhost'),
    'capabilities' => $capabilities,

    'config_cache' => [
        'type' => env_string('APP_CONFIG_CACHE_TYPE', $production ? 'single' : 'sharded'),
    ],

    'container' => [
        'environment' => $environment,
        'lazy_loading' => env_bool('APP_CONTAINER_LAZY_LOADING', true),
        'debug_tracing' => [
            'enabled' => env_bool('APP_CONTAINER_DEBUG_TRACING', false),
            'level' => env_string('APP_CONTAINER_DEBUG_TRACE_LEVEL', 'node'),
        ],
    ],
];
