<?php

declare(strict_types=1);

use Infocyph\Foundation\Foundation;

$basePath = dirname(__DIR__);

return Foundation::create([
    'base_path' => $basePath,
]);
