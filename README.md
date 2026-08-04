# Infbyte

Infbyte is the minimal application skeleton for
[Infocyph Foundation](https://github.com/infocyph/Foundation). Foundation owns
the reusable framework runtime; Infbyte provides the application bootstrap,
configuration, routes, storage layout, and starter code.

Infbyte 1.x requires PHP 8.4 or newer.

## Quick start

```bash
composer create-project infocyph/infbyte my-app
cd my-app
php infbyte serve
```

The development server listens on `http://127.0.0.1:8000` by default. Select a
different address or port when needed:

```bash
php infbyte serve --host=0.0.0.0 --port=8080
```

The built-in server is intended for local development only. Composer invokes
Foundation's `app:install` command to create `.env` from `.env.example` and
generate a unique authentication secret without replacing an existing one.

The starter application exposes:

- `GET /api/health`
- `GET /json`

## Console

Run `php infbyte list` to see every available command. Frequently used commands
include:

| Purpose | Commands |
| --- | --- |
| Inspect the application | `about`, `env:show`, `config:show`, `route:list` |
| Local development | `serve`, `create:*` |
| Optional capabilities | `module:list`, `module:install`, `module:remove` |
| Database work | `db:show`, `db:table`, `db:seed`, `migrate*` |
| Runtime maintenance | `app:install`, `cache:clear`, `storage:link`, `secret:generate` |
| Background work | `schedule:*`, `worker:*`, `queue:consume` |
| Deployment | `optimize`, `optimize:clear`, `app:ready` |

Some commands become usable only after their corresponding optional module is
installed. Missing capabilities report the exact installation command.

Create application artifacts without adding unused runtime services:

```bash
php infbyte create:controller Admin/User
php infbyte create:command Reports/Daily
php infbyte create:repository User
php infbyte create:worker Queue
php infbyte create:test Http/UserAccess
```

Existing files are preserved unless `--force` is supplied. Register application
commands explicitly in `routes/console.php`, schedules in `routes/schedule.php`,
and supervised workers in `routes/workers.php`.

## Optional modules

A new project installs only the core runtime. Add or publish capabilities as the
application needs them:

```bash
php infbyte module:list
php infbyte module:install db
php infbyte module:install cache
php infbyte module:install messaging
php infbyte module:install session
```

Available modules include database, cache, communication, cryptography,
filesystem, logging, messaging, OTP, passkeys, JSON resources, sessions, and
validation. Installation publishes documented configuration without activating
global middleware, providers, connections, workers, or sessions. Optional
services remain lazy until selected by application code, a route, or a command.

## Application structure

- `app/` contains application controllers, commands, and generated code.
- `bootstrap/` contains the isolated web and console composition roots.
- `config/` contains application-owned configuration.
- `routes/` contains HTTP, command, schedule, and worker mappings.
- `public/` contains the web entry point.
- `storage/` contains writable runtime data.
- `tests/Feature/` and `tests/Unit/` contain starter Pest examples.

Web requests boot only the HTTP path. CLI requests boot only the command
capabilities they require; the ordinary web path does not load console command,
schedule, or worker definitions.

## Testing

The generated project includes one feature test and one unit test:

```bash
composer ic:tests
```

Foundation provides a native Webrick HTTP test client, as demonstrated by
`tests/Feature/ExampleTest.php`. Infbyte's repository-only release tests are
excluded from Composer-created projects.

## Production

Compile application metadata before serving production traffic:

```bash
php infbyte optimize
php infbyte app:ready
```

`optimize` compiles configuration, route, command, schedule, and module metadata
so requests do less work. `optimize:clear` removes generated caches.

The included deployment helper validates writable runtime paths and builds the
same caches:

```bash
./deploy.sh
```

Before deployment, set `APP_ENV=production`, disable debug output, configure
installed modules, apply required migrations, and require `app:ready` to pass.

## Documentation

- [Foundation](https://github.com/infocyph/Foundation/tree/main/docs)
- [Console](https://github.com/infocyph/Console/tree/main/docs)
- [Omnibus](https://github.com/infocyph/Omnibus/tree/main/docs)
- [Webrick](https://docs.infocyph.com/projects/webrick/en/latest/)
- [JsonDispatch](https://docs.infocyph.com/projects/json-dispatch/)

Detailed framework and package documentation will expand separately; this
README intentionally remains a concise application quick start.
