<?php

declare(strict_types=1);

use Infocyph\Foundation\Application\Application;
use Infocyph\Foundation\Application\RuntimeMode;
use Infocyph\Foundation\Config\ConfigRepository;
use Infocyph\Foundation\Filesystem\PathManager;
use Infocyph\Foundation\Foundation;
use Infocyph\Foundation\Http\HttpKernel;
use Infocyph\Foundation\Runtime\ExecutionScope;
use Infocyph\Webrick\Request\Request;

/** @return array<string, mixed> */
function infbyteTestOptions(): array
{
    $root = dirname(__DIR__, 2);
    $runtime = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
        . '/infbyte-runtime-'
        . getmypid();

    foreach (['cache', 'logs', 'sessions', 'uploads'] as $directory) {
        $path = $runtime . DIRECTORY_SEPARATOR . $directory;

        if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
            throw new RuntimeException(sprintf('Unable to create test runtime directory "%s".', $path));
        }
    }

    return [
        'base_path' => $root,
        '_config_cache' => false,
        'app' => [
            'env' => 'testing',
        ],
        'paths' => [
            'storage' => $runtime,
            'cache' => $runtime . '/cache',
            'logs' => $runtime . '/logs',
            'sessions' => $runtime . '/sessions',
            'uploads' => $runtime . '/uploads',
        ],
        'router' => [
            'cache' => false,
        ],
    ];
}

it('constructs exactly the four explicit Foundation runtimes', function (): void {
    $options = infbyteTestOptions();
    $cases = [
        [Foundation::web(...), RuntimeMode::Web, 'runningInWeb'],
        [Foundation::cli(...), RuntimeMode::Cli, 'runningInCli'],
        [Foundation::worker(...), RuntimeMode::Worker, 'runningInWorker'],
        [Foundation::scheduler(...), RuntimeMode::Scheduler, 'runningInScheduler'],
    ];

    foreach ($cases as [$factory, $mode, $predicate]) {
        /** @var callable(array<string, mixed>):Application $factory */
        $app = $factory($options);

        expect($app)->toBeInstanceOf(Application::class)
            ->and($app->runtimeMode())->toBe($mode)
            ->and($app->{$predicate}())->toBeTrue()
            ->and($app->booted())->toBeFalse()
            ->and($app->environment())->toBe('testing')
            ->and($app->basePath())->toBe(dirname(__DIR__, 2));
    }

    expect(method_exists(Foundation::class, 'console'))->toBeFalse();
});

it('keeps HTTP unavailable outside the web runtime', function (): void {
    $app = Foundation::cli(infbyteTestOptions())->boot();

    expect($app->runningInCli())->toBeTrue()
        ->and($app->booted())->toBeTrue()
        ->and(fn() => $app->http())
        ->toThrow(LogicException::class, 'The HTTP kernel is unavailable in the cli runtime.');
});

it('resolves only the narrow Foundation application core directly', function (): void {
    $app = Foundation::web(infbyteTestOptions())->boot();

    expect($app->make(Application::class))->toBe($app)
        ->and($app->config())->toBeInstanceOf(ConfigRepository::class)
        ->and($app->paths())->toBeInstanceOf(PathManager::class)
        ->and($app->execution())->toBeInstanceOf(ExecutionScope::class)
        ->and($app->http())->toBeInstanceOf(HttpKernel::class)
        ->and($app->booted())->toBeTrue();

    foreach ([
        'auth',
        'authManager',
        'cache',
        'database',
        'filesystem',
        'ids',
        'notifications',
        'router',
        'testing',
        'validation',
    ] as $retiredConvenienceMethod) {
        expect(method_exists($app, $retiredConvenienceMethod))->toBeFalse();
    }
});

it('defines application commands through the explicit command route file', function (): void {
    $commands = require dirname(__DIR__, 2) . '/routes/console.php';

    expect($commands)->toBeArray()->toBe([]);
});

it('keeps Foundation system commands out of application Composer script keys', function (): void {
    $composer = json_decode(
        (string) file_get_contents(dirname(__DIR__, 2) . '/composer.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
    $scripts = $composer['scripts'] ?? [];

    expect($scripts)->toBeArray();

    foreach ([
        'app:ready',
        'config:cache',
        'db:monitor',
        'module:list',
        'optimize',
        'route:cache',
        'schedule:run',
        'worker:run',
    ] as $systemCommand) {
        expect(array_key_exists($systemCommand, $scripts))->toBeFalse();
    }
});

it('serves the skeleton routes through canonical Foundation web handling', function (): void {
    $app = Foundation::web(infbyteTestOptions());

    $health = $app->handle(Request::fake(method: 'GET', uri: 'http://localhost/api/health'));
    $json = $app->handle(Request::fake(method: 'GET', uri: 'http://localhost/json'));

    expect($app->booted())->toBeTrue()
        ->and($health->getStatusCode())->toBe(200)
        ->and((string) $health->getBody())->toContain('"status":"ok"')
        ->and($json->getStatusCode())->toBe(200)
        ->and((string) $json->getBody())->toContain('"memory"');
});
