# Infbyte

Infbyte is the minimal application skeleton for
[Infocyph Foundation](https://github.com/infocyph/Foundation). Foundation owns
the reusable framework/runtime layer; Infbyte provides opinionated application
configuration, routes, writable layout, and application code.

Infbyte targets the stable Foundation 3 runtime architecture:

```json
"infocyph/foundation": "^3.0"
```

Infbyte requires PHP 8.4+.

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

Foundation 3 compiles and activates one trusted release generation when `serve`
starts, then passes its release root and manifest identity to the child server.
Restart `serve` after changing route/configuration/graph topology that must be
recompiled.

Composer invokes Foundation's `app:install` post-create command. The application
root executable remains `infbyte`; there is no separate Artisan/Console runtime.

## Foundation runtime boundary

Infbyte does not implement a framework runtime above Foundation.

The production web entrypoint selects the externally trusted active Foundation
generation and hands native request adaptation and response emission directly to
Webrick through Foundation:

```php
use Infocyph\Foundation\Release\FoundationReleaseBootstrap;

$config = ['base_path' => dirname(__DIR__)];
$release = FoundationReleaseBootstrap::fromEnvironment($config)
    ?? throw new RuntimeException('No trusted Foundation release generation is configured.');

$release->web($config)->server->handle();
```

Infbyte therefore does not construct Webrick `Request` objects, create a second
HTTP kernel, or call an emitter in production. Webrick's boot-selected runtime
adapter owns the single native response write.

`bootstrap/app.php` remains a small development/application façade convenience:

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

Optional package providers are included only when the explicit application
capability topology selects them. Set `APP_CAPABILITIES` to a comma-separated
list such as `database,cache,messaging`; the lean skeleton defaults to none.

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
| Modules | `module:list`, `module:show`, `module:doctor`, `module:plan`, `module:install`, `module:enable`, `module:disable`, `module:repair`, `module:remove`, `module:config:publish`, `module:schema:*` |
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
php infbyte module:plan database
php infbyte module:install database
php infbyte module:enable database
php infbyte module:install communication
php infbyte module:install messaging
php infbyte module:config:publish operations
```

Then add the capabilities the application actually uses to `APP_CAPABILITIES`
(or directly to `app.capabilities`) before compiling a production generation.

The seven package-backed specialist modules are:

- `auth`
- `communication`
- `database`
- `filesystem`
- `messaging`
- `security`
- `validation`

`logging`, `operations`, `resources`, and `session` are Foundation-native
built-in catalog entries. CacheLayer is Foundation core infrastructure and is
not a module; use the core `cache:*` command family instead of
`module:install cache`.

Aliases such as `db`, `crypto`, `otp`, `passkeys`, and `queue` remain
accepted where unambiguous, but application documentation uses purpose names.
OTP and WebAuthn are explicit features of the `auth` module rather than
standalone public modules.

Installing a module does not add global middleware, open connections, or start
workers. Optional config is published only when requested/needed and remains
outside the lean checked-in skeleton by default.

## Module schema lifecycle

Module-owned schemas use the module command family, while core cache schemas use
their dedicated Foundation command family:

```bash
php infbyte module:schema:status auth
php infbyte module:schema:install auth
php infbyte module:schema:install session
php infbyte module:schema:sync

php infbyte cache:schema:status
php infbyte cache:schema:install
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
  cache.php
  notifications.php
  router.php
public/
routes/
  api.php
  console.php
  schedule.php
  workers.php
storage/
  releases/
tests/
composer.json
infbyte
```

Only application-default config is checked in. `cache.php` is included because
CacheLayer is Foundation core infrastructure, and `notifications.php` is included
because notifications are a Foundation-native application surface independent
of the optional communication module. Database/filesystem/messaging/operations/
security/session/validation/communication configuration is published only when
the application chooses those specialist or built-in surfaces.

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

## Production authentication

The checked-in auth defaults are deliberately dependency-light for local
development. Production authentication must opt into durable state and the
specialist packages it actually uses. A minimal OTP-backed production graph is:

```bash
php infbyte module:install database
php infbyte module:install security
php infbyte module:install communication
php infbyte module:install auth --feature=otp
php infbyte module:config:publish session

php infbyte module:schema:install auth --connection=sqlite
php infbyte module:schema:install session --connection=sqlite
```

Then configure the production topology explicitly, for example:

```text
APP_CAPABILITIES=auth,cache,database,security,communication,session

AUTH_STORAGE=database
AUTH_CACHE=cache
AUTH_TOKENS=security
SECURITY_JWT_ISSUER=https://example.com
SECURITY_JWT_AUDIENCE=example-clients
AUTH_MFA=otp
AUTH_NOTIFICATIONS=talkingbytes
AUTH_PASSKEY=disabled

CACHE_STORE=auth-state
CACHE_COUNTER=redis
CACHE_REDIS_DSN=redis://127.0.0.1:6379
CACHE_INTEGRITY_KEY=<deployment secret>

AUTH_OTP_RECOVERY_HMAC_KEY=<deployment secret>
AUTH_OTP_SECRET_PROTECTION_KEYS=[{"id":"primary","environment":"AUTH_OTP_MFA_KEY","status":"active"}]
AUTH_OTP_MFA_KEY=<Base64URL key material>

NOTIFICATIONS_AUTH_TRANSPORT=log
SESSION_DRIVER=database
SESSION_DB_CONNECTION=sqlite
```

`auth-state` and the Redis/Valkey counters are inactive named CacheLayer
resources until selected; the lean skeleton still defaults to the local cache.
Use a real production email transport instead of `log` when authentication
notifications must be delivered. OTP-only installation does not require the
passkey-only WebAuthn package; select `--feature=passkey` only when the
application enables WebAuthn.

Always run `config:validate --production`, provision the applicable schemas,
then require `app:ready` before building the release generation. Keep token,
cache-integrity, MFA-protection, recovery-code and transport credentials in
deployment secret storage rather than source control.

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

## Development / build configuration cache

Foundation delegates both application config-cache layouts directly to ArrayKit:

- `sharded` (the default outside production) writes native namespace cache files
  plus ArrayKit's `__flat.php` exact-leaf index;
- `single` writes one native ArrayKit compiled
  `bootstrap/cache/config/config.php` artifact.

Choose the layout with `APP_CONFIG_CACHE_TYPE=sharded|single` and build/clear it
with:

```bash
php infbyte config:cache
php infbyte config:clear
```

There is no separate `fused` configuration mode. ArrayKit's sharded strategy
already carries fused scalar/null leaf acceleration through `__flat.php`.
Foundation owns cache policy, manifest identity, provider compilation and atomic
publication; it does not maintain another config serializer.

This development/build cache is separate from the immutable production release
generation. `optimize` always publishes its own generation-owned normalized
`config.php` snapshot.

## Production

Build and verify one immutable all-runtime Foundation generation before serving
traffic:

```bash
php infbyte config:validate --production
php infbyte optimize
php infbyte optimize:report
php infbyte app:ready
```

`optimize` compiles web, CLI, worker, and scheduler artifacts together and only
activates the generation after all required artifacts and identities validate.
The previous generation remains active if compilation/verification fails.

The web process must receive trust metadata from deployment/service configuration
outside the writable release directory:

```text
INFOCYPH_FOUNDATION_RELEASE_ROOT=/absolute/path/to/storage/releases
INFOCYPH_FOUNDATION_RELEASE_MANIFEST_SHA256=<Manifest SHA-256 from optimize:report>
```

`INFOCYPH_FOUNDATION_RELEASE_ROOT` may be omitted when the default
`storage/releases` location is used. The manifest SHA-256 is mandatory for the
trusted production web bootstrap; do not derive it from the writable generation
being validated.

The included deployment helper performs the standard validation/build/readiness
sequence:

```bash
./deploy.sh
```

Clear generated release generations with:

```bash
php infbyte optimize:clear
```

Release generations are deployment-owned and ignored by the skeleton repository.
Production requests consume the generation-owned normalized config and compiled
Webrick/InterMix artifacts; they do not rediscover project config, providers, or
route files. Webrick matcher caches remain Webrick-native for fused, generated,
and sharded strategies, while InterMix generated PHP remains the native DI
production cache. CacheLayer is core application infrastructure, not a wrapper
around either compiled routing or compiled DI artifacts.

Before deployment:

- set production environment/debug policy;
- explicitly select only the capabilities the application uses;
- run application migrations;
- provision applicable module schemas;
- require `config:validate --production` / `app:ready` to pass;
- build/activate the release generation;
- install its trusted manifest SHA-256 into the web/service process environment;
- use a production web server and external process manager.


## Upgrading an Infbyte 2.1 application

Foundation 3 is a runtime-generation migration, not an in-place reuse of
Foundation 2 generated caches. For a representative 2.1 application:

1. change the Foundation runtime constraint from `^2.1.1` to `^3.0`;
2. add an explicit `APP_CAPABILITIES` topology for production;
3. remove `app.container.alias`, `app.container.compiled_activation`, and any
   retired compiled-container path assumptions;
4. remove any `router.cache` or `route:cache` deployment path assumptions;
5. delete/ignore old `bootstrap/cache/container/*` and
   `bootstrap/cache/routes/*` artifacts rather than trusting them after upgrade;
6. add the core `config/cache.php` template, but do not install cache as a
   module;
7. update the production front controller to the trusted
   `FoundationReleaseBootstrap` path and keep manifest trust in deployment
   metadata outside the writable release directory;
8. review specialist modules separately: installation does not enable a
   capability, auth OTP/passkey features are explicit, and cache schemas use
   `cache:schema:*`;
9. rebuild with `php infbyte optimize`, install the returned manifest SHA-256
   into the process environment, then run `php infbyte app:ready`;
10. rehearse persisted auth/session/schema/queue data changes before rollout.
    Code rollback does not automatically roll back database rows, queue payloads,
    or key formats written by a newer generation.

Do not assume Foundation 2.1 authentication/browser-session wire formats are
valid Foundation 3 state. For each deployed application, explicitly decide
whether existing credentials/tokens are retained, migrated, or revoked; force
browser re-authentication when no verified session migration exists. OAuth and
passkey state need the same explicit review when those features are in use.
Apply database/schema changes additively first and use expand-contract rollout
while old/new generations can coexist. Durable queue/message payloads likewise
need version-compatible consumers before incompatible cleanup.

See the
[Foundation 2.x → 3.0 migration guide](https://github.com/infocyph/Foundation/blob/3.0/docs/foundation-3-migration.md)
for provider API, persisted-state, lower-library, and rollout details.

## Testing and release checks

The current skeleton Composer file does not invent generic test/release script
aliases. Run the tools/scripts actually installed by the application/release
candidate. Foundation's full Composer/PHPForge/static/PHPUnit/integration and
performance matrix is performed in its dedicated release-verification phase.

## Documentation

- [Foundation 3.0 documentation](https://github.com/infocyph/Foundation/tree/3.0/docs)
- [Foundation 3 migration guide](https://github.com/infocyph/Foundation/blob/3.0/docs/foundation-3-migration.md)
- [Omnibus](https://github.com/infocyph/Omnibus)
- [Webrick](https://github.com/infocyph/Webrick)

Infbyte keeps framework details in Foundation documentation rather than copying
them into the application skeleton.
