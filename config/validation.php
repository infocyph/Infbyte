<?php

declare(strict_types=1);

return [
    'fail_fast' => true,
    'defaults' => [
        'allow_unknown' => true,
        'strip_unknown' => false,
        'strict' => false,
        'nested' => false,
        'nested_mode' => 'all',
        'throw_on_failure' => false,
        'locale' => null,
        'locale_packs' => [],
        'messages' => [],
        'aliases' => [],
        'sanitizers' => [],
        'casts' => [],
        'dto' => null,
    ],
    'overrides' => [],
];
