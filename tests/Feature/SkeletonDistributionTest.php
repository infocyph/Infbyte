<?php

declare(strict_types=1);

namespace Tests\Feature;

use FilesystemIterator;
use PharData;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\Support\FixtureData;

it('ships only core application configuration before module installation', function (): void {
    $files = glob(dirname(__DIR__, 2) . '/config/*.php');
    \PHPUnit\Framework\Assert::assertIsArray($files);
    $config = array_map(basename(...), $files);

    sort($config);

    expect($config)->toBe([
        'app.php',
        'auth.php',
        'cache.php',
        'modules.php',
        'notifications.php',
        'router.php',
    ]);
});

it('exports example tests while excluding repository verification tests', function (): void {
    $attributes = file_get_contents(dirname(__DIR__, 2) . '/.gitattributes');

    expect($attributes)->toBeString()
        ->and($attributes)->toContain('/.gitattributes export-ignore')
        ->and($attributes)->not->toContain("/tests export-ignore\n")
        ->and($attributes)->toContain('/tests/Feature/* export-ignore')
        ->and($attributes)->toContain('/tests/Feature/ExampleTest.php -export-ignore')
        ->and($attributes)->toContain('/tests/Unit/* export-ignore')
        ->and($attributes)->toContain('/tests/Unit/ExampleTest.php -export-ignore')
        ->and($attributes)->toContain('/docs export-ignore')
        ->and($attributes)->toContain('/tests/ci export-ignore')
        ->and($attributes)->toContain('/tests/Support export-ignore')
        ->and($attributes)->toContain('/graphify-out export-ignore')
        ->and($attributes)->toContain('/bootstrap/cache/* export-ignore')
        ->and($attributes)->toContain('/bootstrap/cache/.gitignore -export-ignore')
        ->and($attributes)->toContain('/storage/releases/* export-ignore')
        ->and($attributes)->toContain('/storage/releases/.gitignore -export-ignore');
});

it('uses the trusted Foundation runtime as the production web entrypoint', function (): void {
    $entrypoint = file_get_contents(dirname(__DIR__, 2) . '/public/index.php');

    expect($entrypoint)->toBeString()
        ->and($entrypoint)->toContain('FoundationReleaseBootstrap::fromEnvironment')
        ->and($entrypoint)->toContain('->web($config)->server->handle()')
        ->and($entrypoint)->not->toContain('Request::fromGlobals')
        ->and($entrypoint)->not->toContain('AutoEmitter');
});

