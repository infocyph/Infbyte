<?php

declare(strict_types=1);

return [
    'default' => env('FILESYSTEM_DISK', 'local'),
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
        ],
        'uploads' => [
            'driver' => 'local',
            'root' => storage_path('uploads'),
        ],
    ],
    'uploads' => [
        'disk' => env('FILESYSTEM_UPLOAD_DISK', 'uploads'),
        'directory' => env('FILESYSTEM_UPLOAD_DIRECTORY', ''),
        'temp_directory' => env('FILESYSTEM_UPLOAD_TEMP_DIRECTORY'),
        'use_date_directories' => env('FILESYSTEM_UPLOAD_USE_DATE_DIRECTORIES', false),
        'validation_profile' => env('FILESYSTEM_UPLOAD_VALIDATION_PROFILE'),
        'allowed_file_types' => [],
        'allowed_extensions' => [],
        'blocked_extensions' => ['php', 'phtml', 'phar', 'exe', 'sh', 'bat', 'cmd', 'com'],
        'max_file_size' => env('FILESYSTEM_UPLOAD_MAX_FILE_SIZE', 5 * 1024 * 1024),
        'max_chunk_count' => env('FILESYSTEM_UPLOAD_MAX_CHUNK_COUNT', 0),
        'max_chunk_size' => env('FILESYSTEM_UPLOAD_MAX_CHUNK_SIZE', 0),
        'max_image_width' => env('FILESYSTEM_UPLOAD_MAX_IMAGE_WIDTH', 0),
        'max_image_height' => env('FILESYSTEM_UPLOAD_MAX_IMAGE_HEIGHT', 0),
        'naming_strategy' => env('FILESYSTEM_UPLOAD_NAMING_STRATEGY', 'hash'),
        'require_malware_scan' => env('FILESYSTEM_UPLOAD_REQUIRE_MALWARE_SCAN', false),
        'strict_content_type_validation' => env('FILESYSTEM_UPLOAD_STRICT_CONTENT_TYPE_VALIDATION', false),
    ],
    'downloads' => [
        'disk' => env('FILESYSTEM_DOWNLOAD_DISK', 'uploads'),
        'directory' => env('FILESYSTEM_DOWNLOAD_DIRECTORY', ''),
        'allowed_roots' => [],
        'allowed_extensions' => [],
        'blocked_extensions' => ['php', 'phtml', 'phar', 'exe', 'sh', 'bat', 'cmd', 'com'],
        'block_hidden_files' => env('FILESYSTEM_DOWNLOAD_BLOCK_HIDDEN', true),
        'chunk_size' => env('FILESYSTEM_DOWNLOAD_CHUNK_SIZE', 8192),
        'default_name' => env('FILESYSTEM_DOWNLOAD_DEFAULT_NAME', 'download.bin'),
        'force_attachment' => env('FILESYSTEM_DOWNLOAD_FORCE_ATTACHMENT', true),
        'max_size' => env('FILESYSTEM_DOWNLOAD_MAX_SIZE', 0),
        'range_requests' => env('FILESYSTEM_DOWNLOAD_RANGE_REQUESTS', true),
    ],
    'offload' => [
        'x_sendfile' => [
            'enabled' => env('FILESYSTEM_OFFLOAD_X_SENDFILE', false),
        ],
        'x_accel_redirect' => [
            'enabled' => env('FILESYSTEM_OFFLOAD_X_ACCEL_REDIRECT', false),
        ],
    ],
];
