<?php

declare(strict_types=1);

use Infocyph\Foundation\Foundation;
use Infocyph\Foundation\Testing\TestKit;

it('runs the documented Infbyte health endpoint through the native HTTP TestKit', function (): void {
    $app = Foundation::web([
        'base_path' => dirname(__DIR__, 2),
        '_config_cache' => false,
        'app' => [
            'env' => 'testing',
            'capabilities' => [],
        ],
    ])->boot();

    $testing = $app->make(TestKit::class);
    $testing->http()->get('/api/health')
        ->assertStatus(200)
        ->assertJson(['status' => 'ok']);
});
