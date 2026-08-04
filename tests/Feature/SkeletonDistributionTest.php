<?php

declare(strict_types=1);

it('ships only core application configuration before module installation', function (): void {
    $config = array_map(
        basename(...),
        glob(dirname(__DIR__, 2) . '/config/*.php') ?: [],
    );

    sort($config);

    expect($config)->toBe([
        'app.php',
        'auth.php',
        'ids.php',
        'router.php',
    ]);
});

it('exports example tests while excluding repository verification tests', function (): void {
    $attributes = file_get_contents(dirname(__DIR__, 2) . '/.gitattributes');

    expect($attributes)->toBeString()
        ->and($attributes)->toContain('/.gitattributes export-ignore')
        ->and($attributes)->not->toContain("/tests export-ignore\n")
        ->and($attributes)->toContain('/tests/Feature/FrameworkRuntimeTest.php export-ignore')
        ->and($attributes)->toContain('/tests/Feature/RouteCacheCliTest.php export-ignore')
        ->and($attributes)->toContain('/tests/Feature/SkeletonDistributionTest.php export-ignore');
});

it('builds a clean create-project archive and provisions its environment', function (): void {
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

        $sourceComposer = json_decode(
            (string) file_get_contents($root . '/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $exportedComposer = json_decode(
            (string) file_get_contents($project . '/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $cachedPhpFiles = [];
        $cacheFiles = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $project . '/bootstrap/cache',
                FilesystemIterator::SKIP_DOTS,
            ),
        );
        foreach ($cacheFiles as $cacheFile) {
            if ($cacheFile->isFile() && $cacheFile->getExtension() === 'php') {
                $cachedPhpFiles[] = $cacheFile->getPathname();
            }
        }

        expect($project . '/composer.json')->toBeFile()
            ->and($project . '/bootstrap/install.php')->toBeFile()
            ->and($project . '/bootstrap/cache/config/.gitignore')->toBeFile()
            ->and($project . '/bootstrap/cache/console/.gitignore')->toBeFile()
            ->and($project . '/bootstrap/cache/routes/.gitignore')->toBeFile()
            ->and($project . '/storage/cache/.gitignore')->toBeFile()
            ->and($project . '/storage/logs/.gitignore')->toBeFile()
            ->and($project . '/storage/sessions/.gitignore')->toBeFile()
            ->and($project . '/storage/uploads/.gitignore')->toBeFile()
            ->and($project . '/.env.example')->toBeFile()
            ->and($project . '/.gitattributes')->not->toBeFile()
            ->and($project . '/.github')->not->toBeDirectory()
            ->and($project . '/captainhook.json')->not->toBeFile()
            ->and($project . '/composer.lock')->not->toBeFile()
            ->and($project . '/CODE_OF_CONDUCT.md')->not->toBeFile()
            ->and($project . '/CONTRIBUTING.md')->not->toBeFile()
            ->and($project . '/plan.md')->not->toBeFile()
            ->and($project . '/SECURITY.md')->not->toBeFile()
            ->and($project . '/tests/Pest.php')->toBeFile()
            ->and($project . '/tests/Feature/ExampleTest.php')->toBeFile()
            ->and($project . '/tests/Unit/ExampleTest.php')->toBeFile()
            ->and($project . '/tests/Feature/FrameworkRuntimeTest.php')->not->toBeFile()
            ->and($project . '/tests/Feature/RouteCacheCliTest.php')->not->toBeFile()
            ->and($project . '/tests/Feature/SkeletonDistributionTest.php')->not->toBeFile()
            ->and($project . '/vendor')->not->toBeDirectory()
            ->and($project . '/bootstrap/cache/config/__manifest.php')->not->toBeFile()
            ->and($project . '/database')->not->toBeDirectory()
            ->and($cachedPhpFiles)->toBe([])
            ->and($exportedComposer['require']['infocyph/foundation'] ?? null)
            ->toBe($sourceComposer['require']['infocyph/foundation'] ?? null)
            ->and($exportedComposer['scripts']['post-create-project-cmd'] ?? null)
            ->toBe('@php bootstrap/install.php');

        $installOutput = [];
        $installExitCode = 0;
        exec(sprintf(
            'cd %s && composer run-script post-create-project-cmd --no-interaction 2>&1',
            escapeshellarg($project),
        ), $installOutput, $installExitCode);

        expect($installExitCode)->toBe(0, implode("\n", $installOutput))
            ->and($project . '/.env')->toBeFile()
            ->and(fileperms($project . '/.env') & 0777)->toBe(0600);

        $environment = file_get_contents($project . '/.env');
        expect($environment)->toBeString()
            ->and($environment)->toMatch('/^AUTH_TOKEN_SECRET=[a-f0-9]{64}$/m')
            ->and($environment)->not->toContain('AUTH_TOKEN_SECRET=foundation-dev-secret');

        $before = hash_file('sha256', $project . '/.env');
        exec(sprintf(
            'cd %s && composer run-script post-create-project-cmd --no-interaction 2>&1',
            escapeshellarg($project),
        ), $installOutput, $installExitCode);

        expect($installExitCode)->toBe(0)
            ->and(hash_file('sha256', $project . '/.env'))->toBe($before);
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
        if ($file->isDir()) {
            rmdir($file->getPathname());
        } else {
            unlink($file->getPathname());
        }
    }

    rmdir($directory);
}
