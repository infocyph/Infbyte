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
php -S localhost:8000 -t public
```

Composer creates `.env` from `.env.example` and generates a random
authentication token secret without replacing an existing environment file.
Infbyte 1.x supports PHP 8.4 and newer; its CI verifies PHP 8.4 and 8.5.

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
5. project command mappings from `routes/console.php` when no manifest exists
6. `routes/schedule.php` only for `schedule:*` or optimization commands
7. `routes/workers.php` only for `worker:*` commands

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
`command:*`, `config:*`, `create:*`, `db:*`, `migrate*`, `module:*`,
`optimize*`, `route:*`, `schedule:*`, `session:*`, and `worker:*`. Omnibus
commands such as `queue:consume` are registered through Console's lazy
messaging capability. None of these commands belong in the project command map.
Invoke them through `php infbyte`; the skeleton does not duplicate
Foundation-owned commands as Composer scripts. Composer does not inherit
scripts from dependencies, and application-specific Composer scripts remain
free to use unrelated names.
`php infbyte list` places Foundation's framework commands under `System`,
package capability commands under their capability group, and application
commands under the first namespace segment in their route name, such as
`reports` for `reports:daily`. `php infbyte --version` reports the installed
Foundation runtime version because Composer-created root projects do not retain
the skeleton package version. The displayed CLI application name comes from
`APP_NAME` in `.env` (with process environment values taking precedence) and
falls back to `infbyte` when it is missing or empty.

Start the local development server with `php infbyte serve`. It serves the
configured public directory at `http://127.0.0.1:8000`; use `--host` and
`--port` to select another bind address. PHP's built-in server is for local
development only, not production deployment.

Create application artifacts only when the project needs them:

```bash
php infbyte create:controller Admin/User
php infbyte create:command Reports/Daily
php infbyte create:service Billing
php infbyte create:job SendReceipt
php infbyte create:middleware EnsureTenant
php infbyte create:policy Invoice
php infbyte create:provider Billing
php infbyte create:repository User
php infbyte create:repository Reporting/Person --table=reporting.people
php infbyte create:worker Queue
php infbyte create:event UserRegistered
php infbyte create:listener SendWelcomeEmail
php infbyte create:enum OrderStatus
php infbyte create:exception BillingFailed
php infbyte create:interface BillingGateway
php infbyte create:trait FormatsMoney
php infbyte create:class Services/ReportBuilder
php infbyte create:test Http/UserAccess
```

Existing files are preserved unless `--force` is supplied. Commands, providers,
and workers remain explicitly registered in `routes/console.php`,
`bootstrap/providers.php`, and `routes/workers.php`; generation does not make
them part of either runtime automatically. Repositories use DBLayer rather than
an ORM model and therefore require `php infbyte module:install db`; `--table`
overrides the derived plural snake_case table. Generated jobs and listeners are
plain invokable classes so the application can connect them to its chosen queue
or event mechanism without Foundation loading either mechanism automatically.

Define scheduled commands in `routes/schedule.php`. Define dynamic worker names
in `routes/workers.php` as a map to classes implementing
`Infocyph\Foundation\Console\WorkerProvider`. Schedule and worker execution use
CacheLayer locks; install the cache module to select file, Redis, Valkey,
Memcached, or PDO-backed coordination.

Compile application metadata before serving production traffic:

```bash
php infbyte optimize
php infbyte optimize:clear
```

This builds the sharded configuration cache, selected route matcher cache,
compiled command metadata, schedule manifest, and installed-module manifest.
Command descriptors remain lazy and are stored directly in
`bootstrap/cache/console/` beside the command manifest. `optimize:clear`
removes every generated application cache, including every route matcher
layout. Cache compilation may do more work so web requests and command
dispatch do less.

## Modules and published configuration

A new Infbyte project does not install database, cache, communication,
filesystem, cryptography, OTP, passkey, or validation packages. Foundation's
logging, Omnibus messaging, JsonDispatch resources, and browser-session
integrations are already code-available, but their application configuration
is not published. Their optional service graphs remain lazy and are resolved
only by the route, command, or application service that selects them.

Inspect modules and install or publish only the capabilities the application
uses:

```bash
php infbyte module:list
php infbyte module:install db
php infbyte module:install cache
php infbyte module:install messaging
php infbyte module:install resources
php infbyte module:install session
```

Available module names are `cache`, `communication`, `crypto`, `db`,
`filesystem`, `logging`, `messaging`, `otp`, `passkeys`, `resources`,
`session`, and `validation`.

For an optional package, installation adds that package as a direct Composer
dependency and publishes its Foundation integration config. For a built-in
module, the same command publishes only its config. Published files include
complete key documentation and inactive examples. Existing project
configuration is never overwritten, and no install workflow activates a
provider, route, middleware, listener, queue, database connection, or session
globally.

Commands owned by an absent module fail with an installation instruction. For
example, install `db` before using `auth:schema:*`.

### Database and migrations

Install DBLayer before generating repositories or running schema commands:

```bash
php infbyte module:install db
php infbyte create:repository Billing/Invoice --table=billing.invoices
php infbyte migrate:status
php infbyte migrate
```

Connection examples for MySQL/MariaDB, PostgreSQL, and SQLite are published to
`config/database.php`. Migration and seeder manifests remain explicit and are
never discovered during web requests.

### Events, queues, and schedules

Publish the Omnibus integration only when the application uses messaging:

```bash
php infbyte module:install messaging
php infbyte create:event OrderPaid
php infbyte create:listener SendReceipt
php infbyte create:job GenerateInvoice
```

Register message routes, handlers, listeners, and scheduled-message factories
in the published `config/messaging.php`. Register command schedules in
`routes/schedule.php` and supervised workers in `routes/workers.php`. The
ordinary web path does not load those route files or construct transports,
receivers, consumers, or worker services.

### JSON resources

```bash
php infbyte module:install resources
```

This publishes `config/responses.php` with the JsonDispatch media type,
application version, and restricted-transport error-tunnelling policy.
Resource transformation remains opt-in at the controller or response boundary;
the starter `/json` route stays a minimal benchmark route.

### Browser sessions

```bash
php infbyte module:install session
```

This publishes `config/session.php`. Session and CSRF services are resolved only
on routes that select their middleware. Cache- and database-backed stores still
require their corresponding modules; stateless API routes incur no browser-
session startup.

## Application testing

Foundation provides the native Webrick HTTP test client through the application
test kit:

```php
$response = $app->testing()->http()
    ->get('/api/health')
    ->assertStatus(200)
    ->assertJson(['status' => 'ok']);
```

The Infbyte repository's own release tests are excluded from the
Composer-created project, as required by `.gitattributes`. A created
application can add its own suite with `php infbyte create:test`.

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

Repository-only tests are excluded from Composer-created projects. A created
project can add its own test suite without inheriting Infbyte's repository
release tests.

## Framework and package guides

Infbyte documents application setup and operations. Domain behavior remains in
the repository that owns it:

- [Foundation framework guide](https://github.com/infocyph/Foundation/tree/main/docs)
- [Console guide](https://github.com/infocyph/Console/tree/main/docs)
- [Omnibus messaging guide](https://github.com/infocyph/Omnibus/tree/main/docs)
- [Webrick HTTP guide](https://docs.infocyph.com/projects/webrick/en/latest/)
- [JsonDispatch specification](https://docs.infocyph.com/projects/json-dispatch/)

Use each installed package's README and docs for driver-specific configuration,
delivery guarantees, schema behavior, security policy, and production
operations.
