<?php

declare(strict_types=1);

use Composer\InstalledVersions;
use Tests\Support\FixtureData;

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

it('builds reports and clears one immutable Foundation release generation', function (): void {
    $fixture = createInfbyteCliFixture();

    try {
        [$buildExitCode, $buildOutput] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'optimize',
            '--json=1',
        ], ['APP_ENV' => 'production']);

        $build = FixtureData::json($buildOutput);
        $manifest = FixtureData::text($build['manifest'] ?? null);

        expect($buildExitCode)->toBe(0, $buildOutput)
            ->and($build)->toHaveKeys([
                'release_root',
                'generation',
                'manifest',
                'manifest_sha256',
                'active_pointer',
            ])
            ->and($build['release_root'])->toBe($fixture . '/storage/releases')
            ->and($manifest)->toBeString()->toBeFile()
            ->and(hash_file('sha256', $manifest))->toBe($build['manifest_sha256']);

        [$reportExitCode, $reportOutput] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'optimize:report',
            '--json=1',
        ], ['APP_ENV' => 'production']);
        $report = FixtureData::json($reportOutput);

        expect($reportExitCode)->toBe(0, $reportOutput)
            ->and($report['ready'] ?? null)->toBeTrue()
            ->and($report['generation'] ?? null)->toBe($build['generation'])
            ->and($report['manifest_sha256'] ?? null)->toBe($build['manifest_sha256']);

        [$clearExitCode, $clearOutput] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'optimize:clear',
        ], ['APP_ENV' => 'production']);

        expect($clearExitCode)->toBe(0, $clearOutput)
            ->and($clearOutput)->toContain('Foundation release generations cleared.');

        [$emptyExitCode, $emptyOutput] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'optimize:report',
            '--json=1',
        ], ['APP_ENV' => 'production']);
        $empty = FixtureData::json($emptyOutput);

        expect($emptyExitCode)->toBe(0, $emptyOutput)
            ->and($empty['ready'] ?? null)->toBeFalse();
    } finally {
        removeInfbyteTestDirectory($fixture);
    }
});


it('builds and fully clears the default sharded config cache through the infbyte cli wrapper', function (): void {
    $fixture = createInfbyteCliFixture();
    $cacheDirectory = $fixture . '/bootstrap/cache/config';

    try {
        [$buildExitCode, $buildOutput] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'config:cache',
        ], ['APP_ENV' => 'local']);

        expect($buildExitCode)->toBe(0)
            ->and($buildOutput)->toContain('Configuration cached using sharded.')
            ->and($cacheDirectory . '/__manifest.php')->toBeFile()
            ->and($cacheDirectory)->toBeDirectory();

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
            ->and(glob($fixture . '/bootstrap/cache/.commands-*'))->toBe([]);

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

it('reports readiness and Foundation 4 qualified feature groups through the infbyte cli', function (): void {
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

        $readiness = FixtureData::json($readinessOutput);
        $moduleRows = FixtureData::json($modulesOutput);

        expect($readinessExitCode)->toBe(0, $readinessOutput)
            ->and($readiness['ready'])->toBeTrue()
            ->and($readiness)->toHaveKey('checks')
            ->and($modulesExitCode)->toBe(0)
            ->and($moduleRows)->toBeArray();

        $modules = array_column(FixtureData::rows($moduleRows['modules'] ?? null), null, 'name');

        expect($moduleRows['schema_version'] ?? null)->toBe(2)
            ->and(array_keys($modules))->toBe(['data', 'security', 'execution'])
            ->and(FixtureData::map($modules['data'] ?? null)['features'] ?? null)->toBe(['data.database', 'data.filesystem'])
            ->and(FixtureData::map($modules['security'] ?? null)['features'] ?? null)->toBe([
                'security.crypto',
                'security.validation',
                'security.mfa',
                'security.passkey',
            ])
            ->and(FixtureData::map($modules['execution'] ?? null)['features'] ?? null)->toBe([
                'execution.messaging',
                'execution.communication',
            ]);
    } finally {
        removeInfbyteTestDirectory($fixture);
    }
});


