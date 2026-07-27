<?php

declare(strict_types=1);

$basePath = dirname(__DIR__);
$options = [
    'base_path' => $basePath,
];

if (getenv('INFBYTE_TESTING') === '1') {
    $options['_config_cache'] = false;
    $options['env'] = 'testing';
    $runtime = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . '/infbyte-runtime-' . getmypid();

    foreach (['app', 'app/public', 'cache', 'logs', 'sessions', 'uploads'] as $directory) {
        $path = $runtime . DIRECTORY_SEPARATOR . $directory;

        if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
            throw new RuntimeException(sprintf('Unable to create test runtime directory "%s".', $path));
        }
    }

    $options['paths'] = [
        'storage' => $runtime,
        'cache' => $runtime . '/cache',
        'logs' => $runtime . '/logs',
        'sessions' => $runtime . '/sessions',
        'uploads' => $runtime . '/uploads',
    ];
    $options['filesystem'] = [
        'disks' => [
            'local' => ['root' => $runtime . '/app'],
            'public' => ['root' => $runtime . '/app/public'],
            'uploads' => ['root' => $runtime . '/uploads'],
        ],
    ];
    $options['auth'] = [
        'drivers' => [
            // Tests must not persist auth lockout state in the application runtime.
            'cache' => 'array',
        ],
    ];
    $options['router'] = [
        'cache' => false,
    ];
}

return $options;
