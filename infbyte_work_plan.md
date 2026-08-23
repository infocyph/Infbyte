# Infbyte — Foundation 2.0 Live Work Plan

> Current execution tracker for migrating the Infbyte application skeleton to Foundation 2.0.

## Working branches

- Infbyte: `feature/foundation-2.0`
- Foundation: `feature/foundation-2.0`

## Maintenance rule

After each completed joint batch:

1. record the latest Infbyte and Foundation source checkpoints;
2. move finished work to **Completed**;
3. keep **Immediate next work** limited to the next concrete cross-repo phase;
4. fix framework defects in Foundation rather than working around them in Infbyte;
5. do not reintroduce Foundation 1.x/Console compatibility;
6. keep specialist-library engines in their owning packages;
7. keep the full test/release matrix deferred until implementation/config/docs are stable.

# Current checkpoint

- Date: 2026-08-23
- Infbyte source checkpoint: `297d171f793f083795fba6ee70876a5170ff5f66`
- Foundation source checkpoint: `2e1415fd871d1564a70d08b18da45ebf243fe4e5`
- Infbyte branch base: `main` at `47fb985f266c977504c3dca6bd13e85c9a1b73dc`
- Status: **Foundation 2.0 implementation/config migration baseline complete; documentation reconciliation is next.**
- Full PHPUnit/static-analysis/PHPForge/release matrix: not run yet.

# Ownership boundary

## Foundation owns

- Application/runtime composition;
- Web, CLI, Worker and Scheduler runtime modes;
- InterMix container/scopes;
- command parsing/preflight/dispatch/execution;
- config/cache/optimized-artifact machinery;
- reusable module installation/publication policy;
- reusable framework defaults;
- specialist-package bridges.

## Infbyte owns

- project Web bootstrap and public entrypoint;
- application-facing config overrides;
- provider lists;
- routes/application code;
- root `infbyte` convenience launcher;
- deployment conventions;
- application-specific defaults/branding when intentionally configured;
- skeleton documentation/developer experience.

Infbyte must not rebuild Foundation runtime machinery. Foundation must not depend on Infbyte.

# Completed

## 1. Branch/dependency setup

- created `feature/foundation-2.0` from Infbyte `main`;
- development Composer constraint is `dev-feature/foundation-2.0 as 2.0.x-dev`;
- final release constraint becomes `^2.0` only when Foundation 2.0 is released/frozen.

## 2. CLI ownership migration

- removed `FoundationConsole` construction and `Foundation::console()` usage;
- root `infbyte` delegates directly to Foundation `CommandDispatcher`;
- Foundation preflight accepts a lightweight display name so Infbyte reports `Infbyte <foundation-version>` without booting Application;
- Foundation package CLI remains `Foundation` by default;
- no second Console application/container/config hierarchy remains;
- no dedicated `bootstrap/cli.php` is retained because it has no runtime owner: `CommandDispatcher` selects CLI/Worker/Scheduler runtime directly.

## 3. Bootstrap/runtime/provider migration

- `bootstrap/console.php` removed;
- redundant `bootstrap/options.php` removed;
- `bootstrap/app.php` is one direct `Foundation::web(['base_path' => ...])` bootstrap;
- `public/index.php` remains minimal and delegates request handling to Foundation/Webrick;
- provider groups are exactly `common|web|cli|worker|scheduler`.

## 4. Application config migration

Checked-in core config stays deliberately small:

- `config/app.php`;
- `config/auth.php`;
- `config/router.php`.

Completed:

- removed `app.container.request_scope`;
- lazy loading defaults to true;
- kept compiled container activation explicit/opt-in;
- uses typed Foundation helpers (`env_string`, `env_bool`, `env_int`) where a typed value is required;
- `config/ids.php`, `AUTH_IDS`, and `auth.drivers.ids` removed;
- UID remains Foundation-core identity generation;
- auth/OTP/WebAuthn application defaults align with Foundation 2.0 schema;
- `.env.example` leaves `AUTH_TOKEN_SECRET` blank so `app:install` generates real secret material.

## 5. Route/schedule/worker surface

