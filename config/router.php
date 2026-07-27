<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Application Route Files
    |--------------------------------------------------------------------------
    |
    | These files are loaded from the routes directory when route metadata has
    | not been cached. Add a filename for each application-owned route surface.
    | Example values: `api.php`, `web.php`, and `admin.php`.
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
    | The matcher controls how Webrick compiles and dispatches routes. Build the
    | selected form during deployment with `php infbyte route:cache`.
    | Allowed values: `fused|generated|sharded`.
    |
    */
    'matcher' => env('ROUTER_MATCHER', 'fused'),
];
