<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Infocyph\Foundation\Foundation;

$basePath = dirname(__DIR__);

if (class_exists(Dotenv::class)) {
    Dotenv::createImmutable($basePath)->safeLoad();
}

return Foundation::create([
    'base_path' => $basePath,
]);
