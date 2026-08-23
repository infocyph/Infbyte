# Infbyte — Foundation 2.0 Live Work Plan

## Branches

- Infbyte: `feature/foundation-2.0`
- Foundation: `feature/foundation-2.0`

## Current checkpoint

- Date: 2026-08-23
- Infbyte source: `56cb73e18eab07f34242a929eccbc9e6572d9971`
- Foundation source: `3d8b2350094fbf8b031290b17a4085643234b563`
- Infbyte branch base: `main` at `47fb985f266c977504c3dca6bd13e85c9a1b73dc`
- Phase: pre-documentation cleanup is complete for modules, schemas, CLI, and operational runtime surfaces.
- Latest completed cleanup: Foundation capability-driven CLI + operations runtime integration.
- Full tests/release gates remain deferred.

## Ownership

Foundation owns reusable runtime composition, CLI/runtime machinery, purpose-module policy, module schema orchestration, configuration machinery, optimized artifacts, operational runtime controls, and integrations.

Infbyte owns project bootstrap, application config, routes/code, deployment conventions, and skeleton developer experience.

Specialist libraries retain their own storage/communication/crypto/messaging/database engines and public schema grammar. Foundation orchestrates those APIs rather than copying their implementations.

## Completed migration baseline

- root `infbyte` delegates to Foundation `CommandDispatcher`;
- retired Console bootstrap/runtime removed;
- Web bootstrap delegates to `Foundation::web()`;
- provider groups are `common|web|cli|worker|scheduler`;
- old IDs-driver and request-scope config removed;
- checked-in config stays lean: `app`, `auth`, `router`;
- route files use Foundation's scoped registrar contract;
- generated optimized artifacts are not committed;
- deployment uses `php infbyte optimize`.

## Purpose-first module model

Public modules represent application capabilities, not Composer packages.

| Module | Purpose |
|---|---|
| `auth` | optional OTP/MFA and WebAuthn/passkeys |
| `cache` | cache, shared state, coordination |
| `communication` | HTTP, email, webhook, gRPC |
| `database` | persistence, schema, migrations |
| `filesystem` | storage, files, uploads, archives |
| `logging` | built-in logging |
| `messaging` | events, queues, workers, workflows |
| `operations` | built-in maintenance, execution history, runtime control and process visibility |
| `resources` | built-in response resources |
| `security` | cryptography, password/token, keys |
| `session` | built-in sessions, CSRF, flash |
| `validation` | request/config/schema/database validation |

Naming rules:

- `database` is canonical; `db` remains an alias;
- `security` is canonical; `crypto` remains an alias;
- OTP and passkeys are no longer separate modules;
- `otp|mfa|passkey|passkeys|webauthn` resolve to `auth`;
- `ops|runtime` resolve to `operations`;
- `module:install auth` installs both OTP and WebAuthn dependencies;
- runtime configuration still enables OTP and WebAuthn independently.

Foundation reports `built-in|installed|partial|available` module status and includes owned schema metadata in `module:list`.

## Module config + schema lifecycle

A module installation is operationally complete for database-backed capabilities rather than stopping at Composer/config publication.

Current database schema ownership:

| Module | Schema behavior |
|---|---|
| `auth` | Foundation auth schema covering accounts, sessions/tokens, MFA factors/revisions, passkeys and authorization |
| `cache` | CacheLayer native PDO/SQLite cache-entry schema and active PDO invalidation schema |
| `session` | Foundation database-session schema |

Other modules currently own no application tables. The `database` module provides DB/migration infrastructure rather than another application schema.

CacheLayer node/tiered SQLite internals continue to self-initialize inside CacheLayer; Infbyte/Foundation do not copy private CacheLayer SQL.

### Commands

- `php infbyte module:list`
- `php infbyte module:show <module>`
- `php infbyte module:install <module>`
- `php infbyte module:remove <module>`
- `php infbyte module:config:publish <module> [--force]`
- `php infbyte module:schema:status <module>`
- `php infbyte module:schema:install <module>`
- `php infbyte module:schema:sync`

Schema commands accept `--connection` where applicable.

Old `auth:schema:*` and `session:schema:*` command families remain removed in favor of the single module schema lifecycle.

### `module:install` lifecycle

After package/config changes Foundation:

1. invalidates compiled runtime container state;
2. launches schema synchronization in a fresh PHP process so the updated Composer autoloader is used;
3. provisions only schemas required by current application configuration;
4. reports schema state and fails when an active required schema cannot be provisioned.

This makes install order safe: a later `module:install database` can synchronize already-configured database auth/session/cache requirements.

`module:remove` never drops schemas or user/application data.

`app:ready` also verifies applicable module schemas, so package installation alone no longer counts as persistence readiness.

## Auth config alignment

`config/auth.php` documents purpose-module requirements and schema behavior:

- database-backed auth storage -> `module:install database` plus Foundation auth schema;
- cache-backed auth state -> `module:install cache`;
- enhanced password/token drivers -> `module:install security`;
- OTP or WebAuthn -> `module:install auth`;
- external communication notifications -> `module:install communication`;
- schema state may be checked/prepared with `module:schema:status auth` / `module:schema:install auth`.

Package installation, config publication, schema readiness, and runtime activation remain distinct states.

## Foundation CLI expansion inherited by Infbyte

