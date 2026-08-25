# Infbyte

Infbyte is the minimal application skeleton for
[Infocyph Foundation](https://github.com/infocyph/Foundation). Foundation owns
the reusable framework/runtime layer; Infbyte provides opinionated application
bootstrap, starter configuration, routes, writable layout, and application code.

Infbyte requires PHP 8.4+ and Foundation 2.0:

```json
"infocyph/foundation": "^2.0"
```

## Quick start

```bash
composer create-project infocyph/infbyte my-app
cd my-app
php infbyte serve
```

The built-in server defaults to `127.0.0.1:8000` and is for local development
only:

```bash
php infbyte serve --host=0.0.0.0 --port=8080
```

Composer invokes Foundation's `app:install` post-create command. The application
root executable remains `infbyte`; there is no separate Artisan/Console runtime.

## Foundation runtime boundary

Infbyte does not implement a framework layer above Foundation.

The Web bootstrap is deliberately small:

```php
use Infocyph\Foundation\Foundation;

return Foundation::web([
    'base_path' => dirname(__DIR__),
]);
```

Foundation's `CommandDispatcher` selects CLI, Worker, and Scheduler runtimes for
the root `infbyte` executable when commands require them.

The four Foundation runtimes are:

- Web
- CLI
- Worker
- Scheduler

Optional package providers remain lazy until configuration/code selects their
capability.

## CLI

List the current command surface with:

```bash
php infbyte list
```

Common families include:

| Purpose | Commands |
| --- | --- |
| Inspect | `about`, `env:show`, `config:show`, `config:validate`, `route:list` |
| Local development | `serve`, `create:*` |
| Modules | `module:list`, `module:show`, `module:install`, `module:remove`, `module:config:publish`, `module:schema:*` |
| Database | `db:*`, `migrate*` |
| Operations | `execution:*`, `maintenance:*`, `runtime:reload`, `log:tail` |
| Background work | `schedule:*`, `worker:*`, `queue:*`, `messaging:list` |
| Storage/session/auth | `storage:*`, `session:prune`, `auth:prune` |
| Environment protection | `env:encrypt`, `env:decrypt` |
| Deployment | `optimize`, `optimize:clear`, `optimize:report`, `app:ready` |

Global controls include `--json`, `-q|--quiet`, `--silent`, `-v|-vv|-vvv`,
`--profile`, `--env`, and `-n|--no-interaction`.

Application commands are registered explicitly in `routes/console.php`,
schedules in `routes/schedule.php`, and non-message maintenance workers in
`routes/workers.php`.

## Application generators

Foundation generators create application starting points without silently
editing registration/configuration:

```bash
php infbyte create:controller Admin/User
php infbyte create:command Reports/Daily
php infbyte create:request StoreUser
php infbyte create:rule ValidVatNumber
php infbyte create:resource User
php infbyte create:job GenerateReport
php infbyte create:handler GenerateReport
php infbyte create:job-middleware AuditJob
php infbyte create:mail Welcome
php infbyte create:notification PasswordChanged
php infbyte create:notification-channel Sms
php infbyte create:repository User
php infbyte create:migration CreateUsers
php infbyte create:seeder Production
php infbyte create:worker Metrics
```

Existing files are preserved unless `--force` is supplied.

## Purpose-first optional modules

A new application keeps only the Foundation core runtime. Add capabilities as
they are needed:

```bash
php infbyte module:list
php infbyte module:install database
php infbyte module:install cache
php infbyte module:install communication
php infbyte module:install messaging
php infbyte module:config:publish operations
```

Canonical modules are:

- `auth`
- `cache`
- `communication`
- `database`
- `filesystem`
- `logging` (built in)
- `messaging`
- `operations` (built in)
- `resources` (built in)
- `security`
- `session` (built in)
- `validation`

Aliases such as `db`, `crypto`, `otp`, `passkeys`, and `queue` remain accepted,
but the application documentation uses purpose names. OTP and WebAuthn are
implementations inside the `auth` module rather than standalone public modules.

Installing a module does not add global middleware, open connections, or start
workers. Optional config is published only when requested/needed and remains
outside the lean checked-in skeleton by default.

## Module schema lifecycle

Capability-owned schemas use one command family:

```bash
php infbyte module:schema:status auth
php infbyte module:schema:install auth
php infbyte module:schema:status cache
php infbyte module:schema:install session
php infbyte module:schema:sync
```

The `database` module owns DB/migration infrastructure; it does not own arbitrary
application tables. Removing a module never drops schemas/application data.

## Application structure

The starter intentionally stays small:

```text
app/
bootstrap/
  app.php
  providers.php
  cache/
config/
  app.php
  auth.php
  router.php
public/
routes/
  api.php
  console.php
  schedule.php
  workers.php
storage/
tests/
composer.json
infbyte
```

Only application-default config is checked in. Cache/database/filesystem/
messaging/operations/security/session/validation/communication config belongs
to optional module publication rather than being bulk-copied into every new
project.

## Database

```bash
php infbyte module:install database
php infbyte migrate
php infbyte migrate --pretend
php infbyte migrate:status
php infbyte db:monitor --section=status
```

Application migrations are registered explicitly under
`database.migrations.classes`; Foundation does not scan migration directories.

## Messaging and workers

Omnibus-backed messaging is optional:

```bash
php infbyte module:install messaging
php infbyte worker:list
php infbyte worker:run reports
php infbyte worker:status reports
php infbyte queue:failed
```

Foundation does not expose a parallel messaging manager. Application services
resolve native Omnibus `MessageBus`, `EventDispatcher`, and related APIs through
DI.

`routes/workers.php` is only for application maintenance workers; queue worker
loops remain Omnibus-owned.

## Runtime operations

Useful production/runtime commands include:

```bash
php infbyte config:validate --production
php infbyte maintenance:status
php infbyte runtime:reload
php infbyte worker:restart
php infbyte schedule:interrupt
php infbyte storage:status
php infbyte log:tail --follow
```

Runtime generation commands request graceful shutdown. Foundation does not
replace Supervisor/systemd/Docker/Kubernetes process supervision.

## Production

Build deployment-owned optimized artifacts before serving production traffic:

```bash
php infbyte optimize
php infbyte optimize:report
php infbyte app:ready
```

Clear generated artifacts with:

```bash
php infbyte optimize:clear
```

Compiled config/route/command/schedule/container artifacts belong to deployment
and are ignored by the skeleton repository.

Before deployment:

- set production environment/debug policy;
- configure only the modules the application actually uses;
- run application migrations;
- provision applicable module schemas;
- require `config:validate --production` / `app:ready` to pass;
- use a production web server and external process manager.

## Testing and release checks

The current skeleton Composer file does not invent generic test/release script
aliases. Run the tools/scripts actually installed by the application/release
candidate. Foundation's full Composer/PHPForge/static/PHPUnit/integration and
performance matrix is performed in its dedicated release-verification phase.

## Documentation

- [Foundation documentation](https://github.com/infocyph/Foundation/tree/2.0/docs)
- [Omnibus](https://github.com/infocyph/Omnibus)
- [Webrick](https://github.com/infocyph/Webrick)

Infbyte keeps framework details in Foundation documentation rather than copying
them into the application skeleton.
