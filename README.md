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

Update `.env` for your environment, then either:

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

`bootstrap/app.php` loads `.env` and creates the Foundation application with the project base path.

## Project structure

- `app/` application code
- `bootstrap/` bootstrap files and provider registration
- `config/` project configuration loaded by Foundation
- `routes/` route definitions
- `public/` web entry point
- `storage/` runtime cache, logs, sessions, uploads
- `database/` migrations, seeders, factories, local SQLite file
- `resources/` views, emails, assets
- `tests/` test suite

## Foundation relationship

Infbyte depends on `infocyph/foundation` through Composer. Foundation provides the runtime, routing integration, auth wiring, validation, cache integration, database integration, notifications, and path management. Infbyte provides the main project shape where that runtime lives.

## Notes

- `bootstrap/providers.php` is the application provider list.
- `config/*.php` is loaded automatically by Foundation.
- `routes/web.php`, `routes/api.php`, and `routes/auth.php` are loaded automatically by Foundation.
- Storage directories are already present for cache, logs, sessions, and uploads.
