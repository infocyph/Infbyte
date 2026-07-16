<?php

declare(strict_types=1);

use App\Http\Controllers\HomeController;
use Infocyph\Webrick\Router\Facade\Router as Route;

Route::get('/', [HomeController::class, 'index']);
