<?php

declare(strict_types=1);

use Infocyph\Foundation\Release\FoundationReleaseBootstrap;

require dirname(__DIR__) . '/vendor/autoload.php';

$config = ['base_path' => dirname(__DIR__)];
$release = FoundationReleaseBootstrap::fromEnvironment($config)
    ?? throw new RuntimeException(
        'No trusted Foundation release generation is configured. Run "php infbyte optimize" and provide '
        . FoundationReleaseBootstrap::MANIFEST_SHA256_ENV . ' to the web process.',
    );

$release->web($config)->server->handle();
