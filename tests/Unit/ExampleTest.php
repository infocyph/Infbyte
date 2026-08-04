<?php

declare(strict_types=1);

use App\Http\Controllers\SystemController;

it('reports a healthy application status', function (): void {
    expect(SystemController::health())->toBe(['status' => 'ok']);
});
