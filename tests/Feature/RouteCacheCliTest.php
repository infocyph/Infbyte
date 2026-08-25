<?php

declare(strict_types=1);

use App\Http\Controllers\SystemController;
use Composer\InstalledVersions;
use Infocyph\Foundation\Foundation;
use Infocyph\Foundation\Routing\RouteCachePath;
use Infocyph\Webrick\Request\Request;
use Infocyph\Webrick\Router\Matching\FusedMatcher;

it('uses the environment application name and reports the Foundation runtime version', function (): void {
    $root = dirname(__DIR__, 2);
    [$exitCode, $output] = runInfbyteCommand([
        PHP_BINARY,
        $root . '/infbyte',
        '--version',
    ], ['APP_NAME' => 'Acme Console']);

    expect($exitCode)->toBe(0)
        ->and($output)->toBe(
            'Acme Console ' . (InstalledVersions::getPrettyVersion('infocyph/foundation') ?? 'dev-main'),
        );
});

it('falls back to infbyte when the environment application name is empty', function (): void {
    $root = dirname(__DIR__, 2);
    [$exitCode, $output] = runInfbyteCommand([
        PHP_BINARY,
        $root . '/infbyte',
        '--version',
    ], ['APP_NAME' => '']);

    expect($exitCode)->toBe(0)
        ->and($output)->toBe(
            'infbyte ' . (InstalledVersions::getPrettyVersion('infocyph/foundation') ?? 'dev-main'),
        );
});

it('builds, consumes, and clears route cache through the infbyte cli wrapper', function (): void {
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

    expect($buildExitCode)->toBe(0)
        ->and($buildOutput)->toContain('Route cache ready at:')
        ->and($cacheFile)->toBeFile()
        ->and(filesize($cacheFile))->toBeGreaterThan(0);

    $matcher = FusedMatcher::make()->enableCache($cacheFile);
    [$cachedRoute] = $matcher->match('GET', 'localhost', '/json');

    expect($cachedRoute->getHandler())->toBe([SystemController::class, 'json']);

    $runtime = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
        . '/infbyte-cached-runtime-' . bin2hex(random_bytes(5));
    $runtimeCache = $runtime . '/bootstrap/cache/routes/fused.php';
    mkdir(dirname($runtimeCache), 0775, true);
    mkdir($runtime . '/routes', 0775, true);
    copy($cacheFile, $runtimeCache);
    file_put_contents(
        $runtime . '/routes/missing.php',
        "<?php\n\nthrow new RuntimeException('Cached dispatch loaded route source.');\n",
    );

    try {
        $app = Foundation::web([
            'base_path' => $runtime,
            '_config_cache' => false,
            'router' => [
                'cache' => true,
                'files' => ['missing.php'],
                'matcher' => 'fused',
            ],
        ]);
        $response = $app->handle(Request::fake(method: 'GET', uri: 'http://localhost/json'));
        $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

        expect($response->getStatusCode())->toBe(200)
            ->and($payload)->toHaveKey('memory')
            ->and($payload['memory'])->toBeInt()
            ->and(get_included_files())->not->toContain($runtime . '/routes/missing.php');
    } finally {
        removeInfbyteTestDirectory($runtime);
    }

    [$clearExitCode, $clearOutput] = runInfbyteCommand([
        PHP_BINARY,
        $root . '/infbyte',
        'route:clear',
        '--matcher=fused',
        '--cache=' . $cacheFile,
    ]);

    expect($clearExitCode)->toBe(0)
        ->and($clearOutput)->toContain('Route cache cleared:')
        ->and($cacheFile)->not->toBeFile();
});

it('derives the dedicated routes cache path by default', function (): void {
    $root = dirname(__DIR__, 2);
    $app = Foundation::web([
        'base_path' => $root,
        '_config_cache' => false,
    ]);

    expect(RouteCachePath::for($app->config()))->toBe($root . '/bootstrap/cache/routes/fused.php');
});

it('builds and fully clears the default sharded config cache through the infbyte cli wrapper', function (): void {
    $root = dirname(__DIR__, 2);
    $cacheDirectory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
        . '/infbyte-config-cache-' . bin2hex(random_bytes(5));

    [$buildExitCode, $buildOutput] = runInfbyteCommand([
        PHP_BINARY,
        $root . '/infbyte',
        'config:cache',
        '--path=' . $cacheDirectory,
    ]);

    expect($buildExitCode)->toBe(0)
        ->and($buildOutput)->toContain('Configuration cached (sharded):')
        ->and($cacheDirectory . '/__manifest.php')->toBeFile()
        ->and($cacheDirectory . '/app.php')->toBeFile()
        ->and($cacheDirectory . '/__flat.php')->not->toBeFile()
        ->and($cacheDirectory . '/__compiled.php')->not->toBeFile();

    [$clearExitCode, $clearOutput] = runInfbyteCommand([
        PHP_BINARY,
        $root . '/infbyte',
        'config:clear',
        '--path=' . $cacheDirectory,
    ]);

    expect($clearExitCode)->toBe(0)
        ->and($clearOutput)->toContain('Configuration cache cleared:')
        ->and($cacheDirectory)->not->toBeDirectory();
});

