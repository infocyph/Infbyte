<?php

declare(strict_types=1);

namespace Tests\Feature;

use FilesystemIterator;
use Infocyph\Foundation\Application\Application;
use Infocyph\Foundation\Application\RuntimeMode;
use Infocyph\Foundation\Config\ConfigRepository;
use Infocyph\Foundation\Filesystem\PathManager;
use Infocyph\Foundation\Foundation;
use Infocyph\Foundation\Http\HttpKernel;
use Infocyph\Foundation\Runtime\ExecutionScope;
use Infocyph\Foundation\Session\BrowserSession;
use Infocyph\Webrick\Request\Request;
use Infocyph\Webrick\Response\Response;
use LogicException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Tests\Support\FixtureData;

/**
 * @return array<string, mixed>
 * @throws \RuntimeException
 */
function infbyteTestOptions(): array
{
    $root = dirname(__DIR__, 2);
    $pid = getmypid();
    \PHPUnit\Framework\Assert::assertIsInt($pid);
    $runtime = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
        . '/infbyte-runtime-'
        . $pid;

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
            'capabilities' => [],
        ],
        'paths' => [
            'storage' => $runtime,
            'cache' => $runtime . '/cache',
            'logs' => $runtime . '/logs',
            'sessions' => $runtime . '/sessions',
            'uploads' => $runtime . '/uploads',
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
        $app = $factory($options);

        expect($app::class)->toBe(Application::class)
            ->and($app->runtimeMode())->toBe($mode)
            ->and($app->{$predicate}())->toBeTrue()
            ->and($app->booted())->toBeFalse()
            ->and($app->environment())->toBe('testing')
            ->and($app->basePath())->toBe(dirname(__DIR__, 2));
    }

    expect(get_class_methods(Foundation::class))->not->toContain('console');
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
        ->and($app->config()::class)->toBe(ConfigRepository::class)
        ->and($app->paths()::class)->toBe(PathManager::class)
        ->and($app->execution()::class)->toBe(ExecutionScope::class)
        ->and($app->http()::class)->toBe(HttpKernel::class)
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
    $composer = FixtureData::json((string) file_get_contents(dirname(__DIR__, 2) . '/composer.json'));
    $scripts = FixtureData::map($composer['scripts'] ?? null);

    foreach ([
        'app:ready',
        'config:cache',
        'db:monitor',
        'module:list',
        'optimize',
        'schedule:run',
        'worker:run',
    ] as $systemCommand) {
        expect(array_key_exists($systemCommand, $scripts))->toBeFalse();
    }
});

it('serves the skeleton routes through canonical embedded Foundation handling', function (): void {
    $app = Foundation::web(infbyteTestOptions());

    $health = $app->handle(Request::fake(method: 'GET', uri: 'http://localhost/api/health'));
    $json = $app->handle(Request::fake(method: 'GET', uri: 'http://localhost/json'));
    $healthPayload = FixtureData::json((string) $health->getBody());
    $jsonPayload = FixtureData::json((string) $json->getBody());

    expect($app->booted())->toBeTrue()
        ->and($health->getStatusCode())->toBe(200)
        ->and($healthPayload)->toBe(['status' => 'ok'])
        ->and($json->getStatusCode())->toBe(200)
        ->and($jsonPayload)->toHaveKey('memory')
        ->and($jsonPayload['memory'])->toBeInt();
});


