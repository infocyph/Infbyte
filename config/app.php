<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Application Identity
    |--------------------------------------------------------------------------
    |
    | "name" labels the application in diagnostics and integrations. "env"
    | selects environment-sensitive defaults, "debug" enables development
    | diagnostics, and "url" is the canonical base URL used to build links.
    | Names and environments are free-form strings, for example `Acme API` and
    | `staging`; debug accepts `true|false`; URL example: `https://api.acme.test`.
    |
    */
    'name' => env('APP_NAME', 'Infbyte'),
    'env' => env('APP_ENV', 'local'),
    'debug' => env('APP_DEBUG', true),
    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Configuration Cache
    |--------------------------------------------------------------------------
    |
    | "type" accepts "sharded" or "single". Sharded caches load namespaces
    | on demand; single caches load one compiled snapshot. Build either form
    | during deployment with `php infbyte config:cache`. Allowed values:
    | `sharded|single`.
    |
    */
    'config_cache' => [
        'type' => env('APP_CONFIG_CACHE_TYPE', 'sharded'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dependency Injection Container
    |--------------------------------------------------------------------------
    |
    | "alias" optionally names a configured container profile. "environment"
    | controls environment-aware definitions. "lazy_loading" defers supported
    | services, while "request_scope" isolates request-lived entries.
    |
    | "compiled" may point to compiled container metadata. Debug tracing is
    | disabled by default; "level" selects the trace detail when enabled.
    |
    | Alias/environment examples: `http` and `production`. Boolean switches use
    | `true|false`. Compiled path example: `bootstrap/cache/container.php`.
    | Trace levels: `off|node|info|warn|warning|error|verbose`.
    |
    */
    'container' => [
        'alias' => env('APP_CONTAINER_ALIAS'),
        'environment' => env('APP_ENV', 'local'),
        'lazy_loading' => env('APP_CONTAINER_LAZY_LOADING', false),
        'request_scope' => env('APP_CONTAINER_REQUEST_SCOPE', true),
        'compiled' => env('APP_CONTAINER_COMPILED'),
        'debug_tracing' => [
            'enabled' => env('APP_CONTAINER_DEBUG_TRACING', false),
            'level' => env('APP_CONTAINER_DEBUG_TRACE_LEVEL', 'node'),
        ],
    ],
];
