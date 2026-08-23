# Infbyte — Foundation 2.0 Live Work Plan

## Branches

- Infbyte: `feature/foundation-2.0`
- Foundation: `feature/foundation-2.0`

## Current checkpoint

- Date: 2026-08-24
- Infbyte source checkpoint: `56cb73e18eab07f34242a929eccbc9e6572d9971`
- Foundation source checkpoint: `493c39a7a06bac0455397556254f0f8e7e25f973`
- Infbyte branch base: `main` at `47fb985f266c977504c3dca6bd13e85c9a1b73dc`
- Current phase: **Foundation + Infbyte documentation reconciliation and public-name/config freeze**.
- Application-contract/API cleanup: complete in Foundation.
- Full PHPUnit/static/PHPForge/runtime/release matrix: not run yet.

## Ownership

Foundation owns reusable runtime composition, DI/provider activation policy, CLI/runtime machinery, purpose modules, module schema orchestration, configuration/optimization, operational runtime controls, application contracts and specialist integrations.

Infbyte owns the opinionated application skeleton: project bootstrap, app-specific config, routes/application code, deployment conventions and final developer experience.

Specialist packages retain their storage/database/cache/communication/crypto/messaging/validation engines. Neither Foundation nor Infbyte copies those implementations.

## Fixed Infbyte structure

- root `infbyte` delegates to Foundation `CommandDispatcher`;
- web bootstrap delegates to one `Foundation::web()` application;
- runtime selection for CLI/Worker/Scheduler is Foundation-owned;
- provider groups are `common|web|cli|worker|scheduler`;
- checked-in config remains deliberately lean: `app.php`, `auth.php`, `router.php`;
- optional module config is published on demand, not copied into the skeleton;
- route files use the loader-provided scoped Webrick registrar;
- `routes/console.php` and `routes/workers.php` remain application registration surfaces;
- generated optimized artifacts are not committed;
- deployment optimization uses `php infbyte optimize`.

## Current Foundation package baseline

Core:

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

## Purpose-first modules

| Module | Purpose |
|---|---|
| `auth` | OTP/MFA + WebAuthn/passkeys |
| `cache` | cache/shared state/coordination |
| `communication` | HTTP/email/webhook/gRPC |
| `database` | DB/persistence/schema/migrations |
| `filesystem` | storage/files/uploads/downloads/archives |
| `logging` | built-in logging |
| `messaging` | Omnibus 2.4 messages/events/queues/middleware/workers |
| `operations` | built-in maintenance/history/runtime control/process visibility |
| `resources` | built-in response resources |
| `security` | cryptography/password/token/key services |
| `session` | built-in sessions/CSRF/flash/locking |
| `validation` | ReqShield-backed request/config/schema/database validation |

Canonical aliases remain purpose-oriented: `db|dblayer -> database`, `crypto|epicrypt -> security`, `otp|mfa|passkey|passkeys|webauthn -> auth`, `ops|runtime -> operations`.

No standalone OTP/passkey public module exists.

## Module config + schema lifecycle inherited by Infbyte

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

- auth -> Foundation auth schema;
- cache -> CacheLayer native PDO/SQLite/invalidation schemas;
- session -> Foundation database-session schema.

Schema status is read-only; explicit installation owns creation. `module:remove` never drops schema/data.

Forced config publication is transactional, refuses symbolic-link replacement and reports incomplete rollback rather than silently leaving partial files.

## Application contracts now available to Infbyte apps

### Validation

- Foundation `FormRequest` composes Webrick request input into ReqShield;
- custom rules implement ReqShield `Contracts\Rule` directly;
- generators: `create:request`, `create:rule`.

### Notifications/mail

Foundation core routing is built in:

- `Notification`;
- `NotificationRecipient`;
- `NotificationChannel`;
- `NotificationDispatcher`/registry.

Custom notification channels do not require TalkingBytes.

Mail remains optional communication infrastructure:

- Foundation `MailMessage`/`Mailer` adapt TalkingBytes email;
- `create:mail` and the default mail-based `create:notification` require communication;
- `create:notification-channel` is package-neutral.

### Messaging/jobs