it('keeps core cache outside module lifecycle and exposes its schema commands', function (): void {
    $fixture = createInfbyteCliFixture();

    try {
        [$moduleExitCode, $moduleOutput] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'module:show',
            'cache',
            '--json=1',
        ]);

        [$schemaExitCode, $schemaOutput] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'cache:schema:status',
            '--json=1',
        ], ['APP_CAPABILITIES' => 'cache']);

        $schema = FixtureData::json($schemaOutput);

        expect($moduleExitCode)->not->toBe(0)
            ->and($moduleOutput)->toContain('cache')
            ->and($schemaExitCode)->toBe(0, $schemaOutput)
            ->and($schema)->toHaveKey('schemas')
            ->and($schema['schemas'])->toBeArray();
    } finally {
        removeInfbyteTestDirectory($fixture);
    }
});

it('plans explicit Foundation 4 database feature installation', function (): void {
    $fixture = createInfbyteCliFixture();

    try {
        [$exitCode, $output] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'module:plan',
            'data.database',
            '--json=1',
        ]);

        $plan = FixtureData::json($output);

        expect($exitCode)->toBe(0, $output)
            ->and($plan['schema_version'] ?? null)->toBe(2)
            ->and($plan['requested'] ?? null)->toContain('data.database')
            ->and($output)->toContain('infocyph/dblayer')
            ->and($output)->toContain((new \Infocyph\Foundation\Module\ModuleArchitectureCatalog())->feature('data', 'database')['packages']['infocyph/dblayer']);
    } finally {
        removeInfbyteTestDirectory($fixture);
    }
});

it('rejects unknown module feature identifiers rather than guessing intent', function (): void {
    $fixture = createInfbyteCliFixture();

    try {
        [$exitCode, $output] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'module:plan',
            'unsupported.feature',
            '--json=1',
        ]);

        expect($exitCode)->not->toBe(0)
            ->and(strtolower($output))->toContain('unsupported.feature');
    } finally {
        removeInfbyteTestDirectory($fixture);
    }
});

it('delegates native HTTP host selection and protocol policy to Runwire', function (): void {
    $fixture = createInfbyteCliFixture();

    try {
        [$exitCode, $output] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'serve',
            '--runtime=native',
            '--workers=1',
            '--protocol-policy=fixed',
            '--dry-run',
            '--json=1',
        ]);
        $result = FixtureData::json($output);

        expect($exitCode)->toBe(0, $output)
            ->and($result['requested_runtime'] ?? null)->toBe('native')
            ->and($result['runtime'] ?? null)->toBe('native')
            ->and($result['protocol_policy'] ?? null)->toBe('fixed')
            ->and($result['workers'] ?? null)->toBe(1);

        [$autoExit, $autoOutput] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'serve',
            '--dry-run',
            '--json=1',
        ]);
        $auto = FixtureData::json($autoOutput);

        expect($autoExit)->toBe(0, $autoOutput)
            ->and($auto['requested_runtime'] ?? null)->toBe('auto')
            ->and($auto['runtime'] ?? null)->toBeString();

        [$badDriver, $driverMessage] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'serve',
            '--runtime=nonexistent',
            '--dry-run',
        ]);
        [$badPolicy, $policyMessage] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'serve',
            '--runtime=native',
            '--protocol-policy=bogus',
            '--dry-run',
        ]);

        expect($badDriver)->not->toBe(0)
            ->and($driverMessage)->toContain('Invalid --runtime')
            ->and($badPolicy)->not->toBe(0)
            ->and($policyMessage)->toContain('Invalid --protocol-policy');
    } finally {
        removeInfbyteTestDirectory($fixture);
    }
});

