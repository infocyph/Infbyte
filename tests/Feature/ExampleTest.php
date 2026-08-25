<?php

declare(strict_types=1);

use Infocyph\Foundation\Application\Application;
use Infocyph\Webrick\Request\Request;

it('serves the application health endpoint', function (): void {
    /** @var Application $app */
    $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';

    $response = $app->handle(Request::fake(method: 'GET', uri: 'http://localhost/api/health'));
    $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($response->getStatusCode())->toBe(200)
        ->and($payload)->toBe(['status' => 'ok']);
});
