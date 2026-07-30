<?php

declare(strict_types=1);

use Infocyph\Foundation\Foundation;

/** @var array<string, mixed> $options */
$options = require __DIR__ . '/options.php';

return Foundation::web($options);
