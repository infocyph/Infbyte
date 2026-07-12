<?php

declare(strict_types=1);

use Infocyph\Foundation\Application\Application;
use Infocyph\Foundation\Database\DatabaseConnectionResolver;
use Infocyph\TalkingBytes\Email\Emailer;
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

    expect($app->environment())->toBe(expectedEnvironment());

    $paths = $app->paths()->all();

    expect($paths)->toHaveKey('providers');
    expect(is_file($paths['providers']))->toBeTrue();
    expect(is_dir($paths['storage']))->toBeTrue();
});

it('registers the core services', function (): void {
    $app = infbyteApp()->boot();

    expect($app->auth())->toBeObject();
    expect($app->authManager())->toBeObject();
    expect($app->authActions())->toBeObject();
    expect($app->cache())->toBeObject();
    expect($app->db())->toBeObject();
    expect($app->files())->toBeObject();
    expect($app->http())->toBeObject();
    expect($app->notifications())->toBeObject();
    expect($app->make(ValidationManager::class))->toBeInstanceOf(ValidationManager::class);
});

it('serves the web and api entry routes', function (): void {
    $app = infbyteApp()->boot();
    $router = $app->make(RouterManager::class);
    $registered = [];

    foreach ($router->routes() as $route) {
        $registered[$route->getMethod() . ' ' . $route->getPath()] = true;
    }

    expect($registered)->toHaveKeys(['GET /', 'GET /api/health']);

    $home = $app->handle(Request::fake(headers: ['Host' => 'localhost'], uri: 'http://localhost/'));
    $health = $app->handle(Request::fake(headers: ['Host' => 'localhost'], uri: 'http://localhost/api/health'));
    $missing = $app->handle(Request::fake(headers: ['Host' => 'localhost'], uri: 'http://localhost/missing'));

    expect($home->getStatusCode())->toBe(200);
    expect(json_decode((string) $home->getBody(), true))->toBe([
        'framework' => 'Infbyte',
        'status' => 'ready',
    ]);

    expect($health->getStatusCode())->toBe(200);
    expect(json_decode((string) $health->getBody(), true))->toBe([
        'status' => 'ok',
    ]);

    expect($missing->getStatusCode())->toBe(404);
});

it('exposes the auth validation schema and default notifier', function (): void {
    $app = infbyteApp()->boot();
    $validator = $app->make(ValidationManager::class);

    expect($validator->hasSchema('auth.login'))->toBeTrue();
    expect($validator->validate('auth.login', [
        'identifier' => 'demo@example.com',
        'password' => 'secret-secret',
    ])->fails())->toBeFalse();
    expect($validator->validate('auth.login', [])->fails())->toBeTrue();
    expect($app->notifications()->authNotifier()::class)->toBe(expectedNotifierClass($app));
});

it('round-trips cache data and opens the default database connection', function (): void {
    $app = infbyteApp()->boot();
    $cache = $app->cache()->store();

    $cache->set('infbyte-feature-test', ['ok' => true], 60);

    expect($cache->get('infbyte-feature-test'))->toBe(['ok' => true]);

    $pdo = $app->db()->connection()->getPdo();
    $result = $pdo->query('SELECT 1 AS ok');
    $resolver = $app->make(DatabaseConnectionResolver::class);

    expect($result)->not->toBeFalse();
    expect($result->fetch())->toMatchArray(['ok' => 1]);
    expect($resolver->configuration('sqlite')['database'])
        ->toBe(dirname(__DIR__, 2) . '/database/database.sqlite');

    $readiness = $app->db()->authSchema()->readiness();
    $report = $app->readinessReport();

    expect($readiness['missing_tables'])->toBeArray();
    expect($report['production_ready'])->toBeBool();
});

it('routes filesystem operations through pathwise-backed disks', function (): void {
    $app = infbyteApp()->boot();
    $files = $app->files();
    $unique = 'framework-runtime-' . uniqid('', true);
    $content = 'Infbyte via Pathwise';
    $uploadDirectory = 'tests/' . $unique;

    $files->write($unique . '.txt', $content);

    expect($files->read($unique . '.txt'))->toBe($content);
    expect($files->exists($unique . '.txt'))->toBeTrue();

    $uploadTemp = tempnam(sys_get_temp_dir(), 'infbyte-upload-');

    if ($uploadTemp === false) {
        throw new RuntimeException('Unable to create upload temp file.');
    }

    file_put_contents($uploadTemp, 'uploaded payload');

    $uploadedPath = $files->upload($uploadDirectory)->processUpload([
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($uploadTemp) ?: 0,
        'tmp_name' => $uploadTemp,
        'name' => $unique . '.txt',
    ]);

    expect($uploadedPath)->toStartWith($app->uploadsPath());

    $download = $files->download($uploadDirectory);
    $manifest = $download->prepareDownload($uploadedPath);
    $stream = fopen('php://temp', 'w+b');

    if (!is_resource($stream)) {
        throw new RuntimeException('Unable to create download stream.');
    }

    $streamed = $download->streamDownload($uploadedPath, $stream);
    rewind($stream);
    $downloadedContents = stream_get_contents($stream);
    fclose($stream);

    expect($manifest['status'])->toBe(200);
    expect($manifest['headers'])->toHaveKey('Content-Disposition');
    expect($streamed['bytesSent'])->toBe(strlen('uploaded payload'));
    expect($downloadedContents)->toBe('uploaded payload');
    expect($app->notifications()->emailer())->toBeInstanceOf(Emailer::class);

    $files->delete($unique . '.txt');
    $files->deleteDirectory($uploadDirectory, 'uploads');
});
