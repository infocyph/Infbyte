<?php

declare(strict_types=1);

$basePath = dirname(__DIR__);
$prefix = $_ENV['CACHE_PREFIX'] ?? 'infbyte:';

return [
    'default' => $_ENV['CACHE_STORE'] ?? 'local',
    'prefix' => $prefix,
    'stores' => [
        'auth' => [
            'driver' => 'local',
            'dir' => $basePath . '/storage/cache/auth',
            'namespace' => $prefix . 'auth',
        ],
        'file' => [
            'driver' => 'file',
            'dir' => $basePath . '/storage/cache/file',
            'namespace' => $prefix . 'file',
        ],
        'local' => [
            'driver' => 'local',
            'dir' => $basePath . '/storage/cache/local',
            'namespace' => $prefix . 'local',
        ],
        'memory' => [
            'driver' => 'memory',
            'namespace' => $prefix . 'memory',
        ],
    ],
];
