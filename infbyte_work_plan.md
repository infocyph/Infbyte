# Infbyte — Foundation 2.0 Live Work Plan

## Branches

- Infbyte: `feature/foundation-2.0`
- Foundation: `feature/foundation-2.0`

## Current checkpoint

- Date: 2026-08-23
- Infbyte source: `56cb73e18eab07f34242a929eccbc9e6572d9971`
- Foundation source: `b81f508c2d14f1b7bb6a4dc63982ba19cde7fb81`
- Infbyte branch base: `main` at `47fb985f266c977504c3dca6bd13e85c9a1b73dc`
- Phase: pre-documentation cleanup.
- Latest completed cleanup: purpose-first modules + module-owned schema lifecycle.
- Full tests/release gates remain deferred.

## Ownership

Foundation owns reusable runtime composition, CLI/runtime machinery, purpose-module policy, module schema orchestration, configuration machinery, optimized artifacts, and integrations.

Infbyte owns project bootstrap, application config, routes/code, deployment conventions, and skeleton developer experience.

Specialist libraries retain their own storage engines and public schema grammar. Foundation orchestrates native schema APIs rather than copying their SQL.

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
| `resources` | built-in response resources |
| `security` | cryptography, password/token, keys |
| `session` | built-in sessions, CSRF, flash |
| `validation` | request/config/schema/database validation |

Naming rules:

- `database` is canonical; `db` remains an alias;
- `security` is canonical; `crypto` remains an alias;
- OTP and passkeys are no longer separate modules;
- `otp|mfa|passkey|passkeys|webauthn` resolve to `auth`;
- `module:install auth` installs both OTP and WebAuthn dependencies;
- runtime configuration still enables OTP and WebAuthn independently.

Foundation reports `built-in|installed|partial|available` module status and now includes owned schema metadata in `module:list`.

## Module-owned schema lifecycle

A module installation is now operationally complete for database-backed capabilities rather than stopping at Composer/config publication.

Current database schema ownership:

| Module | Schema behavior |
|---|---|
| `auth` | Foundation auth schema covering accounts, sessions/tokens, MFA factors/revisions, passkeys and authorization |
| `cache` | CacheLayer native PDO/SQLite cache-entry schema and active PDO invalidation schema |
| `session` | Foundation database-session schema |

Other modules currently own no application tables. The `database` module provides DB/migration infrastructure rather than another application schema.

CacheLayer node/tiered SQLite internals continue to self-initialize inside CacheLayer; Infbyte/Foundation do not copy private CacheLayer SQL.

### Commands

- `php infbyte module:schema:status <module>`
- `php infbyte module:schema:install <module>`
- `php infbyte module:schema:sync`

All accept `--connection` where applicable.

Old `auth:schema:*` and `session:schema:*` command families are removed in favor of the single module schema lifecycle.

### `module:install` lifecycle

After package/config changes Foundation now:

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

## Immediate next work

Continue the joint cleanup pass before documentation freeze. Review remaining public/application-facing naming, CLI, configuration, and ownership surfaces. Then update docs, freeze public names/config, and run the deferred release matrix.

## Do not regress

- no package-per-module public model;
- no standalone OTP/passkeys modules;
- no duplicated specialized schema command families;
- no schema/data deletion during module removal;
- no copied specialist-package SQL;
- no retired Console runtime hierarchy;
- no generic ID-driver/request-scope compatibility;
- no broad manager/facade proxies in Infbyte;
- no copied optional-module config by default;
- no generated optimized artifacts committed.
