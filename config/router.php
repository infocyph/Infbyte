<?php

declare(strict_types=1);

return [
    'load_files' => true,
    'files' => [
        'web.php',
        'api.php',
        'auth.php',
    ],
    'matcher' => env('ROUTER_MATCHER', 'fused'),
    'cache' => env(
        'ROUTER_CACHE',
        env('ROUTER_MATCHER', 'fused') === 'sharded'
            ? bootstrap_path('cache/routes')
            : bootstrap_path('cache/routes.php'),
    ),
    'auto_slash_redirect' => false,
    'expose_url_services' => true,
    'url_base_uri' => env('APP_URL', 'http://localhost'),
    'signed_urls' => [
        'key' => env('ROUTER_SIGNED_URL_KEY', env('APP_KEY')),
        'default_ttl' => env('ROUTER_SIGNED_URL_DEFAULT_TTL', 900),
        'options' => [],
    ],
    'attributes' => [
        'enabled' => env('ROUTER_ATTRIBUTE_ROUTES', false),
        'controller_file_filter' => true,
        'directories' => [
            'App\\Http\\Controllers\\' => app_path('Http/Controllers'),
        ],
        'classes' => [],
    ],
    'webrick' => [
        'globals' => [
            'pre' => [
                'gateway_hardening',
                'telemetry',
                'request_limits',
                'normalize_method',
                'input_sanitizer',
                'negotiation',
                'cache_validators',
            ],
            'post' => [
                'compression',
                'cors',
                'vary',
            ],
        ],
        'aliases' => [
            'signed' => [
                'driver' => 'verify_signed_url',
                'enabled' => true,
            ],
            'throttle' => [
                'driver' => 'throttle',
                'enabled' => true,
                'max' => 120,
                'window' => 60,
                'store' => env('CACHE_STORE', 'local'),
            ],
            'limits' => [
                'driver' => 'request_limits',
                'enabled' => true,
            ],
            'cors' => [
                'driver' => 'cors',
                'enabled' => true,
            ],
            'cache' => [
                'driver' => 'response_cache',
                'enabled' => true,
                'store' => env('CACHE_STORE', 'local'),
            ],
        ],
        'middleware' => [
            'gateway_hardening' => [
                'trusted_proxy_cidrs' => [],
                'deny_ip_cidrs' => [],
                'trusted_hosts' => [],
                'enforce_https' => env('APP_ENV', 'local') === 'production',
                'https_port' => 443,
                'strip_hop_by_hop' => true,
                'redirect_allowed_hosts' => [],
            ],
            'telemetry' => [
                'add_x_response_time' => true,
                'add_server_timing' => true,
                'emit_request_id' => true,
                'emit_trace_id_header' => true,
                'respect_incoming_traceparent' => true,
                'emit_traceparent_header' => false,
                'enable_otel_integration' => false,
                'otel_service_name' => env('APP_NAME', 'Infbyte'),
                'otel_service_version' => '1.0.0',
            ],
            'request_limits' => [
                'max_header_bytes' => 8192,
                'max_header_count' => 100,
                'max_body_bytes' => null,
                'violate_on_unknown_body' => false,
            ],
            'throttle' => [
                'max' => 120,
                'window' => 60,
                'store' => env('CACHE_STORE', 'local'),
                'retry_as_date' => false,
                'scope' => 'http',
                'cost_attribute' => 'rate_cost.thm',
            ],
            'input_sanitizer' => [
                'touch_form_bodies' => true,
                'touch_json_bodies' => false,
                'touch_uploaded_names' => false,
            ],
            'negotiation' => [
                'produces' => ['+json', 'application/json', 'text/html'],
                'charsets' => ['utf-8'],
                'locales' => ['en'],
                'locale_fallback' => 'en',
            ],
            'cache_validators' => [
                'auto_etag_when_missing' => true,
                'include_query_in_etag' => true,
                'auto_etag_min_size' => 2048,
            ],
            'response_cache' => [
                'store' => env('CACHE_STORE', 'local'),
                'ttl_seconds' => 15,
                'include_query' => true,
            ],
            'compression' => [
                'min_bytes' => 1400,
                'pref_order' => ['zstd', 'br', 'gzip'],
                'etag_mode' => 'weak-on-encode',
                'force_add_vary' => true,
            ],
            'cors' => [
                'origins' => ['*'],
                'methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
                'allow_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
                'expose_headers' => ['Content-Length', 'Content-Type', 'ETag', 'Server-Timing', 'Location'],
                'max_age_seconds' => 3600,
                'allow_credentials' => true,
                'allow_private_network' => false,
                'hsts' => env('APP_ENV', 'local') === 'production',
                'hsts_include_subdomains' => true,
                'csp' => "default-src 'self'; object-src 'none'; frame-ancestors 'none'; base-uri 'self';",
                'accept_ch' => [],
                'timing_allow_origins' => [],
            ],
            'response_linter' => [
                'checks' => env('APP_DEBUG', true),
            ],
        ],
    ],
];
