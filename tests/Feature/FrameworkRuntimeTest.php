<?php

declare(strict_types=1);

use Infocyph\Foundation\Application\Application;
use Infocyph\Foundation\Auth\Contract\Notification\AuthNotifierInterface;
use Infocyph\Foundation\Cache\CacheManager;
use Infocyph\Console\Command\CommandContract;
use Infocyph\Foundation\Database\DatabaseManager;
use Infocyph\Foundation\Filesystem\FilesystemManager;
use Infocyph\Foundation\Http\HttpKernel;
use Infocyph\Foundation\Notifications\NotificationManager;
use Infocyph\Foundation\Routing\RouteFileLoader;
use Infocyph\Foundation\Routing\RouterManager;
use Infocyph\Foundation\Validation\ValidationManager;
use Infocyph\Webrick\Request\Request;

function infbyteApp(): Application
{
    $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';

    if (!$app instanceof Application) {
        throw new RuntimeException('Bootstrap should return an Application instance.');
    }

    return $app;
}

function expectedEnvironment(): string
{
    if (getenv('INFBYTE_TESTING') === '1') {
        return 'testing';
    }

    $value = $_ENV['APP_ENV'] ?? 'local';

    return is_string($value) && $value !== ''
        ? strtolower($value)
        : 'local';
}

function expectedNotifierClass(Application $app): string
{
    return $app->config()->get('auth.drivers.notifications') === 'talkingbytes'
        ? 'Infocyph\\Foundation\\Auth\\Adapter\\TalkingBytes\\TalkingBytesAuthNotifier'
        : 'Infocyph\\Foundation\\Auth\\Support\\CollectingAuthNotifier';
}

it('boots with the expected application shape', function (): void {
    $app = infbyteApp();

    expect($app->environment())->toBe(expectedEnvironment())
        ->and($app->runningInWeb())->toBeTrue();

    $paths = $app->paths()->all();

    expect($paths)->toHaveKey('providers');
    expect(is_file($paths['providers']))->toBeTrue();
    expect(is_dir($paths['storage']))->toBeTrue();
});

it('keeps the console bootstrap outside the web boot graph', function (): void {
    $app = require dirname(__DIR__, 2) . '/bootstrap/console.php';

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
