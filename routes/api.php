<?php

declare(strict_types=1);

use App\Http\Controllers\SystemController;
use Infocyph\Webrick\Router\Definition\Registrar;

/** @var Registrar $router */
$router->get('/api/health', SystemController::health(...));
$router->get('/json', SystemController::json(...), 'json');
