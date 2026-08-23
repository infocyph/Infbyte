<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Application Identity
    |--------------------------------------------------------------------------
    |
    | Infbyte owns application-facing defaults while Foundation owns the runtime
    | implementation. "name" labels diagnostics and integrations, "env" selects
    | environment-sensitive policy, "debug" enables development diagnostics,
    | and "url" is the application's canonical external base URL.
    |
    */
    'name' => env('APP_NAME', 'Infbyte'),
    'env' => env('APP_ENV', 'local'),
    'debug' => env_bool('APP_DEBUG', true),
    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Configuration Cache
    |--------------------------------------------------------------------------
    |
    | Sharded config remains the normal application default so namespaces stay
    | lazy. Build the selected artifact explicitly during deployment.
    | Allowed values: sharded|single.
    |
    */
    'config_cache' => [
        'type' => env('APP_CONFIG_CACHE_TYPE', 'sharded'),
    ],

    /*
    |--------------------------------------------------------------------------
    | InterMix Container
    |--------------------------------------------------------------------------
    |
    | Foundation 2.0 is lazy by default and owns execution scopes directly.
    | There is no application request_scope switch. Compiled container activation
    | remains opt-in until a deployment deliberately builds and enables it.
    |
    */
    'container' => [
        'alias' => env('APP_CONTAINER_ALIAS'),
        'environment' => env('APP_ENV', 'local'),
        'lazy_loading' => env_bool('APP_CONTAINER_LAZY_LOADING', true),
        'compiled' => env('APP_CONTAINER_COMPILED', 'bootstrap/cache/container.php'),
        'compiled_activation' => env('APP_CONTAINER_COMPILED_ACTIVATION', 'off'),
        'debug_tracing' => [
            'enabled' => env_bool('APP_CONTAINER_DEBUG_TRACING', false),
            'level' => env('APP_CONTAINER_DEBUG_TRACE_LEVEL', 'node'),
        ],
    ],
];
