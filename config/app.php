<?php

declare(strict_types=1);

$environment = env_string('APP_ENV', 'local');
$production = $environment === 'production';

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
    'name' => env_string('APP_NAME', 'Infbyte'),
    'env' => $environment,
    'debug' => env_bool('APP_DEBUG', !$production),
    'url' => env_string('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Configuration Cache
    |--------------------------------------------------------------------------
    |
    | Production uses one precomputed artifact to minimize request bootstrap.
    | Other environments keep namespaces lazy with sharded configuration.
    | Build the selected artifact during deployment. Allowed: sharded|single.
    |
    */
    'config_cache' => [
        'type' => env_string('APP_CONFIG_CACHE_TYPE', $production ? 'single' : 'sharded'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Container
    |--------------------------------------------------------------------------
    |
    | Lazy by default and owns execution scopes directly.
    | There is no application request_scope or compiled-path switch.
    | Fixed per-runtime artifact paths. Production activates only a
    | matching pre-validated artifact and safely falls back to dynamic resolution.
    |
    */
    'container' => [
        'alias' => env('APP_CONTAINER_ALIAS'),
        'environment' => $environment,
        'lazy_loading' => env_bool('APP_CONTAINER_LAZY_LOADING', true),
        'compiled_activation' => env_string(
            'APP_CONTAINER_COMPILED_ACTIVATION',
            $production ? 'always' : 'off',
        ),
        'debug_tracing' => [
            'enabled' => env_bool('APP_CONTAINER_DEBUG_TRACING', false),
            'level' => env_string('APP_CONTAINER_DEBUG_TRACE_LEVEL', 'node'),
        ],
    ],
];
