<?php

declare(strict_types=1);

use Infocyph\Webrick\Request\Request;
use Infocyph\Webrick\Response\Emitter\AutoEmitter;

require dirname(__DIR__) . '/vendor/autoload.php';

/** @var \Infocyph\Foundation\Application\Application $app */
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$request = Request::fromGlobals();
$response = $app->handle($request);

new AutoEmitter()->emit($response, $request);
