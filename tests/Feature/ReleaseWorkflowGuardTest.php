<?php

declare(strict_types=1);

use Symfony\Component\Yaml\Yaml;

it('requires tagged Infbyte releases to pass stable Foundation and published consumer gates', function (): void {
    $workflow = Yaml::parseFile(dirname(__DIR__, 2) . '/.github/workflows/security-standards.yml');
    $jobs = $workflow['jobs'];

    expect($jobs['release']['needs'] ?? [])->toContain('published-core', 'published-database')
        ->and($jobs['release']['if'])->toContain("startsWith(github.ref, 'refs/tags/')")
        ->not->toContain('always(', 'failure(', 'cancelled(')
        ->and($jobs['phpforge']['needs'])->toBe('stable-constraint')
        ->and($jobs['published-core']['needs'])->toBe('phpforge')
        ->and($jobs['published-database']['needs'])->toBe('published-core');

    foreach (['stable-constraint', 'phpforge', 'published-core', 'published-database'] as $job) {
        expect($jobs[$job]['if'] ?? null)->toBeNull();
    }
});

it('validates the actual CI dependency gate across candidate and stable release identities', function (
    string $constraint,
    string $version,
    string $normalized,
    bool $repository,
    array $aliases,
    int $expected,
): void {
    $root = dirname(__DIR__, 2);
    $workflow = Yaml::parseFile($root . '/.github/workflows/foundation4-candidate.yml');
    $steps = array_column($workflow['jobs']['candidate-core']['steps'], 'run', 'name');
    $step = $steps['Verify Composer metadata and resolve the declared Foundation 4 dependency'];
    expect(preg_match('/php -r \'(.*?)\'/s', $step, $matches))->toBe(1);

    $fixture = sys_get_temp_dir() . '/infbyte-version-gate-' . bin2hex(random_bytes(6));
    mkdir($fixture . '/vendor', 0775, true);

    try {
        $manifest = ['require' => ['infocyph/foundation' => $constraint]];
        if ($repository) {
            $manifest['repositories'] = [['type' => 'vcs', 'url' => 'https://github.com/infocyph/Foundation']];
        }
        file_put_contents($fixture . '/composer.json', json_encode($manifest, JSON_THROW_ON_ERROR));
        $installed = ['versions' => ['infocyph/foundation' => [
            'pretty_version' => $version,
            'version' => $normalized,
            'aliases' => $aliases,
        ]]];
        file_put_contents(
            $fixture . '/vendor/autoload.php',
            '<?php require ' . var_export($root . '/vendor/composer/InstalledVersions.php', true)
                . '; Composer\\InstalledVersions::reload(' . var_export($installed, true) . ');',
        );
        exec('cd ' . escapeshellarg($fixture) . ' && ' . escapeshellarg(PHP_BINARY)
            . ' -r ' . escapeshellarg($matches[1]) . ' 2>&1', $output, $status);

        expect($status)->toBe($expected, implode("\n", $output));
    } finally {
        unlink($fixture . '/vendor/autoload.php');
        unlink($fixture . '/composer.json');
        rmdir($fixture . '/vendor');
        rmdir($fixture);
    }
})->with([
    'declared candidate' => ['dev-feature/foundation-3', 'dev-feature/foundation-3', 'dev-feature/foundation-3', true, [], 0],
    'published 4.0' => ['^4.0', '4.0.0', '4.0.0.0', false, [], 0],
    'published 4.1' => ['^4.0', 'v4.1.0', '4.1.0.0', false, [], 0],
    'old major' => ['^4.0', '3.0.1', '3.0.1.0', false, [], 1],
    'next major' => ['^4.0', '5.0.0', '5.0.0.0', false, [], 1],
    'prerelease' => ['^4.0', '4.0.0-RC1', '4.0.0.0-RC1', false, [], 1],
    'development alias' => ['^4.0', 'dev-feature/foundation-3', 'dev-feature/foundation-3', false, ['4.0.0'], 1],
    'stable with VCS override' => ['^4.0', '4.0.0', '4.0.0.0', true, [], 1],
    'wrong candidate branch' => ['dev-feature/foundation-3', 'dev-main', 'dev-main', true, [], 1],
    'unbounded requirement' => ['*', '4.0.0', '4.0.0.0', false, [], 1],
]);
