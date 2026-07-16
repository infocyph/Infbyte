<?php

declare(strict_types=1);

it('builds and clears route cache through the infbyte cli wrapper', function (): void {
    $root = dirname(__DIR__, 2);
    $cacheFile = $root . '/storage/framework-tests/route-cache-' . uniqid('', true) . '.php';

    [$buildExitCode, $buildOutput] = runInfbyteCommand([
        PHP_BINARY,
        $root . '/infbyte',
        'route:cache',
        '--matcher=fused',
        '--cache=' . $cacheFile,
    ]);

    expect($buildExitCode)->toBe(0);
    expect($buildOutput)->toContain('Route cache ready at:');
    expect(is_file($cacheFile))->toBeTrue();
    expect(filesize($cacheFile))->toBeGreaterThan(0);

    [$clearExitCode, $clearOutput] = runInfbyteCommand([
        PHP_BINARY,
        $root . '/infbyte',
        'route:clear',
        '--matcher=fused',
        '--cache=' . $cacheFile,
    ]);

    expect($clearExitCode)->toBe(0);
    expect($clearOutput)->toContain('Route cache cleared:');
    expect(file_exists($cacheFile))->toBeFalse();
});

it('builds and clears compiled config through the infbyte cli wrapper', function (): void {
    $root = dirname(__DIR__, 2);
    $cacheFile = $root . '/storage/framework-tests/config-cache-' . uniqid('', true) . '.php';

    [$buildExitCode, $buildOutput] = runInfbyteCommand([
        PHP_BINARY,
        $root . '/infbyte',
        'config:cache',
        '--path=' . $cacheFile,
    ]);

    expect($buildExitCode)->toBe(0);
    expect($buildOutput)->toContain('Configuration cached:');
    expect(is_file($cacheFile))->toBeTrue();

    $cached = require $cacheFile;

    expect($cached)->toBeArray()
        ->and($cached)->toHaveKey('app');

    [$clearExitCode, $clearOutput] = runInfbyteCommand([
        PHP_BINARY,
        $root . '/infbyte',
        'config:clear',
        '--path=' . $cacheFile,
    ]);

    expect($clearExitCode)->toBe(0);
    expect($clearOutput)->toContain('Configuration cache cleared:');
    expect(file_exists($cacheFile))->toBeFalse();
});

it('reports application readiness and auth schema status through the infbyte cli', function (): void {
    $root = dirname(__DIR__, 2);

    [$readinessExitCode, $readinessOutput] = runInfbyteCommand([
        PHP_BINARY,
        $root . '/infbyte',
        'app:ready',
        '--json=1',
    ]);
    [$schemaExitCode, $schemaOutput] = runInfbyteCommand([
        PHP_BINARY,
        $root . '/infbyte',
        'auth:schema:status',
        '--json=1',
    ]);

    expect($readinessExitCode)->toBe(0);
    expect(json_decode($readinessOutput, true, flags: JSON_THROW_ON_ERROR))
        ->toMatchArray(['production_ready' => true]);
    expect($schemaExitCode)->toBe(0);
    expect(json_decode($schemaOutput, true, flags: JSON_THROW_ON_ERROR))
        ->toMatchArray(['installed' => true]);
});

/**
 * @param list<string> $arguments
 * @return array{0:int,1:string}
 */
function runInfbyteCommand(array $arguments): array
{
    $command = implode(' ', array_map(
        static fn(string $argument): string => escapeshellarg($argument),
        $arguments,
    )) . ' 2>&1';

    $output = [];
    $exitCode = 0;

    exec($command, $output, $exitCode);

    return [$exitCode, implode("\n", $output)];
}
