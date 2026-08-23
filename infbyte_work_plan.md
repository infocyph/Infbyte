# Infbyte — Foundation 2.0 Live Work Plan

> Current execution tracker for migrating the Infbyte application skeleton to Foundation 2.0.

## Working branches

- Infbyte: `feature/foundation-2.0`
- Foundation: `feature/foundation-2.0`

The two branches are developed together. Foundation remains the reusable runtime/framework layer; Infbyte remains the opinionated application skeleton.

## Maintenance rule

After each completed joint batch:

1. record the latest Infbyte and Foundation source checkpoints;
2. move finished work to **Completed**;
3. keep **Immediate next work** limited to the next concrete cross-repo phase;
4. do not reintroduce Foundation 1.x/Console compatibility;
5. keep specialist-library engines in their owning packages;
6. keep the full PHPUnit/static-analysis/PHPForge/release matrix deferred until implementation/config/docs are stable.

## Current checkpoint

- Date: 2026-08-23
- Infbyte source checkpoint: `456482094f98d82b5f89c37ebac57a11477d2d5e`
- Foundation source checkpoint: `7601aa0803e997ce4e960ce367cd0530b9b10dc3`
- Infbyte base branch used to start this work: `main` at `47fb985f266c977504c3dca6bd13e85c9a1b73dc`
- Status: initial Foundation 2.0 migration baseline complete; joint reconciliation continues.
- Full tests/release gates: not run yet.

# Fixed ownership boundary

## Foundation owns

- Application/runtime composition;
- Web, CLI, Worker, Scheduler runtime modes;
- InterMix container/scopes;
- command dispatcher and CLI machinery;
- config loader/cache and optimized runtime artifacts;
- reusable module integration policy;
- reusable framework defaults;
- specialist-package bridges.

## Infbyte owns

- application/project bootstrap files;
- application-facing config overrides;
- project provider lists;
- routes and application code;
- root `infbyte` convenience launcher;
- deployment conventions;
- application branding/default namespace choices;
- final developer-facing skeleton documentation.

Infbyte must not rebuild Foundation runtime machinery. Foundation must not depend on Infbyte.

# Completed

## 1. Branch and dependency setup

- created `feature/foundation-2.0` from Infbyte `main`;
- Infbyte now targets `dev-feature/foundation-2.0 as 2.0.x-dev` while both repositories are under active development;
- final release constraint will become `^2.0` after Foundation 2.0 is released/frozen.

## 2. CLI ownership migration

- removed the old Infbyte `FoundationConsole` construction path;
- root `infbyte` now delegates directly to Foundation `CommandDispatcher`;
- Foundation dispatcher gained a small preflight `displayName` input so Infbyte can report `Infbyte <foundation-version>` without booting the application;
- Foundation package CLI remains branded `Foundation` by default;
- no second Console application/container/config hierarchy is retained.

## 3. Runtime bootstrap/provider migration

- added `bootstrap/cli.php` using `Foundation::cli()`;
- removed `bootstrap/console.php` and `Foundation::console()` usage;
- provider groups now use exactly:
  - `common`;
  - `web`;
  - `cli`;
  - `worker`;
  - `scheduler`.

## 4. Core application config migration

- removed `app.container.request_scope`;
- application container lazy loading now defaults to true;
- retained explicit opt-in compiled-container activation;
- environment helpers use Foundation 2.0 `env*` contract;
- app-facing name/environment/debug/url remain Infbyte-owned overrides.

## 5. Auth/UID migration

- removed `config/ids.php`;
- removed `AUTH_IDS` and the retired `auth.drivers.ids` selector;
- UID remains Foundation-core identity generation;
- auth driver config now covers only replaceable capabilities;
- OTP defaults were aligned with current Foundation 2.0 auth policy;
- auth example secret is blank so `app:install` generates real secret material instead of preserving a committed placeholder.

## 6. Deployment/cache artifact hygiene

- removed retired `bootstrap/cache/console` creation from deployment flow;
- `deploy.sh` delegates optimization to `php infbyte optimize`;
- all generated Foundation artifacts under `bootstrap/cache` are ignored;
- `bootstrap/cache/.gitignore` keeps the application cache directory present without tracking generated artifacts.

# Immediate next work — cross-repo surface reconciliation

## 1. Application entrypoints and route surface

Audit and align:

- `public/index.php`;
- `bootstrap/app.php`;
- `bootstrap/cli.php`;
- any worker/scheduler entry needs;
- `routes/web.php`, `routes/api.php`, `routes/console.php`;
- application command examples/namespaces.

Do not add worker/scheduler bootstrap files merely for symmetry if Foundation commands already own those runtime transitions.

## 2. Config and module publication

Reconcile Infbyte's checked-in config with Foundation 2.0's module catalog and published templates:

- determine which config belongs in a fresh skeleton by default;
- keep optional module config absent until the module is intentionally installed/published unless Infbyte deliberately chooses otherwise;
- ensure `module:install` publication is the authoritative path for CacheLayer, DBLayer, Epicrypt, OTP, Pathwise, ReqShield, TalkingBytes, Omnibus, etc.;
- remove stale copied config instead of maintaining divergent application versions of Foundation templates;
- verify package-present versus configured/activated semantics.

## 3. Infbyte application branding

Foundation defaults are neutral. Decide Infbyte's application defaults deliberately and only at the application layer, including where appropriate:

- HTTP User-Agent;
- cache namespace;
- lock namespace;
- session cookie;
- remember-me cookie;
- application response metadata.

Keep optional-module branding with the corresponding module config rather than forcing optional packages into a core-only install.

## 4. Foundation support discovered by Infbyte

Any cross-repo integration defect must be fixed in Foundation rather than worked around in Infbyte. Current example already completed:

- CLI preflight display-name support.

Continue this rule for module publication, runtime bootstrap, optimized artifacts, and app install behavior.

## 5. Composer/install lifecycle

Audit:

- `post-create-project-cmd`;
- clean create-project flow;
- `.env` creation and secret generation;
- writable runtime directories;
- optional module installation commands;
- final change from Foundation development branch constraint to `^2.0` at release.

# Later phases

## Documentation freeze

After implementation/config surfaces settle:

- rewrite Infbyte README for the Foundation 2.0 architecture;
- document Infbyte vs Foundation ownership;
- update install/CLI/config/module/deployment examples;
- remove Foundation 1.x and Console-era examples;
- update Foundation docs in parallel where application-facing behavior is shared.

## Deferred test/release matrix

When explicitly started:

- Composer validation;
- PHPForge/static analysis;
- PHPUnit/integration tests;
- clean `composer create-project`/install flow;
- `php infbyte --version`, list/help/completion preflight;
- Web boot;
- CLI command boot;
- Worker/Scheduler command runtime handoff;
- core-only install without optional packages;
- optional module install/remove/config publication matrix;
- optimize/optimize:clear/deploy flow;
- persistent runtime reset/isolation;
- final stale-symbol/config/doc scan;
- final Foundation 2.0 + Infbyte release compatibility review.

# Do not regress

- no `FoundationConsole` or `Foundation::console()`;
- no `bootstrap/console.php`;
- no `auth.drivers.ids` / generic IdentifierManager config;
- no `app.container.request_scope`;
- no second CLI container/config/bootstrap hierarchy;
- no broad Foundation manager/facade proxies in Infbyte;
- no duplicated specialist-library engines;
- no generated optimized artifacts committed to the skeleton;
- no workaround in Infbyte when the defect belongs to Foundation.
