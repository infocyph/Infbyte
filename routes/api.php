<?php

declare(strict_types=1);

use Infocyph\Webrick\Router\Facade\Router as Route;

Route::group(
    middleware: ['throttle'],
    callback: static function (): void {
        Route::get('/api/health', static fn(): array => ['status' => 'ok']);
    },
);
