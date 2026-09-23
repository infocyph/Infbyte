<?php

declare(strict_types=1);

use Infocyph\Foundation\Release\FoundationReleaseBootstrap;

require dirname(__DIR__) . '/vendor/autoload.php';

$config = ['base_path' => dirname(__DIR__)];
$release = FoundationReleaseBootstrap::fromEnvironment($config);
if (!$release instanceof FoundationReleaseBootstrap) {
    throw new RuntimeException(
        'No trusted Foundation release generation is configured. '
        . 'Run "php infbyte serve" for local development or deploy with '
        . 'INFOCYPH_FOUNDATION_RELEASE_ROOT and '
        . 'INFOCYPH_FOUNDATION_RELEASE_MANIFEST_SHA256.',
    );
}

$release->web($config)->server->handle();
