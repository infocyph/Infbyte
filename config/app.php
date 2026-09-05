<?php

declare(strict_types=1);

$environment = env_string('APP_ENV', 'local');
$production = $environment === 'production';
$capabilities = array_values(array_filter(
    array_map('trim', explode(',', env_string('APP_CAPABILITIES', ''))),
    static fn(string $capability): bool => $capability !== '',
));

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
    | Runtime Capabilities
    |--------------------------------------------------------------------------
    |
    | Foundation 3 production generations require an explicit capability
    | topology. Keep the default empty for the lean skeleton and enable optional
    | integrations deliberately, for example: database,cache,messaging.
    |
    */
    'capabilities' => $capabilities,

    /*
    |--------------------------------------------------------------------------
    | Configuration Cache
    |--------------------------------------------------------------------------
    |
    | Production uses one precomputed artifact to minimize process bootstrap.
    | Other environments keep namespaces lazy with sharded configuration.
    | Build the selected artifact during deployment. Allowed: sharded|single.
    |
    */
    'config_cache' => [
        'type' => env_string('APP_CONFIG_CACHE_TYPE', $production ? 'single' : 'sharded'),
    ],

    /*
    |--------------------------------------------------------------------------
    | InterMix Development Settings
    |--------------------------------------------------------------------------
    |
    | Generated Foundation 3 production runtimes load immutable InterMix
    | artifacts from the active release generation. These settings therefore
    | describe graph/development behavior only; there is no resolver-map path,
    | alias, or compiled-activation fallback switch.
    |
    */
    'container' => [
        'environment' => $environment,
        'lazy_loading' => env_bool('APP_CONTAINER_LAZY_LOADING', true),
        'debug_tracing' => [
            'enabled' => env_bool('APP_CONTAINER_DEBUG_TRACING', false),
            'level' => env_string('APP_CONTAINER_DEBUG_TRACE_LEVEL', 'node'),
        ],
    ],
];
