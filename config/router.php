<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Route Sources And Matcher
    |--------------------------------------------------------------------------
    |
    | "files" lists route files beneath the routes directory. "matcher" selects
    | a Webrick matcher mode such as fused, sharded, or another supported mode.
    | "auto_slash_redirect" redirects trailing-slash variants when enabled.
    | File example: `api.php`; matcher: `fused|generated|sharded`; redirect:
    | `true|false`.
    |
    */
    'files' => [
        'api.php',
    ],
    'matcher' => env('ROUTER_MATCHER', 'fused'),
    'auto_slash_redirect' => false,

    /*
    |--------------------------------------------------------------------------
    | URL Services And Signed URLs
    |--------------------------------------------------------------------------
    |
    | "expose_url_services" registers URL generation services and
    | "url_base_uri" supplies their default origin. Signed URLs use "key" for
    | authentication, "default_ttl" in seconds, and "options" for additional
    | signer-specific settings. Keep the signing key secret and environment
    | specific. Exposure accepts `true|false`. A base URI may be
    | `https://api.example.com`, the key should be a random 32-byte Base64 secret,
    | and the TTL may be 900 seconds.
    |
    | Supported option keys: `generation_key`, `verification_keys`, `default_ttl`,
    | `signature_param`, `expiry_param`, `algorithm`, `payload_mode`,
    | `ignored_query_params`, and `leeway`. Payload mode accepts
    | `relative|absolute`. Example signature and expiry parameters are `_sig` and
    | `_exp`; an algorithm may be `sha3-256`; an ignored parameter may be
    | `utm_source`; and leeway may be 30 seconds.
    |
    */
    'expose_url_services' => true,
    'url_base_uri' => env('APP_URL', 'http://localhost'),
    'signed_urls' => [
        'key' => env('ROUTER_SIGNED_URL_KEY', env('APP_KEY')),
        'default_ttl' => env('ROUTER_SIGNED_URL_DEFAULT_TTL', 900),
        'options' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Attribute Route Discovery
    |--------------------------------------------------------------------------
    |
    | "enabled" activates attribute discovery. "controller_file_filter" limits
    | scanning to controller-like files. "directories" maps namespaces to scan
    | roots, while "classes" explicitly lists additional attributed classes.
    | Leave discovery disabled when deployment uses compiled route metadata.
    | Boolean values accept `true|false`. For example, map the
    | `App\\Http\\Controllers\\` namespace to `app/Http/Controllers`, and list
    | `App\\Http\\Controllers\\HealthController` as an explicit class.
    |
    */
    'attributes' => [
        'enabled' => env('ROUTER_ATTRIBUTE_ROUTES', false),
        'controller_file_filter' => true,
        'directories' => [
            'App\\Http\\Controllers\\' => app_path('Http/Controllers'),
        ],
        'classes' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Middleware
    |--------------------------------------------------------------------------
    |
    | Global "pre" middleware runs before the route handler and global "post"
    | middleware processes the response. "aliases" maps short route names to
    | registered middleware. "definitions" contains each middleware's options.
    | Entries are registered middleware names such as `gateway_hardening` or
    | class strings; alias examples are `throttle` and `signed`.
    |
    */
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
             *
             * `trusted_proxy_cidrs` permits forwarded headers only from listed networks.
             * "deny_ip_cidrs" rejects client networks and "trusted_hosts" constrains Host.
             * "forwarded_header_mask" selects accepted forwarded fields. "enforce_https"
             * redirects to "https_port". "strip_hop_by_hop" removes connection-local
             * headers and "redirect_allowed_hosts" constrains redirect destinations.
             * CIDR examples: `10.0.0.0/8`, `203.0.113.4/32`; host examples:
             * `api.example.com`; header mask is null or an integer bitmask; HTTPS
             * port example: `443`; boolean keys accept `true|false`.
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
             *
             * "add_x_response_time" and "add_server_timing" expose timing headers.
             * Request/trace ID emit flags control their named headers and the corresponding
             * "respect" flags preserve trusted incoming values. "emit_traceparent_header"
             * emits W3C trace context. OpenTelemetry uses its enable flag, service name,
             * and version. NEL group/endpoint enable browser reporting; TTL is seconds,
             * with subdomain and successful-request collection controlled separately.
             * Boolean keys accept `true|false`. Header examples: `X-Request-Id`,
             * `Trace-Id`; service/version examples: `checkout-api`, `2.4.0`; NEL
             * An NEL group may be `network-errors` and its endpoint may be
             * `https://nel.example/report`. A TTL may be 86400 seconds. Null
             * group/endpoint values disable NEL output.
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
             *
             * "enabled" activates checks, "file" identifies the marker, "retry_after"
             * supplies the response delay in seconds, and "content_type" labels the body.
             * Enabled accepts `true|false`. A marker file may be
             * `storage/framework/down`, a retry delay may be 3600 seconds, and
             * the content type may be `text/plain`.
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
             *
             * Header byte/count keys bound metadata. `max_body_bytes` bounds payload size.
             * null leaves it unbounded. "body_limit_verbs" restricts enforcement by HTTP
             * method. "violate_on_unknown_body" rejects bodies whose size is unavailable.
             * Typical header ceilings are 8192 bytes and 100 fields, while a body
             * ceiling may be 10485760 bytes. Verbs use HTTP names such as
             * `POST|PUT|PATCH`; unknown-body behavior accepts `true|false`.
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
             *
             * "max" requests are allowed per "window" seconds using "store". The retry
             * header may be an HTTP date when "retry_as_date" is true. Standard RateLimit
             * headers are controlled by "emit_standard_rate_limit". "scope" namespaces
             * counters and "cost_attribute" names an optional per-request cost attribute.
             * A typical limit is 120 requests per 60 seconds. The store may be
             * `redis`, the scope may be `api`, and the cost attribute may be
             * `rate_cost.thm`. Boolean keys accept `true|false`.
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
             *
             * "key" is primary and "keys" supports rotation. "cookie_prefix" selects
             * protected cookies and "max_bytes" bounds plaintext. Oversized state may use
             * "store" for "store_ttl" seconds. Decryption failures can drop cookies.
             * "force_secure", "force_http_only", and "default_same_site" harden output.
             * Enabled and policy switches accept `true|false`. Use a random 32-byte
             * Base64 key; rotation may list a new key followed by an old key. A
             * prefix may be `enc_`, limits may be 3800 bytes and 86400 seconds,
             * and the store may be `redis`. SameSite accepts `Lax|Strict|None|null`.
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
             * "enabled" is the sole switch for this middleware.
             * Accepted values: `true|false`.
             */
            'normalize_method' => [
                'enabled' => true,
            ],
            /*
             * Parses selected request input sources early, making controller input access predictable.
             * Enable JSON or upload name handling only when the application needs those sources.
             * "touch_form_bodies", "touch_json_bodies", and "touch_uploaded_names"
             * independently enable normalization for those request input sources.
             * Every key accepts `true|false`.
             */
            'input_sanitizer' => [
                'touch_form_bodies' => true,
                'touch_json_bodies' => false,
                'touch_uploaded_names' => false,
            ],
            /*
             * Chooses a response media type, charset, and locale from client preference headers.
             * Keep the lists aligned with the representations your application can actually produce.
             * "produces" lists supported media suffixes/types, "charsets" accepted encodings,
             * and "locales" supported languages. "locale_fallback" is used when no locale matches.
             * Examples: `['+json', 'application/json', 'text/html']`, `['utf-8']`,
             * `['en', 'bn']`, and fallback `en`.
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
             * "auto_etag_when_missing" hashes eligible responses, "include_query_in_etag"
             * varies validators by query input, and "auto_etag_min_size" is the byte threshold.
             * Boolean keys accept `true|false`; minimum-size example: `2048` bytes.
             */
            'cache_validators' => [
                'auto_etag_when_missing' => true,
                'include_query_in_etag' => true,
                'auto_etag_min_size' => 2048,
            ],
            /*
             * Enables shared response caching only when explicitly requested by ROUTER_RESPONSE_CACHE.
             * Keep it off globally unless every included route is safe to cache for its configured Vary keys.
             *
             * "store" names the cache backend and "ttl_seconds" controls freshness.
             * "include_query" varies keys by query string and "max_body_bytes" bounds entries.
             * "default_vary" lists request headers in the cache key. Personalized responses,
             * restrictive response Cache-Control, and Set-Cookie output are skipped by their
             * respective safety flags.
             * Enabled/safety switches accept `true|false`. A store may be `redis`,
             * with a 15 second TTL and a 1048576 byte body ceiling. Typical Vary
             * members are `Accept`, `Accept-Language`, and `Accept-Encoding`.
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
             *
             * "min_bytes" is the response threshold and "pref_order" ranks codecs.
             * "etag_mode" and "etag_derive_salt" preserve validator correctness across
             * encodings. Codec level/quality keys tune CPU versus size. "max_buffer_bytes"
             * bounds buffering. Exclude/only type lists filter media types, and
             * "force_add_vary" always declares Accept-Encoding variance.
             * Codecs: `zstd|br|gzip|deflate`; ETag modes:
             * `weak-on-encode|recompute-strong|derive-strong`. Examples: minimum
             * `1400` bytes, gzip `0..9`, Brotli `0..11`, Zstandard `3`, salt
             * `enc-v1`, buffer `8388608`, MIME pattern `application/pdf` or
             * `image/*`; force-add-Vary accepts `true|false`.
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
             *
             * Origins, methods, allowed request headers, and exposed response headers define
             * browser access. "max_age_seconds" caches preflight results. Credential and
             * private-network flags grant those capabilities. HSTS has an enable flag and
             * subdomain switch. "csp" sets Content-Security-Policy, "accept_ch" requests
             * client hints, and "timing_allow_origins" controls cross-origin timing access.
             * Example origins are `*` and `https://app.example.com`. Methods are
             * comma-separated HTTP names; headers may include `Content-Type` and
             * `Authorization`; and preflight age may be 3600 seconds. Boolean policy
             * keys accept `true|false`. A CSP may use `default-src 'self'`, a client
             * hint may be `Sec-CH-UA`, and a timing origin may be
             * `https://metrics.example.com`.
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
             * "enabled" is the sole switch for Vary normalization.
             * Accepted values: `true|false`.
             */
            'vary' => [
                'enabled' => true,
            ],
            /*
             * Audits response correctness during development; disabled by default outside debug mode.
             * Keep it off in production because linting is diagnostic work, not user-facing behavior.
             * "enabled" activates the linter and "checks" selects whether configured checks run.
             * Enabled accepts `true|false`. Checks accepts `true|false` or a bitmask:
             * `1` content type, `2` no-body statuses, `4` compression Vary,
             * `8` weak encoded ETag, `16` Content-Length; combine by addition.
             */
            'response_linter' => [
                'enabled' => env('APP_DEBUG', false),
                'checks' => env('APP_DEBUG', true),
            ],
        ],
    ],
];
