# Infbyte — Foundation 2.0 Live Work Plan

## Branches

- Infbyte: `feature/foundation-2.0`
- Foundation: `feature/foundation-2.0`

## Current checkpoint

- Date: 2026-08-23
- Infbyte source: `8e5e9443be1a40a770fc80b4ffaf22322dc08de9`
- Foundation source: `11801b097f93b593df98e5a3c3d3fdca702166f1`
- Infbyte branch base: `main` at `47fb985f266c977504c3dca6bd13e85c9a1b73dc`
- Phase: pre-documentation cleanup.
- Latest completed cleanup: purpose-first modules and auth config alignment.
- Full tests/release gates remain deferred.

## Ownership

Foundation owns reusable runtime composition, CLI/runtime machinery, module policy, configuration machinery, optimized artifacts, and integrations.

Infbyte owns project bootstrap, application config, routes/code, deployment conventions, and skeleton developer experience.

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
- `otp`, `mfa`, `passkey`, `passkeys`, and `webauthn` resolve to `auth`;
- `module:install auth` installs both OTP and WebAuthn dependencies;
- runtime configuration still enables OTP and WebAuthn independently.

Foundation now supports multi-package module bundles and reports `built-in`, `installed`, `partial`, or `available` module status.

## Auth config alignment

`config/auth.php` documents purpose-module requirements for optional choices:

- database-backed auth storage -> `module:install database`;
- cache-backed auth state -> `module:install cache`;
- enhanced password/token drivers -> `module:install security`;
- OTP or WebAuthn -> `module:install auth`;
- external communication notifications -> `module:install communication`.

Package installation, config publication, and runtime activation remain separate states.

## Immediate next work

Continue the joint cleanup pass before documentation freeze. Review remaining public/application-facing naming, CLI, configuration, and ownership surfaces. Then update docs, freeze public names/config, and run the deferred release matrix.

## Do not regress

- no package-per-module public model;
- no standalone OTP/passkeys modules;
- no retired Console runtime hierarchy;
- no generic ID-driver/request-scope compatibility;
- no broad manager/facade proxies in Infbyte;
- no copied optional-module config by default;
- no generated optimized artifacts committed.
