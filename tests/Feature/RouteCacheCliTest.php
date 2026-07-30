<?php

declare(strict_types=1);

use Infocyph\Foundation\Foundation;
use Infocyph\Foundation\Routing\RouteCachePath;

it('builds and clears route cache through the infbyte cli wrapper', function (): void {
    $root = dirname(__DIR__, 2);
    $cacheFile = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
        . '/infbyte-route-cache-' . bin2hex(random_bytes(5)) . '.php';

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

it('derives the dedicated routes cache path by default', function (): void {
    $root = dirname(__DIR__, 2);
    $app = Foundation::web([
        'base_path' => $root,
        '_config_cache' => false,
    ]);

    expect(RouteCachePath::for($app->config()))->toBe($root . '/bootstrap/cache/routes/fused.php');
});

it('builds and clears the default sharded config cache through the infbyte cli wrapper', function (): void {
    $root = dirname(__DIR__, 2);
    $cacheDirectory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
        . '/infbyte-config-cache-' . bin2hex(random_bytes(5));

    [$buildExitCode, $buildOutput] = runInfbyteCommand([
        PHP_BINARY,
        $root . '/infbyte',
        'config:cache',
        '--path=' . $cacheDirectory,
    ]);

    expect($buildExitCode)->toBe(0);
    expect($buildOutput)->toContain('Configuration cached (sharded):');
    expect($cacheDirectory . '/__manifest.php')->toBeFile()
        ->and($cacheDirectory . '/app.php')->toBeFile()
        ->and($cacheDirectory . '/__flat.php')->not->toBeFile()
        ->and($cacheDirectory . '/__compiled.php')->not->toBeFile();

    [$clearExitCode, $clearOutput] = runInfbyteCommand([
        PHP_BINARY,
        $root . '/infbyte',
        'config:clear',
        '--path=' . $cacheDirectory,
    ]);

    expect($clearExitCode)->toBe(0);
    expect($clearOutput)->toContain('Configuration cache cleared:');
    expect($cacheDirectory . '/__manifest.php')->not->toBeFile()
        ->and($cacheDirectory . '/app.php')->not->toBeFile()
        ->and($cacheDirectory . '/__flat.php')->not->toBeFile();

    rmdir($cacheDirectory);
});

it('can explicitly build a single config cache through the infbyte cli wrapper', function (): void {
    $root = dirname(__DIR__, 2);
    $cacheDirectory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
        . '/infbyte-single-config-cache-' . bin2hex(random_bytes(5));

    [$exitCode, $output] = runInfbyteCommand([
        PHP_BINARY,
        $root . '/infbyte',
        'config:cache',
        '--type=single',
        '--path=' . $cacheDirectory,
    ]);

    expect($exitCode)->toBe(0);
    expect($output)->toContain('Configuration cached (single):');
    expect($cacheDirectory . '/__manifest.php')->toBeFile()
        ->and($cacheDirectory . '/app.php')->not->toBeFile()
        ->and($cacheDirectory . '/__flat.php')->not->toBeFile();

    [$clearExitCode] = runInfbyteCommand([
        PHP_BINARY,
        $root . '/infbyte',
        'config:clear',
        '--path=' . $cacheDirectory,
    ]);

    expect($clearExitCode)->toBe(0);
    rmdir($cacheDirectory);
});

it('builds and clears compiled command metadata through the infbyte cli wrapper', function (): void {
    $root = dirname(__DIR__, 2);
    $cacheDirectory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
        . '/infbyte-command-cache-' . bin2hex(random_bytes(5));
    $manifest = $cacheDirectory . '/commands.php';

    [$buildExitCode, $buildOutput] = runInfbyteCommand([
        PHP_BINARY,
        $root . '/infbyte',
        'command:cache',
        '--path=' . $manifest,
    ]);

    expect($buildExitCode)->toBe(0);
    expect($buildOutput)->toContain('Command manifest ready at:');
    expect($manifest)->toBeFile();
    expect(glob($cacheDirectory . '/commands-*.php') ?: [])->not->toBeEmpty();
    expect($manifest . '.d')->not->toBeDirectory();

    [$clearExitCode, $clearOutput] = runInfbyteCommand([
        PHP_BINARY,
        $root . '/infbyte',
        'command:clear',
        '--path=' . $manifest,
    ]);

    expect($clearExitCode)->toBe(0);
    expect($clearOutput)->toContain('Command manifest cleared:');
    expect($manifest)->not->toBeFile();
    expect(glob($cacheDirectory . '/commands-*.php') ?: [])->toBeEmpty();
    expect($manifest . '.d')->not->toBeDirectory();

    rmdir($cacheDirectory);
});

it('reports readiness and optional module state through the infbyte cli', function (): void {
    $root = dirname(__DIR__, 2);

    [$readinessExitCode, $readinessOutput] = runInfbyteCommand([
        PHP_BINARY,
        $root . '/infbyte',
        'app:ready',
        '--json=1',
    ]);
    [$modulesExitCode, $modulesOutput] = runInfbyteCommand([
        PHP_BINARY,
        $root . '/infbyte',
        'module:list',
        '--json=true',
    ]);

    expect($readinessExitCode)->toBe(2);
    expect(json_decode($readinessOutput, true, flags: JSON_THROW_ON_ERROR)['production_ready'])->toBeFalse();
    expect($modulesExitCode)->toBe(0);

    $modules = array_column(
        json_decode($modulesOutput, true, flags: JSON_THROW_ON_ERROR)['modules'],
        null,
        'name',
    );

    expect($modules['db']['installed'])->toBeFalse()
        ->and($modules['cache']['installed'])->toBeFalse()
        ->and($modules['filesystem']['installed'])->toBeFalse()
        ->and($modules['logging']['installed'])->toBeTrue()
        ->and($modules['messaging']['installed'])->toBeTrue()
        ->and($modules['resources']['installed'])->toBeTrue()
        ->and($modules['session']['installed'])->toBeTrue();
});

it('explains how to install a service owned by an absent optional module', function (): void {
    $root = dirname(__DIR__, 2);

    [$exitCode, $output] = runInfbyteCommand([
        PHP_BINARY,
        $root . '/infbyte',
        'auth:schema:status',
        '--json=1',
    ]);

    expect($exitCode)->toBe(2)
        ->and($output)->toContain('requires infocyph/dblayer')
        ->and($output)->toContain('php infbyte module:install db');
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
