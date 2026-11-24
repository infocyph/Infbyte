<?php

declare(strict_types=1);

namespace Tests\Feature;

use Symfony\Component\Yaml\Yaml;
use Tests\Support\FixtureData;

it('reports the existing required PHPForge checks on pull requests', function (): void {
    $workflow = FixtureData::map(Yaml::parseFile(dirname(__DIR__, 2) . '/.github/workflows/security-standards.yml'));
    $jobs = FixtureData::map($workflow['jobs'] ?? null);
    $phpforge = FixtureData::map($jobs['phpforge'] ?? null);
    $triggers = FixtureData::map($workflow['on'] ?? null);
    $pullRequest = FixtureData::map($triggers['pull_request'] ?? null);
    $inputs = FixtureData::map($phpforge['with'] ?? null);

    expect($workflow['name'])->toBe('Security & Standards')
        ->and($pullRequest['branches'] ?? [])->toContain('main', 'master', 'develop', 'development')
        ->and($phpforge)->not->toHaveKey('name')
        ->not->toHaveKey('if')
        ->not->toHaveKey('needs')
        ->and($phpforge['uses'])->toBe('infocyph/phpforge/.github/workflows/security-standards.yml@main')
        ->and(json_decode(FixtureData::text($inputs['php_versions'] ?? null), true, flags: JSON_THROW_ON_ERROR))->toBe(['8.4', '8.5', '8.6'])
        ->and(json_decode(FixtureData::text($inputs['dependency_versions'] ?? null), true, flags: JSON_THROW_ON_ERROR))->toBe(['prefer-lowest', 'prefer-stable'])
        ->and($inputs['fail_on_skipped_tests'] ?? null)->toBeTrue();
});

it('requires tagged Infbyte releases to pass stable Foundation and published consumer gates', function (): void {
    $workflow = FixtureData::map(Yaml::parseFile(dirname(__DIR__, 2) . '/.github/workflows/security-standards.yml'));
    $jobs = FixtureData::map($workflow['jobs'] ?? null);
    $release = FixtureData::map($jobs['release'] ?? null);
    $stable = FixtureData::map($jobs['stable-constraint'] ?? null);
    $core = FixtureData::map($jobs['published-core'] ?? null);
    $database = FixtureData::map($jobs['published-database'] ?? null);

    expect($release['needs'] ?? [])->toContain('published-core', 'published-database')
        ->and($release['if'])->toContain("startsWith(github.ref, 'refs/tags/')")
        ->not->toContain('always(', 'failure(', 'cancelled(')
        ->and($stable)->not->toHaveKey('if')
        ->and($core['needs'])->toContain('stable-constraint', 'phpforge')
        ->and($database['needs'])->toBe('published-core');

    foreach (['phpforge', 'published-core', 'published-database'] as $job) {
        expect(FixtureData::map($jobs[$job] ?? null)['if'] ?? null)->toBeNull();
    }
});

it('runs source QA before creating any compiled release generation', function (): void {
    $workflow = FixtureData::map(Yaml::parseFile(dirname(__DIR__, 2) . '/.github/workflows/foundation4-candidate.yml'));
    $jobs = FixtureData::map($workflow['jobs'] ?? null);
    $core = FixtureData::map($jobs['candidate-core'] ?? null);
    $names = array_column(FixtureData::rows($core['steps'] ?? null), 'name');
    $qa = array_search('Run configured PHPForge CI (no suppressed failures)', $names, true);
    $build = array_search('Prepare application and compile one trusted Core release', $names, true);
    \PHPUnit\Framework\Assert::assertIsInt($qa);
    \PHPUnit\Framework\Assert::assertIsInt($build);
    expect($qa)->toBeLessThan($build);
});

it('validates the actual CI dependency gate against stable and rejected release identities', function (
    string $constraint,
    string $version,
    string $normalized,
    bool $repository,
    array $aliases,
    int $expected,
): void {
    $root = dirname(__DIR__, 2);
    $workflow = FixtureData::map(Yaml::parseFile($root . '/.github/workflows/foundation4-candidate.yml'));
    $jobs = FixtureData::map($workflow['jobs'] ?? null);
    $core = FixtureData::map($jobs['candidate-core'] ?? null);
    $steps = array_column(FixtureData::rows($core['steps'] ?? null), 'run', 'name');
    $step = FixtureData::text($steps['Verify Composer metadata and resolve the declared Foundation 4 dependency'] ?? null);
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
            . ' -r ' . escapeshellarg(FixtureData::text($matches[1] ?? null)) . ' 2>&1', $output, $status);

        expect($status)->toBe($expected, implode("\n", $output));
    } finally {
        unlink($fixture . '/vendor/autoload.php');
        unlink($fixture . '/composer.json');
        rmdir($fixture . '/vendor');
        rmdir($fixture);
    }
})->with([
    'development branch' => ['dev-feature/example', 'dev-feature/example', 'dev-feature/example', true, [], 1],
    'published 4.0' => ['^4.0', '4.0.0', '4.0.0.0', false, [], 0],
    'published 4.1' => ['^4.0', 'v4.1.0', '4.1.0.0', false, [], 0],
    'old major' => ['^4.0', '3.0.1', '3.0.1.0', false, [], 1],
    'next major' => ['^4.0', '5.0.0', '5.0.0.0', false, [], 1],
    'prerelease' => ['^4.0', '4.0.0-RC1', '4.0.0.0-RC1', false, [], 1],
    'development alias' => ['^4.0', 'dev-feature/example', 'dev-feature/example', false, ['4.0.0'], 1],
    'stable with VCS override' => ['^4.0', '4.0.0', '4.0.0.0', true, [], 1],
    'wrong development branch' => ['dev-feature/example', 'dev-main', 'dev-main', true, [], 1],
    'unbounded requirement' => ['*', '4.0.0', '4.0.0.0', false, [], 1],
]);
