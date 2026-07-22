<?php

declare(strict_types=1);

use Infocyph\Foundation\Application\Application;
use Infocyph\Foundation\Auth\AuthManager;
use Infocyph\Foundation\Auth\AuthServices;
use Infocyph\Foundation\Auth\Contract\Notification\AuthNotifierInterface;
use Infocyph\Foundation\Auth\Http\AuthActions;
use Infocyph\Foundation\Cache\CacheManager;
use Infocyph\Foundation\Database\DatabaseConnectionResolver;
use Infocyph\Foundation\Database\DatabaseManager;
use Infocyph\Foundation\Filesystem\FilesystemManager;
use Infocyph\Foundation\Http\HttpKernel;
use Infocyph\Foundation\Notifications\NotificationManager;
use Infocyph\Foundation\Routing\RouterManager;
use Infocyph\TalkingBytes\Email\Emailer;
use Infocyph\Foundation\Validation\ValidationManager;
use Infocyph\Webrick\Request\Request;

require dirname(__DIR__) . '/vendor/autoload.php';

/**
 * @param callable(): void $callback
 */
function section(string $name, callable $callback): void
{
    output('[section] ' . $name);
    $callback();
}

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function expectSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(sprintf(
            '%s Expected %s, got %s.',
            $message,
            var_export($expected, true),
            var_export($actual, true),
        ));
    }
}