it('builds a clean create-project archive and provisions a Foundation 4 release generation', function (): void {
    $root = dirname(__DIR__, 2);
    $fixture = sys_get_temp_dir() . '/infbyte-distribution-' . bin2hex(random_bytes(6));
    $archiveBase = $fixture . '/infbyte';
    $archive = $archiveBase . '.tar';
    $project = $fixture . '/project';
    mkdir($fixture, 0775, true);
    mkdir($project, 0775, true);

    try {
        $output = [];
        $exitCode = 0;
        exec(sprintf(
            'cd %s && composer archive --format=tar --dir=%s --file=%s 2>&1',
            escapeshellarg($root),
            escapeshellarg($fixture),
            escapeshellarg('infbyte'),
        ), $output, $exitCode);

        expect($exitCode)->toBe(0, implode("\n", $output))
            ->and($archive)->toBeFile();

        new PharData($archive)->extractTo($project);

        $sourceComposer = FixtureData::json((string) file_get_contents($root . '/composer.json'));
        $exportedComposer = FixtureData::json((string) file_get_contents($project . '/composer.json'));

        expect($project . '/composer.json')->toBeFile()
            ->and($project . '/bootstrap/install.php')->not->toBeFile()
            ->and($project . '/bootstrap/cache/.gitignore')->toBeFile()
            ->and(scandir($project . '/bootstrap/cache'))->toBe(['.', '..', '.gitignore'])
            ->and($project . '/bootstrap/cache/config')->not->toBeDirectory()
            ->and($project . '/bootstrap/cache/container')->not->toBeDirectory()
            ->and($project . '/bootstrap/cache/routes')->not->toBeDirectory()
            ->and($project . '/storage/cache/.gitignore')->toBeFile()
            ->and(scandir($project . '/storage/cache'))->toBe(['.', '..', '.gitignore'])
            ->and($project . '/storage/logs/.gitignore')->toBeFile()
            ->and(scandir($project . '/storage/logs'))->toBe(['.', '..', '.gitignore'])
            ->and($project . '/storage/releases/.gitignore')->toBeFile()
            ->and(scandir($project . '/storage/releases'))->toBe(['.', '..', '.gitignore'])
            ->and($project . '/storage/sessions/.gitignore')->toBeFile()
            ->and(scandir($project . '/storage/sessions'))->toBe(['.', '..', '.gitignore'])
            ->and($project . '/storage/uploads/.gitignore')->toBeFile()
            ->and(scandir($project . '/storage/uploads'))->toBe(['.', '..', '.gitignore'])
            ->and($project . '/.env.example')->toBeFile()
            ->and($project . '/.env')->not->toBeFile()
            ->and($project . '/.gitignore')->toBeFile()
            ->and($project . '/infbyte')->toBeFile()
            ->and($project . '/deploy.sh')->toBeFile()
            ->and($project . '/public/.htaccess')->toBeFile()
            ->and($project . '/app/Http/Controllers/SystemController.php')->toBeFile()
            ->and($project . '/.gitattributes')->not->toBeFile()
            ->and($project . '/.github')->not->toBeDirectory()
            ->and($project . '/docs')->not->toBeDirectory()
            ->and($project . '/graphify-out')->not->toBeDirectory()
            ->and($project . '/tests/ci')->not->toBeDirectory()
            ->and($project . '/tests/Support')->not->toBeDirectory()
            ->and($project . '/tests/Feature/InfbyteManualCompatibilityTest.php')->not->toBeFile()
            ->and($project . '/config/modules.php')->toBeFile()
            ->and($project . '/.phpforge-report')->not->toBeDirectory()
            ->and($project . '/captainhook.json')->not->toBeFile()
            ->and($project . '/composer.lock')->not->toBeFile()
            ->and($project . '/CODE_OF_CONDUCT.md')->not->toBeFile()
            ->and($project . '/CONTRIBUTING.md')->not->toBeFile()
            ->and($project . '/plan.md')->not->toBeFile()
            ->and($project . '/SECURITY.md')->not->toBeFile()
            ->and($project . '/tests/Pest.php')->toBeFile()
            ->and($project . '/tests/Feature/ExampleTest.php')->toBeFile()
            ->and($project . '/tests/Unit/ExampleTest.php')->toBeFile()
            ->and(scandir($project . '/tests'))->toBe(['.', '..', 'Feature', 'Pest.php', 'Unit'])
            ->and(scandir($project . '/tests/Feature'))->toBe(['.', '..', 'ExampleTest.php'])
            ->and(scandir($project . '/tests/Unit'))->toBe(['.', '..', 'ExampleTest.php'])
            ->and($project . '/tests/Feature/FrameworkRuntimeTest.php')->not->toBeFile()
            ->and($project . '/tests/Feature/ReleaseRuntimeCliTest.php')->not->toBeFile()
            ->and($project . '/tests/Feature/ReleaseWorkflowGuardTest.php')->not->toBeFile()
            ->and($project . '/tests/Feature/SkeletonDistributionTest.php')->not->toBeFile()
            ->and($project . '/vendor')->not->toBeDirectory()
            ->and($project . '/database')->not->toBeDirectory()
            ->and(FixtureData::map($exportedComposer['require'] ?? null)['infocyph/foundation'] ?? null)
            ->toBe(FixtureData::map($sourceComposer['require'] ?? null)['infocyph/foundation'] ?? null)
            ->and(FixtureData::map($exportedComposer['scripts'] ?? null)['post-create-project-cmd'] ?? null)
            ->toBe('@php infbyte app:install');

        mkdir($project . '/vendor', 0775, true);
        file_put_contents(
            $project . '/vendor/autoload.php',
            '<?php return require ' . var_export($root . '/vendor/autoload.php', true) . ';',
        );

        $installOutput = [];
        $installExitCode = 0;
        exec(sprintf(
            'cd %s && composer run-script post-create-project-cmd --no-interaction 2>&1',
            escapeshellarg($project),
        ), $installOutput, $installExitCode);

        expect($installExitCode)->toBe(0, implode("\n", $installOutput))
            ->and($project . '/.env')->toBeFile();
        $permissions = fileperms($project . '/.env');
        \PHPUnit\Framework\Assert::assertIsInt($permissions);
        expect($permissions & 0777)->toBe(0600);

        $environment = file_get_contents($project . '/.env');
        expect($environment)->toBeString()
            ->and($environment)->toMatch('/^AUTH_TOKEN_SECRET=[a-f0-9]{64}$/m')
            ->and($environment)->toContain("APP_CAPABILITIES=\n")
            ->and($environment)->not->toContain('AUTH_TOKEN_SECRET=foundation-dev-secret');

        $before = hash_file('sha256', $project . '/.env');
        exec(sprintf(
            'cd %s && composer run-script post-create-project-cmd --no-interaction 2>&1',
            escapeshellarg($project),
        ), $installOutput, $installExitCode);

        expect($installExitCode)->toBe(0)
            ->and(hash_file('sha256', $project . '/.env'))->toBe($before);

        $deployOutput = [];
        $deployExitCode = 0;
        exec(sprintf(
            'cd %s && bash ./deploy.sh 2>&1',
            escapeshellarg($project),
        ), $deployOutput, $deployExitCode);

        expect($deployExitCode)->toBe(0, implode("\n", $deployOutput))
            ->and($project . '/storage/releases/active.json')->toBeFile()
            ->and(implode("\n", $deployOutput))->toContain('INFOCYPH_FOUNDATION_RELEASE_ROOT')
            ->and(implode("\n", $deployOutput))->toContain('INFOCYPH_FOUNDATION_RELEASE_MANIFEST_SHA256');

        $reportOutput = [];
        $reportExitCode = 0;
        exec(sprintf(
            'cd %s && %s infbyte optimize:report --json=1 2>&1',
            escapeshellarg($project),
            escapeshellarg(PHP_BINARY),
        ), $reportOutput, $reportExitCode);
        $report = FixtureData::json(implode("\n", $reportOutput));
        $generation = FixtureData::text($report['generation'] ?? null);

        expect($reportExitCode)->toBe(0)
            ->and($report['ready'] ?? null)->toBeTrue()
            ->and($generation)->not->toBe('')
            ->and($report['manifest_sha256'] ?? null)->toMatch('/^[a-f0-9]{64}$/')
            ->and($project . '/storage/releases/generations/' . $generation . '/foundation.php')->toBeFile()
            ->and($project . '/storage/releases/generations/' . $generation . '/config.php')->toBeFile();

        [$activeGeneration, $manifest] = new \Infocyph\Foundation\Release\FoundationReleaseRuntime()->trustedActiveManifest(
            $project . '/storage/releases',
            FixtureData::text($report['manifest_sha256'] ?? null),
        );
        expect($activeGeneration)->toBe($generation)
            ->and($manifest)->toHaveKeys(['web', 'cli', 'worker', 'scheduler']);

        $clearOutput = [];
        $clearExitCode = 0;
        exec(sprintf(
            'cd %s && %s infbyte optimize:clear 2>&1',
            escapeshellarg($project),
            escapeshellarg(PHP_BINARY),
        ), $clearOutput, $clearExitCode);

        expect($clearExitCode)->toBe(0, implode("\n", $clearOutput))
            ->and($project . '/storage/releases/active.json')->not->toBeFile();
    } finally {
        removeInfbyteDistributionFixture($fixture);
    }
});

function removeInfbyteDistributionFixture(string $directory): void
{
    if (!is_dir($directory)) {
        return;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($files as $file) {
        \PHPUnit\Framework\Assert::assertInstanceOf(\SplFileInfo::class, $file);
        if ($file->isDir() && !$file->isLink()) {
            rmdir($file->getPathname());
        } else {
            unlink($file->getPathname());
        }
    }

    rmdir($directory);
}