it('can explicitly build and fully clear a single config cache', function (): void {
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

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('Configuration cached (single):')
        ->and($cacheDirectory . '/__manifest.php')->toBeFile()
        ->and($cacheDirectory . '/app.php')->not->toBeFile()
        ->and($cacheDirectory . '/__flat.php')->not->toBeFile();

    [$clearExitCode] = runInfbyteCommand([
        PHP_BINARY,
        $root . '/infbyte',
        'config:clear',
        '--path=' . $cacheDirectory,
    ]);

    expect($clearExitCode)->toBe(0)
        ->and($cacheDirectory)->not->toBeDirectory();
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

    expect($buildExitCode)->toBe(0)
        ->and($buildOutput)->toContain('Command manifest ready at:')
        ->and($manifest)->toBeFile()
        ->and(glob($cacheDirectory . '/commands-*.php') ?: [])->not->toBeEmpty()
        ->and($manifest . '.d')->not->toBeDirectory();

    [$clearExitCode, $clearOutput] = runInfbyteCommand([
        PHP_BINARY,
        $root . '/infbyte',
        'command:clear',
        '--path=' . $manifest,
    ]);

    expect($clearExitCode)->toBe(0)
        ->and($clearOutput)->toContain('Command manifest cleared:')
        ->and($manifest)->not->toBeFile()
        ->and(glob($cacheDirectory . '/commands-*.php') ?: [])->toBeEmpty()
        ->and($manifest . '.d')->not->toBeDirectory();

    if (is_dir($cacheDirectory)) {
        rmdir($cacheDirectory);
    }
});

it('reports readiness and canonical module state through the infbyte cli', function (): void {
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

    expect($readinessExitCode)->toBe(2)
        ->and(json_decode($readinessOutput, true, flags: JSON_THROW_ON_ERROR)['production_ready'])->toBeFalse()
        ->and($modulesExitCode)->toBe(0);

    $modules = array_column(
        json_decode($modulesOutput, true, flags: JSON_THROW_ON_ERROR)['modules'],
        null,
        'name',
    );

    expect($modules)->toHaveKeys([
        'auth',
        'cache',
        'communication',
        'database',
        'filesystem',
        'logging',
        'messaging',
        'operations',
        'resources',
        'security',
        'session',
        'validation',
    ])->not->toHaveKey('db');

    foreach (['logging', 'operations', 'resources', 'session'] as $builtIn) {
        expect($modules[$builtIn]['installed'])->toBeTrue();
    }
});

it('explains the canonical module installation path for absent database support', function (): void {
    $root = dirname(__DIR__, 2);

    [$exitCode, $output] = runInfbyteCommand([
        PHP_BINARY,
        $root . '/infbyte',
        'auth:schema:status',
        '--json=1',
    ]);

    expect($exitCode)->toBe(2)
        ->and($output)->toContain('requires infocyph/dblayer')
        ->and($output)->toContain('php infbyte module:install database');
});

/**
 * @param list<string> $arguments
 * @param array<string, string> $environment
 * @return array{0:int,1:string}
 */
function runInfbyteCommand(array $arguments, array $environment = []): array
{
    $command = '';
    foreach ($environment as $key => $value) {
        if (preg_match('/^[A-Z_][A-Z0-9_]*$/D', $key) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid environment variable name: %s', $key));
        }

        $command .= $key . '=' . escapeshellarg($value) . ' ';
    }

    $command .= implode(' ', array_map(
        static fn(string $argument): string => escapeshellarg($argument),
        $arguments,
    )) . ' 2>&1';

    $output = [];
    $exitCode = 0;

    exec($command, $output, $exitCode);

    return [$exitCode, implode("\n", $output)];
}

function removeInfbyteTestDirectory(string $directory): void
{
    if (!is_dir($directory)) {
        return;
    }

    $entries = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($entries as $entry) {
        $path = $entry->getPathname();

        if ($entry->isLink() || $entry->isFile()) {
            unlink($path);
            continue;
        }

        rmdir($path);
    }

    rmdir($directory);
}
