<?php

declare(strict_types=1);

return [
    'files' => [
        'web.php',
        'api.php',
        'auth.php',
    ],
    'matcher' => env('ROUTER_MATCHER', 'fused'),
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
    'middleware' => [
        'globals' => [
            'pre' => [
                /*
                 * Trust proxy headers only from known networks and reject hostile requests early.
                 * This must run before routing so every downstream component sees a safe request.
                 */
                'gateway_hardening',
                /*
                 * Add request IDs, timing, tracing, and optional OpenTelemetry/NEL response metadata.
                 * It establishes observability context for the entire request lifecycle.
                 */
                'telemetry',
                /*
                 * Serve a controlled 503 response while the application is intentionally offline.
                 * Keeping it early prevents controllers and external services from running during maintenance.
                 */
                'maintenance_mode',
                /*
                 * Reject oversized headers or bodies before they consume application resources.
                 * This limits exposure to malformed or intentionally expensive requests.
                 */
                'request_limits',
                /*
                 * Select the best response representation from Accept, charset, and locale headers.
                 * Controllers can then return content that matches the client contract.
                 */
                'negotiation',
                /*
                 * Cache only when ROUTER_RESPONSE_CACHE is enabled; personalized responses stay uncached.
                 * It runs before the controller to serve a valid cached response without executing it.
                 */
                'response_cache',
                /*
                 * Generate validators and honour conditional requests to save response bandwidth.
                 * This enables efficient 304 responses when a representation has not changed.
                 */
                'cache_validators',
                /*
                 * Encrypt configured cookies only when ROUTER_COOKIE_ENCRYPTION and a valid key are set.
                 * Keeping it before input access makes decrypted cookie state available to the application.
                 */
                'cookie_encryption',
                /*
                 * Normalize HTTP method overrides before route matching.
                 * This lets browser form submissions consistently target PUT, PATCH, and DELETE routes.
                 */
                'normalize_method',
                /*
                 * Safely parse form, JSON, and upload input before controllers consume it.
                 * This gives request handlers predictable access to the configured input sources.
                 */
                'input_sanitizer',
            ],
            'post' => [
                /*
                 * Compress eligible response bodies after controllers have produced them.
                 * This reduces bandwidth while preserving correct content negotiation headers.
                 */
                'compression',
                /*
                 * Apply browser-facing CORS and security policy headers.
                 * It must see the final response to consistently secure success and error responses.
                 */
                'cors',
                /*
                 * Merge Vary requirements contributed by earlier middleware.
                 * A single normalized header keeps shared caches from mixing response variants.
                 */
                'vary',
                /*
                 * In debug mode, surface malformed or unsafe HTTP responses during development.
                 * It is intentionally last so it validates the completed HTTP response.
                 */
                'response_linter',
            ],
        ],
        'aliases' => [
            /*
             * Use `signed` on routes that require a valid, unexpired signed URL.
             * This is suitable for temporary downloads, previews, and invitation links.
             */
            'signed' => 'verify_signed_url',
            /*
             * Use `throttle:max,seconds` to rate-limit a specific route or route group.
             * Per-route limits avoid penalizing unrelated endpoints with different traffic patterns.
             */
            'throttle' => 'throttle',
            /*
             * Use `limits` when only request-size protection is needed on a route.
             * This is useful for upload or webhook endpoints with stricter payload requirements.
             */
            'limits' => 'request_limits',
            /*
             * Use `cors` to apply the configured cross-origin policy to selected routes.
             * Keep it route-scoped when only an API surface should be accessed cross-origin.
             */
            'cors' => 'cors',
            /*
             * Use `cache` to opt an individual route into response caching.
             * This is safer than global caching for public, stable endpoints.
             */
            'cache' => 'response_cache',
        ],
        'definitions' => [
            /*
             * Defines which proxies and hosts may influence request origin and HTTPS decisions.
             * Configure this accurately whenever the application is deployed behind a load balancer.
             */
            'gateway_hardening' => [
                'trusted_proxy_cidrs' => [],
                'deny_ip_cidrs' => [],
                'trusted_hosts' => [],
                'forwarded_header_mask' => null,
                'enforce_https' => env('APP_ENV', 'local') === 'production',
                'https_port' => 443,
                'strip_hop_by_hop' => true,
                'redirect_allowed_hosts' => [],
            ],
            /*
             * Controls observability headers and optional network-error/OpenTelemetry integration.
             * These settings make request correlation available without coupling controllers to telemetry.
             */
            'telemetry' => [
                'add_x_response_time' => true,
                'add_server_timing' => true,
                'emit_request_id' => true,
                'request_id_header' => 'X-Request-Id',
                'respect_existing_request_id' => true,
                'emit_trace_id_header' => true,
                'trace_id_header' => 'Trace-Id',
                'respect_incoming_traceparent' => true,
                'emit_traceparent_header' => false,
                'enable_otel_integration' => false,
                'otel_service_name' => env('APP_NAME', 'Infbyte'),
                'otel_service_version' => '1.0.0',
                'nel_group' => null,
                'nel_endpoint' => null,
                'nel_ttl_seconds' => 86400,
                'nel_include_subdomains' => true,
                'nel_collect_successes' => false,
            ],
            /*
             * Watches the configured marker file so deployments can temporarily take the app offline.
             * Create the file during a deployment to fail fast with a retriable response.
             */
            'maintenance_mode' => [
                'enabled' => true,
                'file' => 'storage/framework/down',
                'retry_after' => 3600,
                'content_type' => 'text/plain',
            ],
            /*
             * Establishes an application-wide ceiling for HTTP request metadata and payload sizes.
             * Set a body limit before accepting untrusted uploads or webhook payloads.
             */
            'request_limits' => [
                'max_header_bytes' => 8192,
                'max_header_count' => 100,
                'max_body_bytes' => null,
                'body_limit_verbs' => [],
                'violate_on_unknown_body' => false,
            ],
            /*
             * Provides the default values used by the `throttle` route alias.
             * Use an atomic shared cache store for this in multi-worker production deployments.
             */
            'throttle' => [
                'max' => 120,
                'window' => 60,
                'store' => env('CACHE_STORE', 'local'),
                'retry_as_date' => false,
                'emit_standard_rate_limit' => true,
                'scope' => 'http',
                'cost_attribute' => 'rate_cost.thm',
            ],
            /*
             * Protects cookie confidentiality and integrity; keep disabled until a valid key is configured.
             * The key must be suitable for Webrick cookie encryption, not merely any application secret.
             */
            'cookie_encryption' => [
                'enabled' => env('ROUTER_COOKIE_ENCRYPTION', false),
                'key' => env('ROUTER_COOKIE_KEY'),
                'keys' => [],
                'cookie_prefix' => 'enc_',
                'max_bytes' => 3800,
                'store' => env('CACHE_STORE', 'local'),
                'store_ttl' => 86400,
                'drop_on_decrypt_failure' => true,
                'force_secure' => env('APP_ENV', 'local') === 'production',
                'force_http_only' => true,
                'default_same_site' => 'Lax',
            ],
            /*
             * Enables or disables method-override normalization without changing route definitions.
             * Disable it only when clients must be restricted to their literal HTTP verbs.
             */
            'normalize_method' => [
                'enabled' => true,
            ],
            /*
             * Parses selected request input sources early, making controller input access predictable.
             * Enable JSON or upload name handling only when the application needs those sources.
             */
            'input_sanitizer' => [
                'touch_form_bodies' => true,
                'touch_json_bodies' => false,
                'touch_uploaded_names' => false,
            ],
            /*
             * Chooses a response media type, charset, and locale from client preference headers.
             * Keep the lists aligned with the representations your application can actually produce.
             */
            'negotiation' => [
                'produces' => ['+json', 'application/json', 'text/html'],
                'charsets' => ['utf-8'],
                'locales' => ['en'],
                'locale_fallback' => 'en',
            ],
            /*
             * Adds ETag support and handles conditional requests to avoid sending unchanged content.
             * This is a low-risk bandwidth optimization for stable responses.
             */
            'cache_validators' => [
                'auto_etag_when_missing' => true,
                'include_query_in_etag' => true,
                'auto_etag_min_size' => 2048,
            ],
            /*
             * Enables shared response caching only when explicitly requested by ROUTER_RESPONSE_CACHE.
             * Keep it off globally unless every included route is safe to cache for its configured Vary keys.
             */
            'response_cache' => [
                'enabled' => env('ROUTER_RESPONSE_CACHE', false),
                'store' => env('CACHE_STORE', 'local'),
                'ttl_seconds' => 15,
                'include_query' => true,
                'max_body_bytes' => 1048576,
                'default_vary' => ['Accept', 'Accept-Language', 'Accept-Encoding'],
                'skip_when_personalized' => true,
                'respect_response_cache_control' => true,
                'avoid_set_cookie' => true,
            ],
            /*
             * Controls response compression trade-offs between bandwidth, CPU time, and memory use.
             * Tune codecs and buffer limits for your infrastructure rather than compressing every response blindly.
             */
            'compression' => [
                'min_bytes' => 1400,
                'pref_order' => ['zstd', 'br', 'gzip'],
                'etag_mode' => 'weak-on-encode',
                'gzip_level' => 6,
                'brotli_quality' => 4,
                'zstd_level' => 3,
                'etag_derive_salt' => 'enc-v1',
                'max_buffer_bytes' => 8388608,
                'exclude_types' => [],
                'only_types' => [],
                'force_add_vary' => true,
            ],
            /*
             * Defines cross-origin access plus browser security headers such as HSTS and CSP.
             * Replace wildcard origins before enabling credentialed browser requests in production.
             */
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
            /*
             * Ensures all response Vary contributions are emitted as one valid header.
             * This protects downstream caches when middleware varies output by request headers.
             */
            'vary' => [
                'enabled' => true,
            ],
            /*
             * Audits response correctness during development; disabled by default outside debug mode.
             * Keep it off in production because linting is diagnostic work, not user-facing behavior.
             */
            'response_linter' => [
                'enabled' => env('APP_DEBUG', false),
                'checks' => env('APP_DEBUG', true),
            ],
        ],
    ],
];
