<?php

declare(strict_types=1);

use App\Http\Controllers\HomeController;

/** @var \Infocyph\Foundation\Routing\RouterManager $router */

$router->get('/', [HomeController::class, 'index']);
