# Infbyte — Foundation 2.0 Live Work Plan

## Branches

- Infbyte: `feature/foundation-2.0`
- Foundation: `feature/foundation-2.0`

## Current checkpoint

- Date: 2026-08-24
- Infbyte source checkpoint: `56cb73e18eab07f34242a929eccbc9e6572d9971`
- Infbyte documentation checkpoint: `26a35da0926285119c31ed880bf1f5aa06f3cf19`
- Foundation source checkpoint: `493c39a7a06bac0455397556254f0f8e7e25f973`
- Foundation documentation checkpoint: `944220490e1c28e9945fd398265dc9d072eb4c93`
- Infbyte branch base: `main` at `47fb985f266c977504c3dca6bd13e85c9a1b73dc`
- Current phase: **Foundation 2.0 public-name/config freeze complete; ready for deferred verification matrix**.
- Full PHPUnit/static/PHPForge/runtime/release matrix: **not run yet**.

# Ownership boundary

Foundation owns reusable framework/runtime behavior: explicit runtimes, DI/provider activation, CLI, scheduler/worker composition, purpose modules, schema orchestration, optimization, operational controls, application contracts, and specialist-package integration.

Infbyte owns only the opinionated host skeleton: application bootstrap, app-specific defaults, routes/application code, writable layout, deployment conventions, and final developer experience.

Specialist packages retain their own database/cache/messaging/communication/crypto/validation/filesystem engines.

# Frozen Infbyte structure

- root `infbyte` delegates to Foundation `CommandDispatcher`;
- Web bootstrap delegates to one `Foundation::web()` application;
- CLI/Worker/Scheduler runtime selection is Foundation-owned;
- Foundation has exactly Web, CLI, Worker, Scheduler runtimes;
- provider groups are `common|web|cli|worker|scheduler`;
- checked-in config remains deliberately only `app.php`, `auth.php`, `router.php`;
- optional capability config is module-published on demand;
- `routes/console.php` registers application commands;
- `routes/schedule.php` registers schedule definitions;
- `routes/workers.php` registers non-message maintenance workers;
- generated optimized artifacts are not committed;
- deployment optimization uses `php infbyte optimize`.

No retired Console runtime hierarchy is reintroduced.

# Foundation dependency baseline consumed by Infbyte

Core Foundation runtime:

- PHP `^8.4`
- `infocyph/arraykit ^5.1.1`
- `infocyph/intermix ^9.2`
- `infocyph/uid ^5.0`
- `infocyph/webrick ^4.0.2`

Optional capability packages:

- `infocyph/cachelayer ^3.2.0`
- `infocyph/dblayer ^4.1`
- `infocyph/epicrypt ^2.1`
- `infocyph/omnibus ^2.4`
- `infocyph/otp ^6.0`
- `infocyph/pathwise ^3.1`
- `infocyph/reqshield ^3.0.1`
- `infocyph/talkingbytes ^2.0`
- `web-auth/webauthn-lib ^5.3.5`
- `infocyph/phpforge dev-main@dev`

During branch development Infbyte requires:

```json
"infocyph/foundation": "dev-feature/foundation-2.0 as 2.0.x-dev"
```

Final release alignment will move to the stable Foundation 2.0 constraint.

# Frozen purpose-first modules

Canonical modules:

- `auth`
- `cache`
- `communication`
- `database`
- `filesystem`
- `logging`
- `messaging`
- `operations`
- `resources`
- `security`
- `session`
- `validation`

Canonical aliases include:

- `db|dblayer -> database`
- `crypto|epicrypt -> security`
- `otp|mfa|passkey|passkeys|webauthn -> auth`
- `notifications|talkingbytes -> communication`
- `events|omnibus|queue|queues -> messaging`
- `files|pathwise|storage -> filesystem`
- `reqshield|validator -> validation`
- `ops|runtime -> operations`

No standalone OTP/passkey public module exists.

# Config/schema lifecycle inherited from Foundation

Commands:

- `module:list`
- `module:show <module>`
- `module:install <module>`
- `module:remove <module>`
- `module:config:publish <module> [--force]`
- `module:schema:status <module> [--connection=...]`
- `module:schema:install <module> [--connection=...]`
- `module:schema:sync [--connection=...]`

Schema owners:

- `auth` -> Foundation auth schema;
- `cache` -> CacheLayer public PDO/SQLite/invalidation schemas;
- `session` -> Foundation database-session schema.

Schema status is read-only. Explicit installation owns mutation. Module removal never drops schema/data.

Infbyte does not copy optional config into the skeleton merely because Foundation supports the capability.

# Application API rule

Foundation `Application` is a narrow runtime/composition object. Infbyte application code should resolve real services through DI:

```php
$service = $app->make(ServiceClass::class);
```

Do not rely on or recreate removed convenience facades such as:

- `$app->auth()`;
- `$app->session()` / `$app->browserSession()`;
- `$app->router()`;
- `$app->responses()`;
- `$app->testing()`;
- `$app->messaging()`;
- generic cache/database/filesystem/security manager methods.