No second Infbyte command layer was added. Because the root launcher already delegates to Foundation `CommandDispatcher`, the expanded Foundation catalog is automatically the Infbyte CLI surface.

### Database/cache

New/expanded commands include:

- `db:monitor`;
- `db:wipe`;
- `migrate --pretend`;
- `migrate:rollback --batch=N`;
- `cache:forget`.

Foundation delegates database monitoring/migration/schema primitives to DBLayer and cache primitives to CacheLayer.

### Messaging/queues

New/expanded commands include:

- `messaging:list`;
- `queue:failed`;
- `queue:failed:show`;
- `queue:retry`;
- `queue:forget`;
- `queue:flush`;
- `queue:prune-failed`;
- `queue:monitor`.

Failure/transport/worker mechanics remain Omnibus-owned.

### Scheduling/storage/auth/logging

New/expanded commands include:

- `schedule:test`;
- `schedule:interrupt`;
- richer `schedule:list` execution state;
- `storage:status`;
- `storage:unlink`;
- `auth:prune`;
- `log:tail [--follow]`.

### Generators

The Foundation generator surface now includes:

- `create:config`;
- `create:resource`.

No fake application abstractions were introduced merely to reproduce Laravel command names. Request/rule/mail/notification generators remain absent until corresponding framework contracts exist.

## Operations module/runtime

Foundation now provides built-in `operations` configuration for:

```text
operations.history.*
operations.maintenance.*
operations.runtime_control.*
operations.runtime_registry.*
```

The skeleton does **not** check in `config/operations.php` by default. Foundation defaults make the built-in module usable dependency-free, while applications that need tuning can publish it with:

```bash
php infbyte module:config:publish operations
```

### Execution history

- `execution:list`;
- `execution:show`;
- `execution:clear`.

History is opt-in because it writes operational metadata.

### Maintenance

- `maintenance:enable`;
- `maintenance:disable`;
- `maintenance:status`.

The default state driver is file-backed. Cache-backed shared state is available for multi-node deployment. Foundation's HTTP kernel enforces maintenance with HTTP 503 and optional `Retry-After`.

### Persistent runtime control

- `runtime:reload`;
- `worker:restart [name]`;
- `worker:status [name]`;
- `schedule:interrupt`.

Foundation uses generation markers and heartbeat-visible worker/scheduler process records. It requests graceful shutdown; Supervisor/systemd/Docker/Kubernetes or another process manager remains responsible for replacement processes.

Messaging remains lazily activated only when a configured messaging worker is actually selected.

## Environment protection

Infbyte inherits:

- `env:encrypt`;
- `env:decrypt`.

Foundation delegates encryption/decryption to Epicrypt. Key material is supplied through an external environment variable (default `ENV_ENCRYPTION_KEY`) or `--key-file`.

`ENV_ENCRYPTION_KEY` is deliberately **not** added to `.env.example`: storing the key in the environment file being protected would defeat the protection boundary.

Forced replacement is staged and restores the previous target when publication/finalization fails.

## Global CLI controls

The root `infbyte` launcher now automatically exposes Foundation's global command controls:

- `-q|--quiet`;
- `--silent`;
- `-v|-vv|-vvv`;
- `--profile`;
- `-n|--no-interaction`;
- `--json`;
- `--env=...`;
- help/version/completion.

`--profile` writes diagnostics to STDERR so command/JSON stdout remains clean. `--silent` suppresses all output and interactive prompting.

## Deliberate Infbyte non-changes in this batch

Infbyte source checkpoint remains `56cb73e18eab07f34242a929eccbc9e6572d9971` because no application-side workaround or duplicate command/config layer is needed.

In particular:

- `.env.example` remains lean;
- optional operations environment variables are not listed before `operations.php` is published;
- encryption key material is not placed in `.env.example`;
- `config/operations.php` is not copied into the default skeleton;
- no queue/database/cache/security implementation is duplicated in Infbyte.

This is intentional architecture, not missing implementation.

## Source-audit status

Foundation completed a source/config consistency audit of the expanded CLI/operations batch at source checkpoint `3d8b2350094fbf8b031290b17a4085643234b563`.

The audit confirmed command-to-handler wiring, purpose-module/config consistency, lazy worker capability activation, storage unlink safety, maintenance enforcement, runtime-control key alignment, and staged environment protection.

No PHPUnit/static-analysis/PHPForge/release matrix was run as part of this cleanup batch.

## Immediate next work

The joint module/schema/CLI/operations cleanup pass is ready for **Foundation + Infbyte documentation reconciliation**, unless another public-surface cleanup topic is intentionally opened first.

After documentation reconciliation:

1. freeze public command/module/config names;
2. run the deferred test/release matrix;
3. perform final Foundation 2.0 + Infbyte compatibility/release review.

## Do not regress

- no package-per-module public model;
- no standalone OTP/passkeys modules;
- no duplicated specialized schema command families;
- no schema/data deletion during module removal;
- no copied specialist-package SQL;
- no fake generator-only abstractions;
- no retired Console runtime hierarchy;
- no generic ID-driver/request-scope compatibility;
- no broad manager/facade proxies in Infbyte;
- no copied optional-module config by default;
- no environment-protection key in `.env`/`.env.example`;
- no generated optimized artifacts committed.