it('fails closed if a previously trusted Foundation generation manifest changes', function (): void {
    $fixture = createInfbyteCliFixture();

    try {
        [$code, $output] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'optimize',
            '--json=1',
        ], ['APP_ENV' => 'production']);
        $release = FixtureData::json($output);
        $manifest = FixtureData::text($release['manifest'] ?? null);
        $digest = FixtureData::text($release['manifest_sha256'] ?? null);

        expect($code)->toBe(0, $output)
            ->and($manifest)->toBeString()->toBeFile()
            ->and($digest)->toBeString()->toMatch('/^[a-f0-9]{64}$/');

        $bootstrap = new \Infocyph\Foundation\Release\FoundationReleaseBootstrap(
            $fixture . '/storage/releases',
            $digest,
        );
        expect($bootstrap->web(['base_path' => $fixture])::class)->toBe(
            \Infocyph\Foundation\Routing\WebReleaseRuntime::class,
        );

        file_put_contents($manifest, "\n/* trust-digest tamper */\n", FILE_APPEND);
        expect(fn() => $bootstrap->web(['base_path' => $fixture]))
            ->toThrow(RuntimeException::class, 'Foundation release artifact trust identity mismatch.');
    } finally {
        removeInfbyteTestDirectory($fixture);
    }
});

it('rotates trusted release generations without deleting application-owned state', function (): void {
    $fixture = createInfbyteCliFixture();
    $retained = $fixture . '/storage/uploads/retained.txt';

    try {
        file_put_contents($retained, 'application-owned-file');

        [$firstExit, $firstOutput] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'optimize',
            '--json=1',
        ], ['APP_ENV' => 'production']);
        $first = FixtureData::json($firstOutput);

        [$nextExit, $nextOutput] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'optimize',
            '--json=1',
        ], ['APP_ENV' => 'production']);
        $next = FixtureData::json($nextOutput);

        expect($firstExit)->toBe(0, $firstOutput)
            ->and($nextExit)->toBe(0, $nextOutput)
            ->and($first['generation'])->not->toBe($next['generation'])
            ->and($next['manifest'])->toBeFile()
            ->and($next['manifest_sha256'])->toBe(hash_file('sha256', FixtureData::text($next['manifest'] ?? null)))
            ->and($retained)->toBeFile()
            ->and(file_get_contents($retained))->toBe('application-owned-file');

        [$reportExit, $reportOutput] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'optimize:report',
            '--json=1',
        ], ['APP_ENV' => 'production']);
        $report = FixtureData::json($reportOutput);

        expect($reportExit)->toBe(0, $reportOutput)
            ->and($report['ready'] ?? null)->toBeTrue()
            ->and($report['generation'] ?? null)->toBe($next['generation'])
            ->and($report['manifest_sha256'] ?? null)->toBe($next['manifest_sha256']);
    } finally {
        removeInfbyteTestDirectory($fixture);
    }
});

it('supports an explicitly trusted release rollback while preserving retained application data', function (): void {
    $fixture = createInfbyteCliFixture();
    $releaseRoot = $fixture . '/storage/releases';
    $retained = $fixture . '/storage/sessions/existing-session';

    try {
        file_put_contents($retained, 'retained-session-state');

        [$firstExit, $firstJson] = runInfbyteCommand([
            PHP_BINARY, $fixture . '/infbyte', 'optimize', '--json=1',
        ], ['APP_ENV' => 'production']);
        $first = FixtureData::json($firstJson);

        [$secondExit, $secondJson] = runInfbyteCommand([
            PHP_BINARY, $fixture . '/infbyte', 'optimize', '--json=1',
        ], ['APP_ENV' => 'production']);
        $second = FixtureData::json($secondJson);

        expect($firstExit)->toBe(0, $firstJson)
            ->and($secondExit)->toBe(0, $secondJson)
            ->and($first['generation'])->not->toBe($second['generation']);

        $active = new \Infocyph\Foundation\Release\ActiveGeneration();
        $pointer = $active->activate($releaseRoot, FixtureData::text($first['generation'] ?? null));
        $trusted = new \Infocyph\Foundation\Release\FoundationReleaseRuntime();
        $current = $trusted->trustedActiveManifest($releaseRoot, FixtureData::text($first['manifest_sha256'] ?? null));

        expect($pointer)->toBeFile()
            ->and($current[0])->toBe($first['generation'])
            ->and($current[3])->toBe($first['manifest'])
            ->and(file_get_contents($retained))->toBe('retained-session-state');

        $stale = new \Infocyph\Foundation\Release\FoundationReleaseBootstrap(
            $releaseRoot, FixtureData::text($second['manifest_sha256'] ?? null),
        );
        expect(fn() => $trusted->trustedActiveManifest(
            $stale->releaseRoot, $stale->trustedFoundationManifestSha256,
        ))->toThrow(RuntimeException::class, 'Foundation release artifact trust identity mismatch.');

        [$reportExit, $reportJson] = runInfbyteCommand([
            PHP_BINARY, $fixture . '/infbyte', 'optimize:report', '--json=1',
        ], ['APP_ENV' => 'production']);
        $report = FixtureData::json($reportJson);
        expect($reportExit)->toBe(0, $reportJson)
            ->and($report['generation'] ?? null)->toBe($first['generation'])
            ->and($report['manifest_sha256'] ?? null)->toBe($first['manifest_sha256'])
            ->and(file_get_contents($retained))->toBe('retained-session-state');
    } finally {
        removeInfbyteTestDirectory($fixture);
    }
});

