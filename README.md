# Infbyte

A secure PHP framework powered by Infocyph Foundation.

Infbyte is the full application framework layer for the Infocyph ecosystem. Foundation stays the installable core runtime package; Infbyte is the host-project skeleton that wires application code, config, routes, storage, and bootstrap around it.

## Create a project

```bash
composer create-project infocyph/infbyte my-app
```

## First setup

```bash
composer install
```

Create your local environment file, then either:

```bash
cp .env.example .env
```

`.env` is intentionally ignored by Git. Keep secrets in the deployment
environment or a secret manager; commit only `.env.example`.

Configure the environment, then either:

- keep the default SQLite setup and create `database/database.sqlite`
- or switch `DB_CONNECTION` to `mysql` or `pgsql` and fill in the matching credentials

To serve the app locally:

```bash
php -S localhost:8000 -t public
```

## Bootstrap

The web entry flow is:

1. `public/index.php`
2. `bootstrap/app.php`
3. `bootstrap/options.php`
4. `Foundation::web()`
5. web providers from `bootstrap/providers.php`
6. `routes/*.php`

The CLI entry flow is separate:

1. `bin/infbyte`
2. the compiled command manifest, when present
3. Console preflight for help, list, completion, and version
4. `bootstrap/console.php` only when a real command needs the application
5. `bootstrap/options.php`
6. `Foundation::console()`
7. command routing from `routes/console.php` when no manifest is cached
8. only the providers required by that command

`bootstrap/options.php` contains the options intentionally shared by both
paths. The console runtime does not register the HTTP kernel or load normal web
routes. Route-cache commands activate routing explicitly because route
compilation is their requested work.

Console commands are routed explicitly from a command name to a command class:

```php
<?php

use App\Console\Commands\ImportUsersCommand;

return [
    'users:import' => ImportUsersCommand::class,
];
```

Application command classes belong in `app/Console/Commands`. The command route
key is authoritative, while the class owns its arguments, options, validation,
authorization requirements, and execution. Foundation's `app:ready`,
`app:ready`, `auth:schema:*`, `command:*`, `config:*`, `module:*`, `optimize*`,
and `route:*` commands are system commands registered by Foundation; they do
not belong in this application route file.

## Project structure

- `app/` application code
- `bootstrap/` bootstrap files and provider registration
- `config/` project configuration loaded by Foundation
- `routes/` route definitions
- `public/` web entry point
- `storage/` runtime cache, logs, sessions, uploads
- `database/` migrations, seeders, factories, local SQLite file
- `resources/` views, emails, assets
- `tests/` repository test suite (excluded from created projects)

## Foundation relationship

Infbyte depends on `infocyph/foundation` through Composer. Foundation provides
the runtime, routing integration, auth wiring,
validation, cache integration, database integration, notifications, and path
management. Infbyte provides the main project shape where that runtime lives.

## Operations

The `infbyte` executable is the application command entrypoint:

```bash
php infbyte app:ready --json=1
php infbyte auth:schema:status --json=1
php infbyte auth:schema:install --json=1
php infbyte module:list
php infbyte module:install db
php infbyte config:cache
php infbyte config:clear
php infbyte command:cache
php infbyte command:clear
php infbyte route:cache
php infbyte optimize
php infbyte optimize:clear
```

`config:cache` warms the project configuration at `bootstrap/cache/config/`.
The default sharded layout keeps namespaces lazy; the optional single layout
loads one compiled snapshot. `config:clear` removes either layout.

`command:cache` compiles system and project command definitions beneath
`bootstrap/cache/console/`. Normal command dispatch then reads only the selected
descriptor and does not load `routes/console.php`. `optimize` warms command,
configuration, and route caches together; cache compilation is intentionally
allowed to do more work so requests and command dispatch do less.

Foundation modules are opt-in. Use `module:list` to inspect availability and
`module:install cache|communication|crypto|db|filesystem|otp|passkeys|validation`
to add only the capabilities the application needs. Installing a module makes
it a direct project dependency; absent modules are not registered or loaded on
the request path.

`app:ready` is deployment-safe: it reports Foundation configuration, auth,
cache, database, notification, and writable-path readiness without outputting
secrets. `auth:schema:install` is idempotent and creates only the Foundation
authentication tables that are missing; install the `db` module before using
the auth schema commands.

Before production deployment, set a unique `AUTH_TOKEN_SECRET` of at least 32
bytes, configure the selected production database/cache/notification drivers,
run `auth:schema:install`, and require `app:ready` to succeed.

## Release Process

Run the local guard before tagging a project release:

```bash
composer ic:release:guard
```

See `SECURITY.md` for private vulnerability reporting and `CONTRIBUTING.md`
for the project workflow.

## Notes

- `bootstrap/providers.php` has explicit `common`, `web`, and `console`
  provider lists. Keep a provider out of `common` unless both paths need it.
- `routes/console.php` maps CLI command names to command classes and is loaded
  only by the CLI entrypoint when the compiled command manifest is absent.
- `config/*.php` is loaded automatically by Foundation.
- `routes/api.php` defines the default `/api/health` and `/json` routes.
- Foundation loads the route files listed in `config/router.php` automatically.
- Storage directories are already present for cache, logs, sessions, and uploads.
