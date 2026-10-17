<?php

declare(strict_types=1);

use Composer\InstalledVersions;

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

        $build = json_decode($buildOutput, true, flags: JSON_THROW_ON_ERROR);
        $manifest = $build['manifest'] ?? null;

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
        $report = json_decode($reportOutput, true, flags: JSON_THROW_ON_ERROR);

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
        $empty = json_decode($emptyOutput, true, flags: JSON_THROW_ON_ERROR);

        expect($emptyExitCode)->toBe(0, $emptyOutput)
            ->and($empty['ready'] ?? null)->toBeFalse();
    } finally {
        removeInfbyteTestDirectory($fixture);
    }
});


it('ignores retired Foundation 2.1 generated artifacts during release migration', function (): void {
    $fixture = createInfbyteCliFixture();

    try {
        foreach ([
            $fixture . '/bootstrap/cache/container',
            $fixture . '/bootstrap/cache/routes',
        ] as $directory) {
            if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new RuntimeException(sprintf('Unable to create legacy cache directory "%s".', $directory));
            }
        }

        file_put_contents(
            $fixture . '/bootstrap/cache/container/web.php',
            "<?php throw new RuntimeException('Legacy Foundation 2 container artifact was loaded.');\n",
        );
        file_put_contents(
            $fixture . '/bootstrap/cache/routes/fused.php',
            "<?php throw new RuntimeException('Legacy Foundation 2 route artifact was loaded.');\n",
        );

        [$exitCode, $output] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'optimize',
            '--json=1',
        ], [
            'APP_ENV' => 'production',
            'APP_CONTAINER_ALIAS' => 'legacy',
            'APP_CONTAINER_COMPILED_ACTIVATION' => 'always',
            'ROUTER_CACHE' => 'true',
        ]);

        $release = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
        $generation = $release['generation'] ?? null;

        expect($exitCode)->toBe(0, $output)
            ->and($generation)->toBeString()->not->toBe('')
            ->and($fixture . '/storage/releases/generations/' . $generation . '/foundation.php')->toBeFile()
            ->and($fixture . '/storage/releases/generations/' . $generation . '/config.php')->toBeFile();
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

        $readiness = json_decode($readinessOutput, true, flags: JSON_THROW_ON_ERROR);
        $moduleRows = json_decode($modulesOutput, true, flags: JSON_THROW_ON_ERROR);

        expect($readinessExitCode)->toBe(0, $readinessOutput)
            ->and($readiness['ready'])->toBeTrue()
            ->and($readiness)->toHaveKey('checks')
            ->and($modulesExitCode)->toBe(0)
            ->and($moduleRows)->toBeArray();

        $modules = array_column($moduleRows['modules'] ?? [], null, 'name');

        expect($moduleRows['schema_version'] ?? null)->toBe(2)
            ->and(array_keys($modules))->toBe(['data', 'security', 'execution'])
            ->and($modules['data']['features'])->toBe(['data.database', 'data.filesystem'])
            ->and($modules['security']['features'])->toBe([
                'security.crypto',
                'security.validation',
                'security.mfa',
                'security.passkey',
            ])
            ->and($modules['execution']['features'])->toBe([
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

        $schema = json_decode($schemaOutput, true, flags: JSON_THROW_ON_ERROR);

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

        $plan = json_decode($output, true, flags: JSON_THROW_ON_ERROR);

        expect($exitCode)->toBe(0, $output)
            ->and($plan['schema_version'] ?? null)->toBe(2)
            ->and($plan['requested'] ?? null)->toContain('data.database')
            ->and($output)->toContain('infocyph/dblayer')
            ->and($output)->toContain('^6.1.1');
    } finally {
        removeInfbyteTestDirectory($fixture);
    }
});