function output(string $message): void
{
    file_put_contents('php://stdout', $message . PHP_EOL);
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

/**
 * @return array<string, mixed>
 */
function decodeJsonResponse(string $body, string $message): array
{
    $decoded = json_decode($body, true);

    if (!is_array($decoded)) {
        throw new RuntimeException($message);
    }

    return $decoded;
}

/** @var Application $app */
$app = require dirname(__DIR__) . '/bootstrap/app.php';

section('Boot Flow', function () use ($app): void {
    expect($app instanceof Application, 'Bootstrap should return an Application instance.');
    expectSame(expectedEnvironment(), $app->environment(), 'Application environment should resolve from config.');

    $paths = $app->paths()->all();

    expect(isset($paths['providers']), 'Path manager should expose the providers file.');
    expect(is_file($paths['providers']), 'The providers file should exist.');
    expect(is_dir($paths['storage']), 'The storage directory should exist.');
});

section('Service Graph', function () use ($app): void {
    $app->boot();

    expect($app->auth() instanceof AuthServices, 'Auth services should resolve.');
    expect($app->authManager() instanceof AuthManager, 'Auth manager should resolve.');
    expect($app->authActions() instanceof AuthActions, 'Auth actions should resolve.');
    expect($app->cache() instanceof CacheManager, 'Cache manager should resolve.');
    expect($app->db() instanceof DatabaseManager, 'Database manager should resolve.');
    expect($app->files() instanceof FilesystemManager, 'Filesystem manager should resolve.');
    expect($app->http() instanceof HttpKernel, 'HTTP kernel should resolve.');
    expect($app->notifications() instanceof NotificationManager, 'Notification manager should resolve.');
    expect($app->make(ValidationManager::class) instanceof ValidationManager, 'Validation manager should resolve.');
});

section('Routing And HTTP', function () use ($app): void {
    $router = $app->make(RouterManager::class);
    $registered = [];

    foreach ($router->routes() as $route) {
        $registered[$route->getMethod() . ' ' . $route->getPath()] = true;
    }

    expect(isset($registered['GET /api/health']), 'The health route should be registered.');
    expect(isset($registered['GET /json']), 'The JSON route should be registered.');

    $health = $app->handle(Request::fake(headers: ['Host' => 'localhost'], uri: 'http://localhost/api/health'));
    expectSame(200, $health->getStatusCode(), 'The health route should return 200.');

    $healthPayload = decodeJsonResponse((string) $health->getBody(), 'The health route should return JSON.');
    expectSame('ok', $healthPayload['status'] ?? null, 'The health route should expose the ok status.');

    $json = $app->handle(Request::fake(headers: ['Host' => 'localhost'], uri: 'http://localhost/json'));
    expectSame(200, $json->getStatusCode(), 'The JSON route should return 200.');

    $jsonPayload = decodeJsonResponse((string) $json->getBody(), 'The JSON route should return JSON.');
    expect(isset($jsonPayload['memory']), 'The JSON route should expose memory usage.');

    $missing = $app->handle(Request::fake(headers: ['Host' => 'localhost'], uri: 'http://localhost/missing'));
    expectSame(404, $missing->getStatusCode(), 'Unknown routes should return 404.');
});

section('Auth And Validation', function () use ($app): void {
    $validator = $app->make(ValidationManager::class);

    expect($validator->hasSchema('auth.login'), 'The auth.login schema should be available.');

    $valid = $validator->validate('auth.login', [
        'identifier' => 'demo@example.com',
        'password' => 'secret-secret',
    ]);
    expect(!$valid->fails(), 'The auth.login schema should accept a valid identifier/password payload.');

    $invalid = $validator->validate('auth.login', []);
    expect($invalid->fails(), 'The auth.login schema should reject an empty payload.');

    $notifier = $app->make(AuthNotifierInterface::class);
    expectSame(
        expectedNotifierClass($app),
        $notifier::class,
        'Auth notifications should resolve the configured notifier.',
    );
});

section('Cache And Database', function () use ($app): void {
    $cache = $app->cache()->store();
    $key = 'infbyte-smoke';

    $cache->set($key, ['ok' => true], 60);
    expectSame(['ok' => true], $cache->get($key), 'The default cache store should round-trip data.');

    $pdo = $app->db()->connection()->getPdo();
    $result = $pdo->query('SELECT 1 AS ok');
    $resolver = $app->make(DatabaseConnectionResolver::class);

    expect($result !== false, 'The default database connection should execute queries.');

    $row = $result->fetch();
    expectSame(1, (int) ($row['ok'] ?? 0), 'The default database connection should return query results.');
    expectSame(
        dirname(__DIR__) . '/database/database.sqlite',
        $resolver->configuration('sqlite')['database'] ?? null,
        'The SQLite database path should resolve to the project database file.',
    );

    $readiness = $app->db()->authSchema()->readiness();
    expect(is_array($readiness['missing_tables'] ?? null), 'Auth schema readiness should expose missing tables.');

    $report = $app->readinessReport();
    expect(is_bool($report['production_ready']), 'The readiness report should expose a boolean production flag.');
});

section('Filesystem And Notifications', function () use ($app): void {
    $files = $app->files();
    $unique = 'infbyte-smoke-' . uniqid('', true);
    $name = $unique . '.txt';
    $uploadDirectory = 'tests/' . $unique;

    $files->write($name, 'Pathwise smoke');
    expectSame('Pathwise smoke', $files->read($name), 'The default filesystem disk should round-trip file contents.');
    expect($files->exists($name), 'The default filesystem disk should confirm file existence.');

    $uploadTemp = tempnam(sys_get_temp_dir(), 'infbyte-upload-');
    expect($uploadTemp !== false, 'A temp file should be created for upload smoke.');

    file_put_contents((string) $uploadTemp, 'upload smoke');

    $uploadedPath = $files->upload($uploadDirectory)->processUpload([
        'error' => UPLOAD_ERR_OK,
        'size' => filesize((string) $uploadTemp) ?: 0,
        'tmp_name' => (string) $uploadTemp,
        'name' => $name,
    ]);

    $download = $files->download($uploadDirectory);
    $manifest = $download->prepareDownload($uploadedPath);
    expectSame(200, $manifest['status'], 'The download processor should prepare a normal response.');

    $stream = fopen('php://temp', 'w+b');
    expect(is_resource($stream), 'A temporary output stream should be available for downloads.');

    $result = $download->streamDownload($uploadedPath, $stream);
    rewind($stream);
    $downloaded = stream_get_contents($stream);
    fclose($stream);

    expectSame(strlen('upload smoke'), $result['bytesSent'], 'The download processor should stream the expected byte count.');
    expectSame('upload smoke', $downloaded, 'The download processor should stream the uploaded file contents.');

    expect($app->notifications()->emailer() instanceof Emailer, 'TalkingBytes Emailer should resolve from notifications.');

    $files->delete($name);
    $files->deleteDirectory($uploadDirectory, 'uploads');
});

output('[pass] Infbyte framework smoke checks completed.');
