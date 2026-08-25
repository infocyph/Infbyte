<?php

declare(strict_types=1);

use App\Http\Controllers\SystemController;
use Composer\InstalledVersions;
use Infocyph\Foundation\Foundation;
use Infocyph\Foundation\Routing\RouteCachePath;
use Infocyph\Webrick\Request\Request;
use Infocyph\Webrick\Router\Matching\FusedMatcher;

it('keeps the Infbyte CLI identity independent of the application display name', function (): void {
    $fixture = createInfbyteCliFixture();

    try {
        [$exitCode, $output] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            '--version',
        ], ['APP_NAME' => 'Acme Console']);

        expect($exitCode)->toBe(0)
            ->and($output)->toBe(
                'Infbyte ' . (InstalledVersions::getPrettyVersion('infocyph/foundation') ?? 'dev-main'),
            );
    } finally {
        removeInfbyteTestDirectory($fixture);
    }
});

it('builds, consumes, and clears route cache through the infbyte cli wrapper', function (): void {
    $fixture = createInfbyteCliFixture();
    $cacheFile = $fixture . '/bootstrap/cache/routes/fused.php';

    try {
        [$buildExitCode, $buildOutput] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'route:cache',
        ]);

        expect($buildExitCode)->toBe(0)
            ->and($buildOutput)->toContain('Routes cached using fused matcher at ')
            ->and($cacheFile)->toBeFile()
            ->and(filesize($cacheFile))->toBeGreaterThan(0);

        $matcher = FusedMatcher::make()->enableCache($cacheFile);
        [$cachedRoute] = $matcher->match('GET', 'localhost', '/json');

        expect($cachedRoute->getHandler())->toBe([SystemController::class, 'json']);

        file_put_contents(
            $fixture . '/routes/api.php',
            "<?php\n\nthrow new RuntimeException('Cached dispatch loaded route source.');\n",
        );

        $app = Foundation::web([
            'base_path' => $fixture,
            '_config_cache' => false,
            'router' => [
                'cache' => true,
                'files' => ['api.php'],
                'matcher' => 'fused',
            ],
        ]);
        $response = $app->handle(Request::fake(method: 'GET', uri: 'http://localhost/json'));
        $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

        expect($response->getStatusCode())->toBe(200)
            ->and($payload)->toHaveKey('memory')
            ->and($payload['memory'])->toBeInt()
            ->and(get_included_files())->not->toContain($fixture . '/routes/api.php');

        [$clearExitCode, $clearOutput] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'route:clear',
        ]);

        expect($clearExitCode)->toBe(0)
            ->and($clearOutput)->toContain('Route cache cleared.')
            ->and($cacheFile)->not->toBeFile();
    } finally {
        removeInfbyteTestDirectory($fixture);
    }
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
    $fixture = createInfbyteCliFixture();
    $cacheDirectory = $fixture . '/bootstrap/cache/config';

    try {
        [$buildExitCode, $buildOutput] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'config:cache',
        ]);

        expect($buildExitCode)->toBe(0)
            ->and($buildOutput)->toContain('Configuration cached using sharded.')
            ->and($cacheDirectory . '/__manifest.php')->toBeFile()
            ->and($cacheDirectory . '/app.php')->toBeFile()
            ->and($cacheDirectory . '/__flat.php')->toBeFile();

        [$clearExitCode, $clearOutput] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'config:clear',
        ]);

        expect($clearExitCode)->toBe(0)
            ->and($clearOutput)->toContain('Configuration cache cleared.')
            ->and($cacheDirectory)->not->toBeDirectory();
    } finally {
        removeInfbyteTestDirectory($fixture);
    }
});

it('can select and fully clear the single config cache through application configuration', function (): void {
    $fixture = createInfbyteCliFixture();
    $cacheDirectory = $fixture . '/bootstrap/cache/config';

    try {
        [$exitCode, $output] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'config:cache',
        ], ['APP_CONFIG_CACHE_TYPE' => 'single']);

        expect($exitCode)->toBe(0)
            ->and($output)->toContain('Configuration cached using single.')
            ->and($cacheDirectory . '/__manifest.php')->toBeFile()
            ->and($cacheDirectory . '/app.php')->not->toBeFile()
            ->and($cacheDirectory . '/__flat.php')->not->toBeFile();

        [$clearExitCode] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'config:clear',
        ]);

        expect($clearExitCode)->toBe(0)
            ->and($cacheDirectory)->not->toBeDirectory();
    } finally {
        removeInfbyteTestDirectory($fixture);
    }
});

