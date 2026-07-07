<?php

declare(strict_types=1);

return [
    'auth' => [
        'fail_silently' => ($_ENV['NOTIFICATIONS_AUTH_FAIL_SILENTLY'] ?? 'false') === 'true',
        'from' => $_ENV['NOTIFICATIONS_AUTH_FROM'] ?? null,
        'transport' => $_ENV['NOTIFICATIONS_AUTH_TRANSPORT'] ?? 'null',
    ],
];
