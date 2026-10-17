# Infbyte 4.0 — Foundation 4 Integration and Consolidation Plan

> **Status:** Planning baseline; implementation not started.  
> **Working branch:** `feature/infbyte-4` (single active implementation branch).  
> **Base:** Infbyte `main` at `1a968ad72a5ec785e7227459e722ea8982038700`.  
> **Foundation target:** [Foundation PR #20](https://github.com/infocyph/Foundation/pull/20), branch `feature/foundation-3`; inspected branch head `596825a563f2c3274c4167bd24fbe86a4531fbba` (2026-10-17). Candidate application source cited in release evidence: `3f295de1a2a5a6287d016de8afe0903a0907e675`.  
> **Date:** 2026-10-17. **Release status:** Foundation 4.0 is not yet tagged/release-ready; its sustained paired-performance and final acceptance gates remain open.  
> **Implementation cadence:** one complete batch → focused tests → full applicable QA → record source/CI evidence → update this tracker → next batch.  
> **Owner boundary:** no merging, tagging, publishing, deleting old branches, or modifying Foundation itself in this plan-only phase.

## 1. Objective and source of truth

Build **one** clean, Foundation-4-native Infbyte application skeleton rather than merge three obsolete Foundation-3 branch histories. Infbyte owns the application/distribution interface (bootstrap, app config, sample routes/controllers, root CLI forwarding, deployment, docs and application-level validation); Foundation 4 owns application composition, compiled releases, native integrations and policy; Webrick owns HTTP/routing, InterMix owns DI, Runwire owns host runtime and worker lifecycle, ArrayKit owns config primitives and CacheLayer owns cache/locks.

**Authoritative implementation order:** (1) the current Foundation 4 source and current documented public APIs (verify at time of each batch); (2) latest Infbyte `main`; (3) selectively applicable behavioral acceptance from the three old branches; (4) historic comments as research only. **No blind merge/cherry-pick** of Foundation-3 changes. Preserve the older branches unmodified for traceability. There are no backwards-compatibility obligations for the old major inside this new 4.0 skeleton, but do not delete still-valid security guarantees or useful app behavior.

This initial commit is **documentation only**. Keep existing Infbyte `composer.json` (^3.0.1) and runnable application code unchanged until a deliberately verified Foundation-4 candidate integration is implemented. Do not claim Foundation 4 QA from the plan commit.

## 2. Verified branch inventory and disposition

All four Infbyte refs have diverged from the common history; `main` is the authoritative current distribution and must not be replaced by an older branch snapshot.

| Source | Inspected head | Unique material vs `main` | Decision |
| --- | --- | --- | --- |
| `main` | `1a968ad72a5ec785e7227459e722ea8982038700` | Published Foundation 3.0.1 integration; native release boot and deployment; current QA; core `config/cache.php` and `config/notifications.php` | **New branch base**; maintain valid distribution/security tests |
| `infbyte-3/foundation-3-handoff` | `0319aad9a9d1e3be09ae3c69cf7301a8f5730524` | Two commits beyond its merge base; original handoff workflow, early trusted entrypoint/deploy, capability config, test changes; predates current main | **Reference only**; no handoff workflow duplication or old development dependency pin |
| `infbyte-foundation-3/runtime-integration-plan` | `221727098020b86c97c72b0031b95790a678b7f1` | A 503-line historical persistent-runtime acceptance plan; one planning commit beyond its merge base | **Carry forward applicable runtime/isolation acceptance**; rewrite old Runwire 1.0 / Webrick 5 assumptions for Foundation 4 |
| `foundation-3-runtime-ci` | `d5eadc8dacd219ab39a4dc9051ca7addeb98e9eb` | 27 commits beyond its merge base, with Foundation-3 runtime tests, distribution/deployment improvements and artifact layout; main subsequently diverged via consolidated work | **Regression reference**; do not reset main to this branch or restore removed cache paths |

Source links:
- [Foundation-3 handoff branch](https://github.com/infocyph/Infbyte/tree/infbyte-3/foundation-3-handoff), [PR #10](https://github.com/infocyph/Infbyte/pull/10).
- [Persistent runtime plan branch](https://github.com/infocyph/Infbyte/tree/infbyte-foundation-3/runtime-integration-plan), [historical plan](https://github.com/infocyph/Infbyte/blob/infbyte-foundation-3/runtime-integration-plan/docs/plans/infbyte-foundation-3-persistent-runtime-integration-plan.md).
- [Foundation-3 runtime CI branch](https://github.com/infocyph/Infbyte/tree/foundation-3-runtime-ci).
- [Current Infbyte main](https://github.com/infocyph/Infbyte/tree/main), [Foundation 3.0.1 PR #9](https://github.com/infocyph/Infbyte/pull/9).

### Preservation matrix

| Existing behavior or file family | 4.0 action |
| --- | --- |
| `infbyte` forwarding to Foundation `CommandDispatcher` | Validate current constructor/API and preserve single CLI ownership |
| `bootstrap/app.php` and `bootstrap/providers.php` | Keep only current Foundation public entrypoints and per-runtime provider map; adapt actual provider contracts |
| `public/index.php` | Verify the current trusted Foundation-4 direct-host factory/entrypoint; support normal PHP/FPM **without** launching `serve` |
| `deploy.sh` and `storage/releases` | Preserve non-root/writability protections and fail-closed trusted immutable releases; adapt current release metadata and host handoff |
| `config/app.php`, `auth.php`, `router.php`, `cache.php`, `notifications.php` | Retain actual Core-owned configuration, align keys with current Foundation 4 templates, eliminate obsolete capability/module names |
| `.env.example` and `README.md` | Replace Foundation-3 guidance and local-server assumptions; expose a minimal, accurate 4.0 application surface |
| `.github/workflows/security-standards.yml` and `tests/Feature/*` | Retain useful current distribution and lifecycle checks; add 4.0-specific real Composer, module, host, security and release tests |
| Historical Foundation-3 handoff workflow and removed container/route caches | Do **not** reinstate them |
| Persistent-runtime plan's leakage, cleanup, host, assets, reload/rollback and performance scenarios | Requalify under **Runwire 2.1.2 + Webrick 6 + InterMix 11**, without duplicating native mechanisms |

## 3. Current Foundation 4 contracts (candidate, not published release)

**Core is prebuilt and always installed:** ArrayKit ^5.3.1, CacheLayer ^4.0.1, InterMix ^11.2, Runwire ^2.1.2, UID ^6.0, Webrick ^6.0.3 and PSR Log ^3.0.2, through Foundation's dependency graph. PHP 8.4+, Composer 2. PHPForge is development QA, not a production module. Never reimplement their native router, container, host, config, cache, locks or cancellation machinery.

**Exactly three public optional modules; eight independently selected features:**

| Group | Feature | Native package (current candidate floor) | Feature-owned configuration |
| --- | --- | --- | --- |
| `data` | `data.database` | DBLayer ^6.1.1 | `database.php` |
| `data` | `data.filesystem` | Pathwise ^4.2 | `filesystem.php` |
| `security` | `security.crypto` | Epicrypt ^3.2 | `security.php` |
| `security` | `security.validation` | ReqShield ^3.3 | `validation.php` |
| `security` | `security.mfa` | OTP ^7.0 | none |
| `security` | `security.passkey` | OTP ^7.0 + web-auth/webauthn-lib ^5.3.9 | none |
| `execution` | `execution.messaging` | Omnibus ^3.0.1 | `messaging.php` |
| `execution` | `execution.communication` | TalkingBytes ^2.3 | `communication.php` |

`security.mfa` using the OTP driver explicitly requires `security.crypto`; passkeys do not implicitly enable MFA. Durable messaging explicitly requires `data.database`. Feature install != feature enable; unselected Composer packages remain inactive. `auth`, `cache`, `logging`, `operations`, `resources`, `session`, `notifications` and `runwire` are **Core concerns**, not old installable aliases. No `execution.runtime` feature.

Feature plans and CLI JSON use `schema_version: 2`; group inspection is allowed, group-wide implicit installation is not. Application-selected `modules.features` and explicit `app.capabilities` must match the actual 4.0 contract. Old `APP_CAPABILITIES=database,cache,messaging` documentation is **not automatically valid** under the new module vocabulary; determine canonical values from source and tests before editing.

Source of truth: [Foundation modules](https://github.com/infocyph/Foundation/blob/feature/foundation-3/docs/modules.md), [configuration](https://github.com/infocyph/Foundation/blob/feature/foundation-3/docs/configuration.md), [Foundation-4 setup contract](https://github.com/infocyph/Foundation/blob/feature/foundation-3/docs/foundation-4-migration.md), [public API map](https://github.com/infocyph/Foundation/blob/feature/foundation-3/docs/foundation-4-api-map.md), and [Foundation-4 release ledger](https://github.com/infocyph/Foundation/blob/feature/foundation-3/docs/plans/foundation-4.0-release-audit-and-plan.md).

## 4. Architectural decisions

1. **One working branch, no source-history combination:** use this branch for all changes. If a historical implementation is still needed, reapply the *behavior* cleanly with tests and cite its original ref; do not recreate redundant release/CI infrastructure.
2. **A real new major:** remove old 3.x-only application configuration/commands/docs rather than layering shims. Keep application schema minimal; avoid shipping every specialist config in a fresh core-only project.
3. **Native Runwire first:** `php infbyte serve` delegates through Foundation's Runwire-backed native selector; default runtime AUTO, enum-based overrides (e.g. Swoole), protocol policy distinct. Ordinary web-server PHP/FPM/index.php and supported external native hosts remain first-class. No duplicate PHP built-in server, listener, event loop, worker manager or context factory.
4. **One trusted compilation boundary:** native Webrick format-3 routing and InterMix ABI-3 DI artifacts, full web/CLI/worker/scheduler release identity, correct build-time graph and fail-closed manifest verification; no source runtime fallback when release verification fails.
5. **Preserve state integrity:** isolate request/job/user/session/DB/cache state by the correct upstream lifecycle, forward exact host-provided Runwire contexts and cancellation, avoid Octane-style wholesale cloning/reset registries. Verify releases, rollback, streaming, disconnect and worker recycling.
6. **Explicit opt-in:** keep initial skeleton Core-only, validate qualified features and conditional dependency checks with production `--no-dev` Composer consumers; no default auto-enable of all modules.
7. **Distribution is a product contract:** `composer create-project` installs a usable project with secure generated secrets; archived skeleton excludes QA internals/plans/artifacts, retains expected user-facing configuration and sample tests.
8. **Do not release against an untagged framework:** candidate integration can be verified in isolated CI/development against an exact Foundation Git SHA; final `composer.json` must target published stable `^4.0` with normal Composer resolution, no shipped VCS/path override, alias or transient SHA pin. Record actual source and lock provenance for every qualification.
9. **No QA gaming:** use PHPForge engineering policy as configured; no weaker thresholds, hidden skips, unverified green claims or suppressions to make acceptance appear complete.

## 5. Work batches and gates

### Batch 0 — Three-branch audit and unified planning (this commit)

- [x] Resolve `main` and all three branch heads; compare histories and changed paths.
- [x] Inspect the persistent-runtime plan, handoff and CI artifacts.
- [x] Inspect Foundation 4 current Composer Core graph, module catalog, CLI schema, serving, entrypoints and release contract.
- [x] Choose a non-destructive `main`-based single implementation branch.
- [x] Commit this consolidated plan/tracker.
- [ ] Record an initial read-only application QA baseline from the active branch (requires runnable Composer environment).

**Gate:** branch exists with a traceable plan; no runtime implementation or QA success inferred.

### Batch 1 — Foundation-4 contract inventory and application bootstrap

- [ ] Pin the exact current Foundation candidate source for *isolated development validation*, record SHA, branch, dependency graph and available host drivers; recheck before implementation.
- [ ] Audit `infbyte` root launcher, `bootstrap/app.php`, `bootstrap/providers.php`, `public/index.php` against public Foundation 4 symbols and exact release handoff.
- [ ] Audit existing app routes, controllers, example tests and explicit runtime selection. Verify Webrick 6 request/response semantics without adding adapter layers.
- [ ] Define minimal Core-only `config/*.php`/`.env.example` and application-owned configuration; remove 3.x-only keys and old module assumptions.
- [ ] Implement only necessary application changes; validate direct PHP/FPM startup and CLI dispatch.

**Gate:** focused bootstrap/smoke tests, static/reference checks and public API contract parity recorded.

### Batch 2 — Core capabilities, three modules and eight features

- [ ] Replace obsolete capabilities/module examples with canonical qualified `modules.features` selection and real Foundation 4 capability mapping.
- [ ] Verify all 3 group names and 8 feature IDs in CLI list/show/doctor/plan, including schema-v2 JSON and stable ordering.
- [ ] Exercise install/dry-run/enable/disable/repair/remove of *explicit* selected features through Foundation CLI, without an Infbyte-managed package/module engine.
- [ ] Verify shared OTP/WebAuthn package ownership and conditional Epicrypt, durable Omnibus/DBLayer and validation prerequisites.
- [ ] Verify non-overwriting config publishing, required Core configuration, schema ownership, and no unselected feature auto-activation.
- [ ] Verify production `--no-dev` Core-only, database-only and selected-feature Consumer dependency graphs; no unshipped dev pins.

**Gate:** deterministic module contract fixtures + Composer/package/feature acceptance and targeted QA.

### Batch 3 — Native serve, host portability and execution isolation

- [ ] Verify actual `php infbyte serve` native Runwire AUTO behavior, explicit enum-selected drivers and protocol policy; refuse unsupported runtime/host options.
- [ ] Verify direct PHP/FPM `public/index.php` without `serve`; exercise supported external Swoole/OpenSwoole path using the same application code.
- [ ] Test Webrick native router strategies, middleware, authorization/session input isolation, HTTP errors, streaming, uploads and trusted static assets.
- [ ] Repeat requests in long-lived workers, concurrent requests, exact native request/task/coroutine context forwarding, cancellation, exception recovery, child scopes and worker shutdown/drain.
- [ ] Verify no cross-request auth/session/input/DB/lock/log context bleed, no inherited prefork connection, and no Infbyte-owned host/context/reset implementation.
- [ ] Distinguish tested native host environments from unavailable optional environments; never convert a skipped driver into a pass.

**Gate:** focused real hosted concurrency and request-correctness evidence; FPM + Runwire required, Swoole matrix when available.

### Batch 4 — Immutable releases, CLI/worker/scheduler and deployment

- [ ] Validate `optimize` generation and trust handoff for web/CLI/worker/scheduler; verify immutable config, Webrick, InterMix and provider topology.
- [ ] Reconcile `deploy.sh` with Foundation 4 output/manifest interface, retaining non-root policy, permission checks and trusted identity outside writable release storage.
- [ ] Exercise corrupted/stale/tampered/missing generation rejection; no silent source fallback; correct activation, reload, rollback and orderly worker drain.
- [ ] Verify CLI, scheduler, worker commands and optional resource/message dependencies across optimized and unoptimized contexts as supported.
- [ ] Test cold/empty environment and clean install paths, idempotence, failure recovery and no generated artifacts in distributable packages.

**Gate:** multi-runtime release acceptance with invalid-manifest negative tests and deployment smoke.

### Batch 5 — Distribution, documentation and retained-data safeguards

- [ ] Update README, `.env.example`, deployment instructions, configuration/module docs and Foundation 4 release notes to reflect current source (not historical Foundation 3).
- [ ] Update `.gitattributes` and distribution tests so this planning file, QA fixtures, build reports, generated caches, private env files and local artifacts **do not** enter `composer create-project`; preserve application files and minimal sample tests.
- [ ] Verify generated application secrets and safe permissions, immutable release deployment guidance and real package ownership on create-project.
- [ ] Document native Runwire versus direct FPM hosting, selected module dependencies, persistent-worker safe practices and troubleshooting.
- [ ] Rehearse state preservation/recovery for enabled auth/session/token and optional DB/queue/cache state with their native owners; document unselected/untested services explicitly.

**Gate:** clean `composer archive`/`create-project` round trip, accurate docs and recovery checks.

### Batch 6 — Full QA, performance and release-readiness (without merge/tag)

- [ ] Re-run focused tests and all configured PHPForge/Composer QA; validate PHP 8.4 and 8.5, lowest and stable dependency graphs.
- [ ] Run clean production `--no-dev` consumers, security/advisory and static/architecture checks, and relevant service-backed DB/cache/message QA.
- [ ] Run verified native FPM/Runwire host smoke, concurrency/leak/soak, release rollover/rollback and fault-injection scenarios.
- [ ] Record reproducible performance: serving/boot overhead, successful throughput, p50/p95/p99, worker RSS/FD and request-isolation costs against a comparable frozen baseline. No claims from unmatched or noisy runs.
- [ ] Require the **published** Foundation 4.0 version for final release acceptance and stable production Composer resolution. Confirm upstream Foundation release gate independently.
- [ ] Audit diff against `main` for obsolete 3.x code, duplicate mechanisms, abandoned artifacts, wrong docs and distribution leakage; track resolved exceptions.
- [ ] Sync every checked item with *actual* command/output/CI links, SHA and remaining blockers; only then designate Infbyte release-ready.

**Gate:** all required checks demonstrably green; no automatic merge, tag or release.

## 6. Progress tracker

| Batch | Status | Implementation | QA | Evidence |
| --- | --- | --- | --- | --- |
| 0. Audit + plan | **Planning complete** | Plan-only, no runtime code | Not run | Source refs and plan commit; baseline QA outstanding |
| 1. Bootstrap | Not started | Pending | Pending | — |
| 2. Modules/features | Not started | Pending | Pending | — |
| 3. Host/runtime | Not started | Pending | Pending | — |
| 4. Releases/deployment | Not started | Pending | Pending | — |
| 5. Distribution/docs | Not started | Pending | Pending | — |
| 6. Release QA | Not started | Pending | Pending | — |

**Tracker rules:** mark `[x]` only after implementation plus stated gate evidence; do not equate a prepared plan with delivered code; preserve failed/blocked tests and original results; link exact commit and CI run when complete; update the tracker in the *same commit* as each batch's implementation/evidence. When Foundation 4 upstream changes, append a dated dependency/contract checkpoint before modifying application code.

## 7. Open external blockers and owner control

- Foundation 4.0 PR #20 is **draft**: current full matched-host sustained performance has inconclusive cases and final upstream release approval remains open. Infbyte can plan and test a candidate but cannot claim final stable 4.0 readiness.
- Final public Composer `^4.0` resolution requires a published Foundation 4.0 tag. Do not ship with a development branch alias/VCS override; any isolated candidate check must be explicitly labeled non-release.
- Confirm exact current production bootstrap/deploy/CLI schema and manifest outputs from source when Batch 1/4 begins rather than guessing based on 3.x field names.
- Main's existing QA must first be run and reported honestly. This plan-only change does not execute Composer, host or Docker acceptance.
- The user controls merging, release tagging and publication. Do not delete/merge the three historical branches.

## 8. Change journal

- **2026-10-17 — Initial consolidation:** inspected all three Infbyte branches against current `main`, reviewed Foundation 4 Core/three-module/eight-feature direction and pending upstream release gates, selected one `main`-based branch and authored the plan. No runtime source edits; no QA claims.
