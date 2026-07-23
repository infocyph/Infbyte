<?php

declare(strict_types=1);

use Infocyph\Foundation\Foundation;
use Infocyph\Foundation\Support\ValueNormalizer;

$options = require __DIR__ . '/options.php';
if (!is_array($options)) {
    throw new RuntimeException('Bootstrap options must return an associative array.');
}

$config = ValueNormalizer::associativeArray($options);
if (count($config) !== count($options)) {
    throw new RuntimeException('Bootstrap options must use string keys.');
}

return Foundation::console($config);
