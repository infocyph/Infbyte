<?php

declare(strict_types=1);

use Composer\InstalledVersions;

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

it('consumes the stable Foundation 3 package and released core cache template', function (): void {
    $root = dirname(__DIR__, 2);
    $composer = json_decode(
        (string) file_get_contents($root . '/composer.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    $version = InstalledVersions::getPrettyVersion('infocyph/foundation');
    $foundationPath = InstalledVersions::getInstallPath('infocyph/foundation');

    expect($composer['require']['infocyph/foundation'] ?? null)->toBe('^3.0')
        ->and($version)->toBeString()
        ->and(str_starts_with($version, 'dev-'))->toBeFalse()
        ->and($foundationPath)->toBeString();

    $releasedCache = file_get_contents($foundationPath . '/resources/config/cache.php');
    $skeletonCache = file_get_contents($root . '/config/cache.php');

    expect($releasedCache)->toBeString()
        ->and($skeletonCache)->toBeString()
        ->and($skeletonCache)->toContain("'default' => env('CACHE_STORE', 'local')")
        ->and($skeletonCache)->toContain("'driver' => 'sqlite'")
        ->and($skeletonCache)->toContain("'driver' => 'file'")
        ->and($skeletonCache)->toContain('CACHE_SQLITE_LOCK_PATH');
});

it('keeps production auth secrets indirect and hardened cache resources opt in', function (): void {
    $root = dirname(__DIR__, 2);
    $auth = file_get_contents($root . '/config/auth.php');
    $cache = file_get_contents($root . '/config/cache.php');

    expect($auth)->toBeString()
        ->and($auth)->toContain("'token_secret_environment' => env_string('AUTH_TOKEN_SECRET_ENVIRONMENT', 'AUTH_TOKEN_SECRET')")
        ->and($auth)->not->toContain("'token_secret' => env('AUTH_TOKEN_SECRET')")
        ->and($cache)->toBeString()
        ->and($cache)->toContain("'default' => env('CACHE_STORE', 'local')")
        ->and($cache)->toContain("'auth-state' => [")
        ->and($cache)->toContain("'fail_open' => false")
        ->and($cache)->toContain("'counters' => [")
        ->and($cache)->toContain("'redis' => [")
        ->and($cache)->toContain("'valkey' => [");
});

it('documents the released module and cache lifecycle without pre-tag paths', function (): void {
    $readme = file_get_contents(dirname(__DIR__, 2) . '/README.md');
    $auth = file_get_contents(dirname(__DIR__, 2) . '/config/auth.php');

    expect($readme)->toBeString()
        ->and($readme)->toContain('"infocyph/foundation": "^3.0"')
        ->and($readme)->toContain('cache:schema:status')
        ->and($readme)->toContain('cache:schema:install')
        ->and($readme)->not->toContain('php infbyte module:install cache')
        ->and($readme)->not->toContain('foundation-3/close-26.6')
        ->and($auth)->toBeString()
        ->and($auth)->not->toContain('module:install cache')
        ->and($auth)->toContain('module:install auth --feature=otp')
        ->and($auth)->toContain('module:install auth --feature=passkey');
});
