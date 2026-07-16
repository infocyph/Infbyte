<?php

declare(strict_types=1);

namespace App\Providers;

use Infocyph\Foundation\Application\Application;
use Infocyph\Foundation\Application\ServiceProvider;
use Infocyph\Foundation\Routing\RouterManager;

final class RouteServiceProvider extends ServiceProvider
{
    public function boot(Application $app): void
    {
        $app->make(RouterManager::class)->router();
    }

    public function register(Application $app): void {}
}
