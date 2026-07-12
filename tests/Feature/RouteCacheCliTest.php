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
