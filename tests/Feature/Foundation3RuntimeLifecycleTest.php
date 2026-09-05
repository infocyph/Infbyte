<?php

declare(strict_types=1);

it('requires an explicit Foundation 3 capability topology', function (): void {
    $appConfig = file_get_contents(dirname(__DIR__, 2) . '/config/app.php');

    expect($appConfig)->toBeString()
        ->and($appConfig)->toContain("env_string('APP_CAPABILITIES', '')")
        ->and($appConfig)->not->toContain('APP_CONTAINER_COMPILED_ACTIVATION')
        ->and($appConfig)->not->toContain('APP_CONTAINER_ALIAS');
});

it('keeps production release trust outside project configuration', function (): void {
    $example = file_get_contents(dirname(__DIR__, 2) . '/.env.example');

    expect($example)->toBeString()
        ->and($example)->toContain("APP_CAPABILITIES=\n")
        ->and($example)->not->toContain('INFOCYPH_FOUNDATION_RELEASE_MANIFEST_SHA256')
        ->and($example)->not->toContain('INFOCYPH_FOUNDATION_RELEASE_ROOT');
});
