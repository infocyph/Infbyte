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

The application entry flow is:

1. `public/index.php`
2. `bootstrap/app.php`
3. Foundation application boot
4. `bootstrap/providers.php`
5. `routes/*.php`

`bootstrap/app.php` creates the Foundation application with the project base path. Foundation loads `.env` and `.env.local` during config boot.

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
php infbyte config:cache
php infbyte config:clear
php infbyte route:cache
```

`config:cache` warms the project configuration at `bootstrap/cache/config/`.
The default sharded layout keeps namespaces lazy; the optional single layout
loads one compiled snapshot. `config:clear` removes either layout.

`app:ready` is deployment-safe: it reports Foundation configuration, auth,
cache, database, notification, and writable-path readiness without outputting
secrets. `auth:schema:install` is idempotent and creates only the Foundation
authentication tables that are missing.

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

- `bootstrap/providers.php` is the application provider list.
- `config/*.php` is loaded automatically by Foundation.
- `routes/api.php` defines the default `/api/health` and `/json` routes.
- Foundation loads the route files listed in `config/router.php` automatically.
- Storage directories are already present for cache, logs, sessions, and uploads.