it('runs auth-selected browser session issuance rotation and invalidation through the HTTP kernel', function (): void {
    $project = sys_get_temp_dir() . '/infbyte-session-' . bin2hex(random_bytes(5));
    $route = $project . '/routes/web.php';

    if (!mkdir(dirname($route), 0775, true) && !is_dir(dirname($route))) {
        throw new RuntimeException('Unable to create the Infbyte session fixture.');
    }

    file_put_contents($route, <<<'PHP'
<?php

use Infocyph\Foundation\Session\BrowserSession;
use Infocyph\Webrick\Request\Request;
use Infocyph\Webrick\Response\Response;
use Infocyph\Webrick\Router\Facade\Router;

Router::get('/session/issue', static function (Request $request): Response {
    $session = BrowserSession::fromRequest($request);
    $session->put('principal', 'infbyte-user');

    return Response::json(['principal' => $session->get('principal')]);
}, ['middleware' => ['session']]);

Router::get('/session/read', static function (Request $request): Response {
    return Response::json([
        'principal' => BrowserSession::fromRequest($request)->get('principal'),
    ]);
}, ['middleware' => ['session']]);

Router::get('/session/rotate', static function (Request $request): Response {
    $session = BrowserSession::fromRequest($request);
    $principal = $session->get('principal');
    $session->regenerate();

    return Response::json(['principal' => $principal]);
}, ['middleware' => ['session']]);

Router::get('/session/logout', static function (Request $request): Response {
    BrowserSession::fromRequest($request)->invalidate();

    return Response::json(['logged_out' => true]);
}, ['middleware' => ['session']]);
PHP);

    try {
        $app = Foundation::web([
            'base_path' => $project,
            '_config_cache' => false,
            'app' => [
                'env' => 'testing',
                'capabilities' => ['auth', 'session'],
            ],
            'router' => [
                'files' => ['web.php'],
                'matcher' => 'fused',
            ],
            'session' => [
                'driver' => 'array',
                'cookie' => [
                    'name' => 'infbyte_session',
                    'secure' => true,
                    'http_only' => true,
                    'same_site' => 'Lax',
                ],
            ],
        ]);

        $issued = $app->handle(Request::fake(
            headers: ['Host' => 'example.test'],
            uri: 'https://example.test/session/issue',
        ));
        $firstId = infbyteSessionCookieId($issued);

        $rotated = $app->handle(
            Request::fake(
                headers: ['Host' => 'example.test'],
                uri: 'https://example.test/session/rotate',
            )->withCookieParams(['infbyte_session' => $firstId]),
        );
        $secondId = infbyteSessionCookieId($rotated);

        $staleAfterRotate = $app->handle(
            Request::fake(
                headers: ['Host' => 'example.test'],
                uri: 'https://example.test/session/read',
            )->withCookieParams(['infbyte_session' => $firstId]),
        );
        $activeAfterRotate = $app->handle(
            Request::fake(
                headers: ['Host' => 'example.test'],
                uri: 'https://example.test/session/read',
            )->withCookieParams(['infbyte_session' => $secondId]),
        );

        $logout = $app->handle(
            Request::fake(
                headers: ['Host' => 'example.test'],
                uri: 'https://example.test/session/logout',
            )->withCookieParams(['infbyte_session' => $secondId]),
        );
        $thirdId = infbyteSessionCookieId($logout);

        $staleAfterLogout = $app->handle(
            Request::fake(
                headers: ['Host' => 'example.test'],
                uri: 'https://example.test/session/read',
            )->withCookieParams(['infbyte_session' => $secondId]),
        );

        expect($firstId)->not->toBe($secondId)
            ->and($secondId)->not->toBe($thirdId)
            ->and(infbyteSessionJson($issued))->toBe(['principal' => 'infbyte-user'])
            ->and(infbyteSessionJson($rotated))->toBe(['principal' => 'infbyte-user'])
            ->and(infbyteSessionJson($staleAfterRotate))->toBe(['principal' => null])
            ->and(infbyteSessionJson($activeAfterRotate))->toBe(['principal' => 'infbyte-user'])
            ->and(infbyteSessionJson($logout))->toBe(['logged_out' => true])
            ->and(infbyteSessionJson($staleAfterLogout))->toBe(['principal' => null])
            ->and($issued->getHeaderLine('Set-Cookie'))->toContain('Secure', 'HttpOnly', 'SameSite=Lax');
    } finally {
        infbyteRemoveTestDirectory($project);
    }
});

/**
 * @throws \InvalidArgumentException
 * @throws \RuntimeException
 */
function infbyteSessionCookieId(Response $response): string
{
    preg_match('/(?:^|;\\s*)infbyte_session=([a-f0-9]{64})/', $response->getHeaderLine('Set-Cookie'), $matches);

    return $matches[1] ?? throw new RuntimeException('The response did not contain an Infbyte session cookie.');
}

/**
 * @return array<string, mixed>
 * @throws \JsonException
 */
function infbyteSessionJson(Response $response): array
{
    return FixtureData::json((string) $response->getBody());
}

function infbyteRemoveTestDirectory(string $directory): void
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
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }

    rmdir($directory);
}