- `routes/api.php` uses the loader-provided scoped Webrick `Registrar` rather than the process-global Router facade;
- source route loading and route-cache compilation expose the same `$router` contract;
- `routes/schedule.php` uses `Infocyph\Foundation\Scheduling\Schedule`;
- worker example uses `App\Worker` and `Infocyph\Foundation\Worker\WorkerProvider` semantics;
- command example uses `App\Command`;
- stale empty `app/Console/Commands` skeleton removed;
- `routes/console.php` filename remains intentionally because it is the Foundation command-route contract, not a Console runtime hierarchy.

## 6. Module/config publication model

Decision fixed:

- do not bulk-copy Foundation module config into a clean Infbyte skeleton;
- built-in logging/resources/session work from Foundation defaults until explicitly published/customized;
- external CacheLayer/DBLayer/Epicrypt/OTP/Pathwise/ReqShield/TalkingBytes/Omnibus/WebAuthn config appears only when the corresponding capability is intentionally installed/configured;
- `module:install` remains the authoritative config publication path;
- published host config is never silently overwritten or deleted by module removal;
- optional package presence remains distinct from configured/activated capability.

Foundation defect discovered and fixed during this review:

- successful non-dry module install/remove now invalidates runtime-specific compiled containers and the optimize manifest so a changed package/provider graph cannot keep a stale prevalidated container active.

## 7. Deployment/generated artifacts

- `deploy.sh` no longer creates retired Console cache paths;
- deployment delegates aggregate artifact generation to `php infbyte optimize`;
- one root `bootstrap/cache/.gitignore` owns the generated artifact tree;
- redundant tracked `bootstrap/cache/config`, `bootstrap/cache/routes`, and retired `bootstrap/cache/console` placeholders were removed;
- generated Foundation artifacts are not committed.

## 8. Application branding decision

Foundation remains neutral. Infbyte does **not** publish/copy optional or built-in module config solely to rename framework defaults.

- application identity is expressed through `app`/auth config;
- cache/User-Agent/session/response branding is applied only when that configuration is actually published/owned by the application;
- this keeps a fresh skeleton core-only and prevents duplicated framework templates from drifting.

# Immediate next work — joint documentation reconciliation

Implementation/config architecture is now stable enough to document.

Review Foundation and Infbyte docs together:

1. rewrite Infbyte README around Foundation 2.0 rather than the retired Console architecture;
2. update Foundation README/docs with Infbyte as the application skeleton and Foundation as reusable runtime;
3. document exactly four runtime modes: Web, CLI, Worker, Scheduler;
4. document root Infbyte launcher vs package-owned Foundation dispatcher;
5. document `routes/console.php` as command registration, not a Console subsystem;
6. document application worker vs Omnibus message-worker ownership;
7. document module install/config publication and package-present vs activated semantics;
8. document the lean checked-in config policy and how built-in/optional config is published;
9. document deployment-owned `optimize` artifacts and cache invalidation;
10. remove stale `FoundationConsole`, `Foundation::console()`, `App\Console`, `request_scope`, IdentifierManager/IDs-driver, and old cache-path examples;
11. align install/create-project/.env secret-generation documentation;
12. freeze public names/config examples after docs match source.

If documentation exposes a concrete implementation defect, fix it in the owning repository and update both trackers.

# After docs — deferred test/release matrix

When explicitly started:

- Composer validation/dependency checks;
- PHPForge/static analysis;
- PHPUnit/integration suites;
- clean create-project/install flow;
- CLI version/list/help/completion preflight;
- Web/CLI/Worker/Scheduler load isolation;
- core-only runtime without optional packages;
- optional module install/remove/publication matrix;
- optimize/optimize:clear/deploy flow;
- persistent runtime reset/fork isolation;
- locking topology checks;
- startup/memory/throughput benchmarks;
- final stale-symbol/config/doc scan;
- final Foundation 2.0 + Infbyte release compatibility review.

# Do not regress

- no `FoundationConsole`, `Foundation::console()`, or Console runtime hierarchy;
- no `bootstrap/console.php` or unused runtime bootstrap symmetry files;
- no `auth.drivers.ids` / generic IdentifierManager config;
- no `app.container.request_scope`;
- no broad Foundation manager/facade proxies in Infbyte;
- no duplicated specialist-library engines;
- no copied optional-module config in the base skeleton;
- no generated optimized artifacts committed;
- no Infbyte workaround when the defect belongs to Foundation.
