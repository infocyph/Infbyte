<?php

declare(strict_types=1);

/** @var \Infocyph\Foundation\Routing\RouterManager $router */

$router->get('/api/health', static fn(): array => ['status' => 'ok']);
