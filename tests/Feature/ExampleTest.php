<?php

declare(strict_types=1);

use Infocyph\Foundation\Application\Application;

it('serves the application health endpoint', function (): void {
    /** @var Application $app */
    $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
    $app->boot();

    $response = $app->testing()
        ->http()
        ->get('/api/health')
        ->assertStatus(200)
        ->assertJson(['status' => 'ok']);

    expect($response->json())->toBe(['status' => 'ok']);
});