it('checks trusted CLI, worker and scheduler release sections using the same deployment identity', function (): void {
    $fixture = createInfbyteCliFixture();

    try {
        [$code, $output] = runInfbyteCommand([
            PHP_BINARY, $fixture . '/infbyte', 'optimize', '--json=1',
        ], ['APP_ENV' => 'production']);
        $release = FixtureData::json($output);

        expect($code)->toBe(0, $output);
        $bootstrap = new \Infocyph\Foundation\Release\FoundationReleaseBootstrap(
            FixtureData::text($release['release_root'] ?? null), FixtureData::text($release['manifest_sha256'] ?? null),
        );
        foreach ([
            \Infocyph\Foundation\Application\RuntimeMode::Cli,
            \Infocyph\Foundation\Application\RuntimeMode::Worker,
            \Infocyph\Foundation\Application\RuntimeMode::Scheduler,
        ] as $runtime) {
            expect($bootstrap->nonWeb(['base_path' => $fixture], $runtime)->runtimeMode())->toBe($runtime);
        }

        $invalid = new \Infocyph\Foundation\Release\FoundationReleaseBootstrap(
            FixtureData::text($release['release_root'] ?? null), str_repeat('0', 64),
        );
        foreach ([
            \Infocyph\Foundation\Application\RuntimeMode::Cli,
            \Infocyph\Foundation\Application\RuntimeMode::Worker,
            \Infocyph\Foundation\Application\RuntimeMode::Scheduler,
        ] as $runtime) {
            expect(fn() => $invalid->nonWeb(['base_path' => $fixture], $runtime))
                ->toThrow(RuntimeException::class, 'Foundation release artifact trust identity mismatch.');
        }
    } finally {
        removeInfbyteTestDirectory($fixture);
    }
});

/**
 * @param list<string> $arguments
 * @param array<string, string> $environment
 * @return array{0:int,1:string}
 * @throws \InvalidArgumentException
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

/**
 * @throws \RuntimeException
 * @throws \Random\RandomException
 */
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
        $fixture . '/storage/releases',
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
    copyInfbyteTestDirectory($root . '/app', $fixture . '/app');
    copyInfbyteTestDirectory($root . '/config', $fixture . '/config');
    copyInfbyteTestDirectory($root . '/routes', $fixture . '/routes');
    copyInfbyteTestDirectory($root . '/public', $fixture . '/public');
    copy($root . '/bootstrap/providers.php', $fixture . '/bootstrap/providers.php');
    file_put_contents(
        $fixture . '/vendor/autoload.php',
        '<?php return require ' . var_export($root . '/vendor/autoload.php', true) . ';',
    );

    return $fixture;
}

/** @throws \RuntimeException */
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
        \PHPUnit\Framework\Assert::assertInstanceOf(\SplFileInfo::class, $entry);
        $path = $entry->getPathname();

        if ($entry->isLink() || $entry->isFile()) {
            unlink($path);
            continue;
        }

        rmdir($path);
    }

    rmdir($directory);
}
