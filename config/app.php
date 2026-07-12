<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'Infbyte'),
    'env' => env('APP_ENV', 'local'),
    'debug' => env('APP_DEBUG', true),
    'url' => env('APP_URL', 'http://localhost'),
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
