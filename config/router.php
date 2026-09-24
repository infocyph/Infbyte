<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Application Route Files
    |--------------------------------------------------------------------------
    |
    | Foundation/Webrick read these application-owned route sources during
    | development and immutable release generation. Production requests consume
    | the compiled Webrick artifact and never rediscover these files.
    |
    */
    'files' => [
        'api.php',
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Matcher
    |--------------------------------------------------------------------------
    |
    | The matcher controls how Webrick compiles and dispatches routes. The
    | selected form is compiled into the Foundation release generation by
    | `php infbyte optimize`. Allowed values: fused|generated|sharded.
    |
    */
    'matcher' => env_string('ROUTER_MATCHER', 'fused'),
];
