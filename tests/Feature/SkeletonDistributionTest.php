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

it('excludes repository tests without exporting the exclusion policy', function (): void {
    $attributes = file_get_contents(dirname(__DIR__, 2) . '/.gitattributes');

    expect($attributes)->toBeString()
        ->and($attributes)->toContain('/.gitattributes export-ignore')
        ->and($attributes)->toContain('/tests export-ignore');
});
