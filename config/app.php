<?php

declare(strict_types=1);

return [
    'name' => $_ENV['APP_NAME'] ?? 'Infbyte',
    'env' => $_ENV['APP_ENV'] ?? 'local',
    'debug' => ($_ENV['APP_DEBUG'] ?? 'true') === 'true',
];