Concrete Foundation application services and native specialist services are resolved through constructor injection or `Application::make()`.

# Application contracts available to Infbyte apps

## Validation

- Foundation `FormRequest` composes Webrick request input with ReqShield;
- custom rules implement ReqShield `Contracts\Rule` directly;
- generators: `create:request`, `create:rule`.

## Notifications/mail

Foundation application routing:

- `Notification`
- `NotificationRecipient`
- `NotificationChannel`
- `NotificationDispatcher` / registry

TalkingBytes-backed mail:

- `MailMessage`
- `Mailer`
- `MailNotificationChannel`

Generators: `create:mail`, `create:notification`, `create:notification-channel`.

## Messaging/jobs

- `Job`
- `JobContext`
- `JobMiddleware`
- Omnibus-backed handler pipeline
- generators: `create:job`, `create:handler`, `create:job-middleware`.

## Resources/testing

- `JsonResource::resolve(): mixed`;
- `create:resource` targets the current contract;
- resolve `JsonDispatchResponseFactory`, `AuthServices`, and `TestKit` through DI rather than Application shortcuts.

# Runtime/operations inherited by Infbyte

## Omnibus 2.4 workers

- single messaging workers use native Omnibus `WorkerLifecycle` for heartbeat/reload/stop;
- no `pcntl` requirement solely for single-worker generation polling;
- Unix `WorkerPool` remains pcntl/posix based and retains the Foundation watchdog;
- provider-only workers remain messaging-lazy.

## Scheduler ownership

- overlap/single-server locks refresh during child execution;
- lost lease terminates/fails the child;
- schedule history uses stable identity;
- `schedule:test` reports actual failure status.

## Runtime control

- file/cache generation-map mutations are atomic/serialized;
- cache-backed runtime control requires suitable shared visibility and coordination;
- runtime registry visibility is `host|shared`, default `host`;
- process registry is observability metadata, not process-supervision truth.

## Other operations

- supervised child commands do not duplicate `--profile` output;
- `log:tail --follow` handles truncation/rotation;
- production OTP validation uses production topology assumptions;
- environment encryption remains Epicrypt-backed with external key material only;
- cache schema status does not create a missing SQLite file.

# CLI inheritance

Because the root executable delegates directly to Foundation, Infbyte inherits the frozen Foundation command catalog without duplicate command classes.

Major families:

- application/config/cache/optimization;
- database/migrations;
- modules/config/schema lifecycle;
- execution/maintenance/runtime control;
- messaging/queue/scheduling/workers;
- storage/session/auth operations;
- environment protection/logging;
- `create:*` generators.

Global controls include `--quiet`, `--silent`, `-v|-vv|-vvv`, `--profile`, `--json`, `--env`, `--no-interaction`, help/version/completion.

# Documentation alignment completed

Infbyte README now reflects:

- Foundation 2.0 runtime boundary;
- no separate Console framework;
- current purpose-first modules;
- unified module schema lifecycle;
- current generators/operations;
- DI instead of broad Application facades;
- lean checked-in config;
- deployment-owned optimize artifacts;
- absence of invented Composer test/release aliases.

Foundation documentation is the detailed framework source of truth; Infbyte does not duplicate it.

# Deliberate Infbyte non-changes

Infbyte application source/config checkpoint remains unchanged through the Foundation cleanup/doc-freeze work. This is intentional:

- no optional operations/messaging/notifications/database/cache/etc. config is copied into the base skeleton;
- `.env.example` remains lean and contains no environment-encryption key;
- no queue/cache/database/communication/validation/schema implementation is duplicated;
- no Infbyte workaround is added for a Foundation defect;
- no generated optimized artifacts are committed.

# Verification status

Public names/config are frozen. The full Foundation 2.0 + Infbyte verification matrix remains **not run yet**.

Next phase:

1. Composer/dependency validation;
2. PHPForge/static analysis;
3. PHPUnit/integration suites;
4. clean create-project/install path;
5. core-only Foundation/Infbyte runtime;
6. purpose-module install/remove/config/schema matrix, including partial auth bundle/install ordering;
7. `app:ready` / production config diagnostics;
8. CLI/global-option/help/completion matrix;
9. Web/CLI/Worker/Scheduler isolation;
10. maintenance/reload/worker/scheduler lifecycle;
11. queue failure/job middleware/pool/fork behavior;
12. DB/destructive/env/storage/optimization safety;
13. representative performance/soak checks;
14. fix defects and move Foundation/Infbyte constraints to stable release alignment.

# Do not regress

- no package-per-module public model;
- no standalone OTP/passkey module;
- no duplicate specialist schema command families;
- no schema/data deletion during module removal;
- no copied specialist engine/SQL/queue/retry/cache/database/communication implementation;
- no broad Application/service facade in Infbyte;
- no retired Console runtime hierarchy;
- no static global application state;
- no optional config copied into the skeleton by default;
- no environment-protection key in `.env`/`.env.example`;
- no generated optimized artifacts committed.