it('rejects Foundation 3 module aliases instead of silently installing legacy capabilities', function (): void {
    $fixture = createInfbyteCliFixture();

    try {
        [$exitCode, $output] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'module:plan',
            'database',
            '--json=1',
        ]);

        expect($exitCode)->not->toBe(0)
            ->and(strtolower($output))->toContain('database');
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
        $result = json_decode($output, true, flags: JSON_THROW_ON_ERROR);

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
        $auto = json_decode($autoOutput, true, flags: JSON_THROW_ON_ERROR);

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
        $release = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
        $manifest = $release['manifest'] ?? null;
        $digest = $release['manifest_sha256'] ?? null;

        expect($code)->toBe(0, $output)
            ->and($manifest)->toBeString()->toBeFile()
            ->and($digest)->toBeString()->toMatch('/^[a-f0-9]{64}$/');

        $bootstrap = new \Infocyph\Foundation\Release\FoundationReleaseBootstrap(
            $fixture . '/storage/releases',
            $digest,
        );
        expect($bootstrap->web(['base_path' => $fixture]))->toBeInstanceOf(
            \Infocyph\Foundation\Routing\WebReleaseRuntime::class,
        );

        file_put_contents($manifest, "\n/* trust-digest tamper */\n", FILE_APPEND);
        expect(fn() => $bootstrap->web(['base_path' => $fixture]))
            ->toThrow(RuntimeException::class, 'Foundation generation manifest trust identity mismatch.');
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
        $first = json_decode($firstOutput, true, flags: JSON_THROW_ON_ERROR);

        [$nextExit, $nextOutput] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'optimize',
            '--json=1',
        ], ['APP_ENV' => 'production']);
        $next = json_decode($nextOutput, true, flags: JSON_THROW_ON_ERROR);

        expect($firstExit)->toBe(0, $firstOutput)
            ->and($nextExit)->toBe(0, $nextOutput)
            ->and($first['generation'])->not->toBe($next['generation'])
            ->and($next['manifest'])->toBeFile()
            ->and($next['manifest_sha256'])->toBe(hash_file('sha256', $next['manifest']))
            ->and($retained)->toBeFile()
            ->and(file_get_contents($retained))->toBe('application-owned-file');

        [$reportExit, $reportOutput] = runInfbyteCommand([
            PHP_BINARY,
            $fixture . '/infbyte',
            'optimize:report',
            '--json=1',
        ], ['APP_ENV' => 'production']);
        $report = json_decode($reportOutput, true, flags: JSON_THROW_ON_ERROR);

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
        $first = json_decode($firstJson, true, flags: JSON_THROW_ON_ERROR);

        [$secondExit, $secondJson] = runInfbyteCommand([
            PHP_BINARY, $fixture . '/infbyte', 'optimize', '--json=1',
        ], ['APP_ENV' => 'production']);
        $second = json_decode($secondJson, true, flags: JSON_THROW_ON_ERROR);

        expect($firstExit)->toBe(0, $firstJson)
            ->and($secondExit)->toBe(0, $secondJson)
            ->and($first['generation'])->not->toBe($second['generation']);

        $active = new \Infocyph\Foundation\Release\ActiveGeneration();
        $pointer = $active->activate($releaseRoot, $first['generation']);
        $previous = new \Infocyph\Foundation\Release\FoundationReleaseBootstrap(
            $releaseRoot, $first['manifest_sha256'],
        );

        $trusted = new \Infocyph\Foundation\Release\FoundationReleaseRuntime();
        $current = $trusted->trustedActiveManifest($releaseRoot, $first['manifest_sha256']);

        expect($pointer)->toBeFile()
            ->and($current[0])->toBe($first['generation'])
            ->and($current[3])->toBe($first['manifest'])
            ->and(file_get_contents($retained))->toBe('retained-session-state');

        $stale = new \Infocyph\Foundation\Release\FoundationReleaseBootstrap(
            $releaseRoot, $second['manifest_sha256'],
        );
        expect(fn() => $trusted->trustedActiveManifest(
            $stale->releaseRoot, $stale->trustedFoundationManifestSha256,
        ))->toThrow(RuntimeException::class, 'Foundation generation manifest trust identity mismatch.');

        [$reportExit, $reportJson] = runInfbyteCommand([
            PHP_BINARY, $fixture . '/infbyte', 'optimize:report', '--json=1',
        ], ['APP_ENV' => 'production']);
        $report = json_decode($reportJson, true, flags: JSON_THROW_ON_ERROR);
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
        $release = json_decode($output, true, flags: JSON_THROW_ON_ERROR);

        expect($code)->toBe(0, $output);
        $bootstrap = new \Infocyph\Foundation\Release\FoundationReleaseBootstrap(
            $release['release_root'], $release['manifest_sha256'],
        );
        foreach ([
            \Infocyph\Foundation\Application\RuntimeMode::Cli,
            \Infocyph\Foundation\Application\RuntimeMode::Worker,
            \Infocyph\Foundation\Application\RuntimeMode::Scheduler,
        ] as $runtime) {
            expect($bootstrap->nonWeb(['base_path' => $fixture], $runtime))
                ->toBeInstanceOf(\Infocyph\Foundation\Application\Application::class);
        }

        $invalid = new \Infocyph\Foundation\Release\FoundationReleaseBootstrap(
            $release['release_root'], str_repeat('0', 64),
        );
        foreach ([
            \Infocyph\Foundation\Application\RuntimeMode::Cli,
            \Infocyph\Foundation\Application\RuntimeMode::Worker,
            \Infocyph\Foundation\Application\RuntimeMode::Scheduler,
        ] as $runtime) {
            expect(fn() => $invalid->nonWeb(['base_path' => $fixture], $runtime))
                ->toThrow(RuntimeException::class, 'Foundation generation manifest trust identity mismatch.');
        }
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