- `Job` data-message marker;
- `JobContext`;
- `JobMiddleware`;
- Omnibus-backed handler pipeline;
- generators: `create:job`, `create:handler`, `create:job-middleware`.

### Resources

- built-in `JsonResource`;
- `create:resource` uses the current `resolve(): mixed` contract.

## Application object rule

Foundation `Application` is no longer a broad service facade. It retains runtime/bootstrap state, config/container/provider/service resolution, execution scope, paths and canonical HTTP handling.

App code should inject/resolve concrete services rather than calling convenience proxies for auth/session/router/responses/testing or specialist managers.

## Runtime/operations behavior inherited by Infbyte

### Omnibus 2.4 workers

- single messaging workers use Omnibus native `WorkerLifecycle` for heartbeat/reload/stop handling;
- this path no longer requires `pcntl`;
- Unix `WorkerPool` retains the watchdog because the upstream pool itself is pcntl/posix based;
- provider-only workers remain messaging-lazy.

### Scheduler ownership

- overlap/single-server locks refresh throughout child execution;
- lost lease terminates/fails the child instead of continuing without ownership;
- schedule history uses stable schedule identity, not only command text;
- `schedule:test` reports real failure status.

### Runtime control

- generation-map mutations are atomic for file and CacheLayer-backed state;
- cache-backed runtime control requires suitable shared visibility and coordination;
- process registry visibility is `host|shared`, default `host`;
- `worker:status` exposes the registry view;
- process registry remains observability metadata, not a daemon supervisor.

### Other operations

- supervised child commands do not duplicate `--profile` output;
- `log:tail --follow` handles truncation/rotation;
- production OTP validation uses production topology assumptions;
- `AuthPruner` covers disposable expired/consumed/revoked auth state;
- environment encryption remains Epicrypt-backed with external key material only.

## Readiness behavior

`app:ready` now accounts for capability-specific dependencies, including:

- CacheLayer for session locks;
- CacheLayer for migration locks;
- CacheLayer for cache-backed maintenance/runtime-control state;
- DBLayer for explicit validation DB connections;
- exact active package(s) inside the multi-package auth module;
- applicable auth/cache/session schema readiness.

Infbyte does not add a second readiness layer.

## CLI inheritance

Because `infbyte` directly delegates to Foundation, the skeleton automatically receives the current capability-oriented command catalog and generator surface. No duplicate Infbyte command classes are introduced.

Global controls include `--quiet`, `--silent`, `-v|-vv|-vvv`, `--profile`, `--json`, `--env`, `--no-interaction`, help/version/completion.

## Deliberate Infbyte non-changes

The Infbyte **source** checkpoint remains `56cb73e18eab07f34242a929eccbc9e6572d9971` throughout these Foundation cleanup batches.

That is intentional:

- no optional `operations.php`, `messaging.php`, `notifications.php`, validation or other module config is copied into the base skeleton;
- `.env.example` remains lean;
- no environment-encryption key is stored in `.env`/`.env.example`;
- no queue/cache/database/communication/validation/schema implementation is duplicated;
- no workaround is added in Infbyte for a Foundation defect.

## Verification status

Current work is a source/config/API audit only. The full Foundation 2.0 + Infbyte verification matrix remains intentionally deferred until documentation/public names are frozen.

## Immediate next work

1. reconcile Foundation README/docs with source checkpoint `493c39a7a06bac0455397556254f0f8e7e25f973`;
2. reconcile Infbyte README/examples with that public surface;
3. remove stale Omnibus 2.3 / CacheLayer 3.1 / old generator / old Application-facade references;
4. freeze public command/module/config/class names;
5. run the deferred PHPUnit/static/PHPForge/module/runtime/fork/performance verification matrix;
6. fix verification defects and prepare Foundation 2.0 + Infbyte release alignment.

## Do not regress

- no package-per-module public model;
- no standalone OTP/passkey module;
- no duplicate specialist schema command families;
- no schema/data deletion during module removal;
- no copied specialist SQL/queue/retry/cache/database/communication engine;
- no broad Application/service facade in Infbyte;
- no retired Console runtime hierarchy;
- no static global application state;
- no optional config copied into the skeleton by default;
- no environment-protection key in `.env`/`.env.example`;
- no generated optimized artifacts committed.
