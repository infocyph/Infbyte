# Infbyte

Infbyte is the application skeleton for projects powered by
[Infocyph Foundation](https://github.com/infocyph/Foundation). Foundation owns
the reusable framework runtime; this repository contains only the
application-facing bootstrap, configuration, routes, storage layout, and
starter code.

## Create a project

```bash
composer create-project infocyph/infbyte my-app
cd my-app
cp .env.example .env
php -S localhost:8000 -t public
```

The starter application exposes:

- `GET /api/health`
- `GET /json`

## Runtime paths

Web requests follow this isolated path:

1. `public/index.php`
2. `bootstrap/app.php`
3. `bootstrap/options.php`
4. `Foundation::web()`
5. web providers from `bootstrap/providers.php`
6. application route files from `config/router.php`

CLI requests follow:

1. `infbyte`
2. the compiled command manifest, when available
3. `bootstrap/console.php` only when the selected command needs the application
4. console providers from `bootstrap/providers.php`
5. project command mappings from `routes/console.php`

The CLI path does not register the HTTP kernel or load application web routes.
The web path does not load Console classes, command routes, or the console
bootstrap.

## Application structure

- `app/Http/Controllers/` contains HTTP controllers.
- `app/Console/Commands/` contains project command classes.
- `bootstrap/` contains the isolated web/console composition roots and caches.
- `config/` contains application-owned configuration.
- `routes/` contains HTTP routes and the console command map.
- `public/` contains the web server entry point.
- `storage/` contains runtime data and must be writable by the deployment user.

Add application directories only when the project needs them. Optional package
installation may add its own configuration and project structure.

## Console commands

Map project command names explicitly in `routes/console.php`:

```php
<?php

use App\Console\Commands\ImportUsersCommand;

return [
    'users:import' => ImportUsersCommand::class,
];
```

Foundation supplies system commands including `app:ready`, `auth:schema:*`,
`command:*`, `config:*`, `module:*`, `optimize*`, and `route:*`. They do not
belong in the project command map.

Compile application metadata before serving production traffic:

```bash
php infbyte optimize
php infbyte optimize:clear
```

This builds the sharded configuration cache, selected route matcher cache, and
compiled command manifest. Command descriptors remain lazy and are stored
directly in `bootstrap/cache/console/` beside the manifest. `optimize:clear`
removes all three cache types. Cache compilation may do more work so web
requests and command dispatch do less.

## Optional modules

A new Infbyte project contains no database, cache, communication, filesystem,
cryptography, OTP, passkey, or validation package. Inspect and install only the
capabilities the application uses:

```bash
php infbyte module:list
php infbyte module:install db
php infbyte module:install cache
```

Available module names are `cache`, `communication`, `crypto`, `db`,
`filesystem`, `otp`, `passkeys`, and `validation`. Installation adds the package
as a direct Composer dependency and publishes its configuration from
Foundation. Existing project configuration is never overwritten.

Commands owned by an absent module fail with an installation instruction. For
example, install `db` before using `auth:schema:*`.

## Deployment

Run deployment as the user that owns the application:

```bash
./deploy.sh
```

The script verifies writable runtime directories and builds application caches.
Use `./deploy.sh --no-cache` to perform only the permission check.

Before production traffic:

- set `APP_ENV=production` and disable debug output;
- replace `AUTH_TOKEN_SECRET` with a unique high-entropy secret;
- configure every installed module;
- run any required schema installation or migrations;
- require `php infbyte app:ready` to succeed.

Repository-only tests and development workflow files are excluded from
Composer-created projects. A created project can add its own test suite without
inheriting Infbyte’s package-release exclusions.
