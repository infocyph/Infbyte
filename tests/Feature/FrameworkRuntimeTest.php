<?php

declare(strict_types=1);

use Infocyph\Foundation\Application\Application;
use Infocyph\Foundation\Auth\Contract\Notification\AuthNotifierInterface;
use Infocyph\Foundation\Cache\CacheManager;
use Infocyph\Console\Command\CommandContract;
use Infocyph\Foundation\Database\DatabaseManager;
use Infocyph\Foundation\Filesystem\FilesystemManager;
use Infocyph\Foundation\Foundation;
use Infocyph\Foundation\Http\HttpKernel;
use Infocyph\Foundation\Notifications\NotificationManager;
use Infocyph\Foundation\Routing\RouteFileLoader;
use Infocyph\Foundation\Routing\RouterManager;
use Infocyph\Foundation\Validation\ValidationManager;
use Infocyph\Webrick\Request\Request;

function infbyteApp(): Application
{
    return Foundation::web(infbyteTestOptions());
}

/**
 * @return array<string, mixed>
 */
function infbyteTestOptions(): array
{
    $root = dirname(__DIR__, 2);
    $runtime = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
        . '/infbyte-runtime-'
        . getmypid();

    foreach (['app', 'app/public', 'cache', 'logs', 'sessions', 'uploads'] as $directory) {
        $path = $runtime . DIRECTORY_SEPARATOR . $directory;

        if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
            throw new RuntimeException(sprintf('Unable to create test runtime directory "%s".', $path));
        }
    }

    return [
        'base_path' => $root,
        '_config_cache' => false,
        'env' => 'testing',
        'paths' => [
            'storage' => $runtime,
            'cache' => $runtime . '/cache',
            'logs' => $runtime . '/logs',
            'sessions' => $runtime . '/sessions',
            'uploads' => $runtime . '/uploads',
        ],
        'auth' => [
            'drivers' => [
                'cache' => 'array',
            ],
        ],
        'router' => [
            'cache' => false,
        ],
    ];
}

function expectedNotifierClass(Application $app): string
{
    return $app->config()->get('auth.drivers.notifications') === 'talkingbytes'
        ? 'Infocyph\\Foundation\\Auth\\Adapter\\TalkingBytes\\TalkingBytesAuthNotifier'
        : 'Infocyph\\Foundation\\Auth\\Support\\CollectingAuthNotifier';
}

it('boots with the expected application shape', function (): void {
    $app = infbyteApp();

    expect($app->environment())->toBeString()->not->toBeEmpty()
        ->and($app->runningInWeb())->toBeTrue();

    $paths = $app->paths()->all();

    expect($paths)->toHaveKey('providers');
    expect(is_file($paths['providers']))->toBeTrue();
    expect(is_dir($paths['storage']))->toBeTrue();
});

it('keeps the console bootstrap outside the web boot graph', function (): void {
    $app = Foundation::console(infbyteTestOptions());

    expect($app)->toBeInstanceOf(Application::class)
        ->and($app->runningInConsole())->toBeTrue()
        ->and($app->container()->has(RouteFileLoader::class))->toBeFalse()
        ->and($app->container()->has(HttpKernel::class))->toBeFalse();

    $app->boot();

    expect($app->container()->has(RouteFileLoader::class))->toBeFalse()
        ->and($app->container()->has(HttpKernel::class))->toBeFalse()
        ->and(fn() => $app->http())
        ->toThrow(LogicException::class, 'HTTP kernel is unavailable');
});

it('defines console commands through an explicit command route map', function (): void {
    $commands = require dirname(__DIR__, 2) . '/routes/console.php';

    expect($commands)->toBeArray()
        ->and($commands)->toBe([]);

    foreach ($commands as $command) {
        expect(is_string($command) && is_a($command, CommandContract::class, true))->toBeTrue();
    }
});

it('registers the core services', function (): void {
    $app = infbyteApp()->boot();

    expect($app->auth())->toBeObject();
    expect($app->authManager())->toBeObject();
    expect($app->authActions())->toBeObject();
    expect($app->http())->toBeObject();
    expect($app->ids())->toBeObject();
    expect($app->has(CacheManager::class))->toBeFalse();
    expect($app->has(DatabaseManager::class))->toBeFalse();
    expect($app->has(FilesystemManager::class))->toBeFalse();
    expect($app->has(NotificationManager::class))->toBeFalse();
    expect($app->has(ValidationManager::class))->toBeFalse();
});

it('serves the health and JSON routes', function (): void {
    $app = infbyteApp()->boot();
    $router = $app->make(RouterManager::class);
    $registered = [];

    foreach ($router->routes() as $route) {
        $registered[$route->getMethod() . ' ' . $route->getPath()] = true;
    }

    expect(array_keys($registered))->toBe(['GET /api/health', 'GET /json']);

    $health = $app->handle(Request::fake(headers: ['Host' => 'localhost'], uri: 'https://localhost/api/health'));
    $json = $app->handle(Request::fake(headers: ['Host' => 'localhost'], uri: 'https://localhost/json'));

    expect($health->getStatusCode())->toBe(200);
    expect(json_decode((string) $health->getBody(), true))->toBe(['status' => 'ok']);
    expect($json->getStatusCode())->toBe(200);
    expect(json_decode((string) $json->getBody(), true))->toHaveKey('memory');
});

it('uses the self-contained auth notifier without optional modules', function (): void {
    $app = infbyteApp()->boot();

    expect($app->make(AuthNotifierInterface::class)::class)->toBe(expectedNotifierClass($app));
});
