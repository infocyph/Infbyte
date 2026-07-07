<?php

declare(strict_types=1);

namespace App\Providers;

use Infocyph\Foundation\Application\Application;
use Infocyph\Foundation\Application\ServiceProvider;

final class RouteServiceProvider extends ServiceProvider
{
    public function boot(Application $app): void {}

    public function register(Application $app): void {}
}