it('builds and clears compiled command metadata through the infbyte cli wrapper', function (): void {
    $fixture = createInfbyteCliFixture();
    $manifest = $fixture . '/bootstrap/cache/commands.php';

    try {
        [$buildExitCode, $buildOutput] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'command:cache',
        ]);

        expect($buildExitCode)->toBe(0)
            ->and($buildOutput)->toContain('Command manifest cached: ')
            ->and($manifest)->toBeFile()
            ->and(glob($fixture . '/bootstrap/cache/.commands-*') ?: [])->toBeEmpty();

        [$clearExitCode, $clearOutput] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'command:clear',
        ]);

        expect($clearExitCode)->toBe(0)
            ->and($clearOutput)->toContain('Command manifest cleared.')
            ->and($manifest)->not->toBeFile();
    } finally {
        removeInfbyteTestDirectory($fixture);
    }
});

it('reports readiness and canonical module state through the infbyte cli', function (): void {
    $fixture = createInfbyteCliFixture();

    try {
        [$readinessExitCode, $readinessOutput] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'app:ready',
            '--json=1',
        ]);
        [$modulesExitCode, $modulesOutput] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'module:list',
            '--json=true',
        ]);

        $readiness = json_decode($readinessOutput, true, flags: JSON_THROW_ON_ERROR);
        $moduleRows = json_decode($modulesOutput, true, flags: JSON_THROW_ON_ERROR);

        expect($readinessExitCode)->toBe(1)
            ->and($readiness['ready'])->toBeFalse()
            ->and($readiness)->toHaveKey('checks')
            ->and($modulesExitCode)->toBe(0)
            ->and($moduleRows)->toBeArray();

        $modules = array_column($moduleRows, null, 'name');

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
        ])->not->toHaveKey('db')
            ->and($modules['database']['packages']['infocyph/dblayer']['installed'])->toBeFalse()
            ->and($modules['database']['packages']['infocyph/dblayer']['constraint'])->toBe('^5.0');

        foreach (['logging', 'operations', 'resources', 'session'] as $builtIn) {
            expect($modules[$builtIn]['installed'])->toBeTrue();
        }
    } finally {
        removeInfbyteTestDirectory($fixture);
    }
});

it('reports canonical database installation guidance through module schema metadata', function (): void {
    $fixture = createInfbyteCliFixture();

    try {
        [$exitCode, $output] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'module:show',
            'auth',
            '--json=1',
        ]);

        $module = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
        $authSchema = $module['schema_status'][0] ?? null;

        expect($exitCode)->toBe(0)
            ->and($module['name'])->toBe('auth')
            ->and($authSchema)->toBeArray()
            ->and($authSchema['state'])->toBe('unavailable')
            ->and($authSchema['detail'])->toContain('php infbyte module:install database');
    } finally {
        removeInfbyteTestDirectory($fixture);
    }
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

function createInfbyteCliFixture(): string
{
    $root = dirname(__DIR__, 2);
    $fixture = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
        . '/infbyte-cli-' . bin2hex(random_bytes(6));

    foreach ([
        $fixture,
        $fixture . '/bootstrap/cache',
        $fixture . '/storage/cache',
        $fixture . '/storage/logs',
        $fixture . '/storage/sessions',
        $fixture . '/storage/uploads',
        $fixture . '/vendor',
    ] as $directory) {
        if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create CLI fixture directory "%s".', $directory));
        }
    }

    copy($root . '/infbyte', $fixture . '/infbyte');
    copy($root . '/composer.json', $fixture . '/composer.json');
    copyInfbyteTestDirectory($root . '/config', $fixture . '/config');
    copyInfbyteTestDirectory($root . '/routes', $fixture . '/routes');
    copy($root . '/bootstrap/providers.php', $fixture . '/bootstrap/providers.php');
    file_put_contents(
        $fixture . '/vendor/autoload.php',
        '<?php return require ' . var_export($root . '/vendor/autoload.php', true) . ';',
    );

    return $fixture;
}

function copyInfbyteTestDirectory(string $source, string $destination): void
{
    if (!mkdir($destination, 0775, true) && !is_dir($destination)) {
        throw new RuntimeException(sprintf('Unable to create test directory "%s".', $destination));
    }

    foreach (new DirectoryIterator($source) as $entry) {
        if ($entry->isDot()) {
            continue;
        }

        $target = $destination . DIRECTORY_SEPARATOR . $entry->getFilename();
        if ($entry->isDir()) {
            copyInfbyteTestDirectory($entry->getPathname(), $target);
            continue;
        }

        copy($entry->getPathname(), $target);
    }
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
