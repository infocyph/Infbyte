# Infbyte 4.0 — Foundation 4 Integration and Consolidation Plan

> **Status:** **Infbyte 4 candidate implementation and application-owned QA completed** (13/13 checks). Only **published Foundation 4 resolution and Foundation-owned release certification** remain; no merge/tag/publish. Framework-private host internals and optional unselected services are not Infbyte application implementation work.  
> **Working branch:** `feature/infbyte-4` (single active implementation branch).  
> **Base:** Infbyte `main` at `1a968ad72a5ec785e7227459e722ea8982038700`.  
> **Foundation target:** [Foundation PR #20](https://github.com/infocyph/Foundation/pull/20), branch `feature/foundation-3`; inspected branch head `596825a563f2c3274c4167bd24fbe86a4531fbba` (2026-10-17). Candidate application source cited in release evidence: `3f295de1a2a5a6287d016de8afe0903a0907e675`.  
> **Date:** 2026-10-17. **Release status:** Foundation 4.0 is not yet tagged/release-ready; its sustained paired-performance and final acceptance gates remain open.  
> **Implementation cadence:** one complete batch → focused tests → full applicable QA → record source/CI evidence → update this tracker → next batch.  
> **Owner boundary:** modify **Infbyte only**; Foundation `feature/foundation-3` is read-only reference. No merges, tags, publication, or deletion of historical branches.

## 1. Objective and source of truth

Build **one** clean, Foundation-4-native Infbyte application skeleton rather than merge three obsolete Foundation-3 branch histories. Infbyte owns the application/distribution interface (bootstrap, app config, sample routes/controllers, root CLI forwarding, deployment, docs and application-level validation); Foundation 4 owns application composition, compiled releases, native integrations and policy; Webrick owns HTTP/routing, InterMix owns DI, Runwire owns host runtime and worker lifecycle, ArrayKit owns config primitives and CacheLayer owns cache/locks.

**Authoritative implementation order:** (1) the current Foundation 4 source and current documented public APIs (verify at time of each batch); (2) latest Infbyte `main`; (3) selectively applicable behavioral acceptance from the three old branches; (4) historic comments as research only. **No blind merge/cherry-pick** of Foundation-3 changes. Preserve the older branches unmodified for traceability. **No backwards compatibility:** do not ship Foundation 3 API adapters, retired cache readers, legacy CLI aliases, compatibility defaults, migration-only tests or obsolete infrastructure. Keep only independent security and data-integrity guarantees, using Foundation 4 native contracts.

The first commit was documentation-only. **Batch 1 has now started**: the working branch temporarily requires `dev-feature/foundation-3` from Foundation's VCS source, configures explicit Core-only `modules.features`, aligns application auth/module guidance, updates the README and relevant PHP tests, and keeps the original native web release bootstrap. This is an **unreleased development dependency**; the final skeleton must resolve stable `^4.0` through ordinary Composer without a VCS override. Hosted candidate Composer resolution, CLI/Core and PHPForge QA have passed on PHP 8.4/8.5 (see the exact-source run in the journal); independent real FPM/Runwire host acceptance and the published-stable release gate remain open.

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
- [x] Establish a read-only hosted application QA baseline on PHP 8.4/8.5 through full candidate CI; historical baseline recorded in the journal.

**Gate:** branch exists with a traceable plan; no runtime implementation or QA success inferred.

### Batch 1 — Foundation-4 contract inventory and application bootstrap

- [x] Record the exact Foundation candidate dependency source for each hosted development validation: `dev-feature/foundation-3` at `596825a` for the reviewed contract. Candidate Composer resolution is verified in CI; branch references remain intentionally floating and are rechecked per run.
- [x] Audit `infbyte` root launcher, `bootstrap/app.php`, `bootstrap/providers.php`, `public/index.php` against public Foundation 4 symbols and exact release handoff. **Evidence:** unchanged source symbols verified against Foundation `596825a`; hosted Foundation 4 candidate CLI/release PHP 8.4/8.5 [passed](https://github.com/infocyph/Infbyte/actions/runs/39015418129).
- [x] Audit sample Webrick routes, health/JSON response tests, per-runtime provider map and real FPM/Runwire host execution; no Infbyte-owned router or HTTP emitter added.
- [x] Keep six application-owned Core config files (including `config/modules.php`), migrate auth/module selection descriptions and passkey default, and remove obsolete 3.x module commands from the README/tests.
- [x] Validate native CLI and real nginx/PHP-FPM, direct SAPI and Runwire startup against an optimized trusted release on PHP 8.4/8.5 CI.

**Gate:** focused bootstrap/smoke tests, static/reference checks and public API contract parity recorded.

### Batch 2 — Core capabilities, three modules and eight features

- [x] Replace Foundation 3 module examples with three groups, eight feature IDs and explicit Core-only `modules.features`; keep native capability ownership in Foundation.
- [x] Verify exact three-group/eight-feature schema-v2 CLI catalog, qualified IDs and read/plan behavior with Pest, CLI and eight real selected-feature consumers.
- [x] Verify DBLayer dry-run/install/enable/repair/disable/remove in a fresh no-dev exported consumer, plus install/enable for every one of the eight optional features. No duplicate module engine.
- [x] Verify shared OTP/WebAuthn package ownership, conditional crypto/DBLayer prerequisites and retained package removal semantics via native Foundation pure planner integration tests.
- [x] Verify Core-only nonactivation, selected config publication and preserved DBLayer config after disable/remove; Foundation's native publisher is not overridden. Broader service-backed state remains in release QA.
- [x] Verify candidate `--no-dev` Core-only, DBLayer lifecycle and all eight selected-feature Composer installs/activations on published native optional packages. Final stable `^4.0` Composer proof remains Batch 6.

**Gate:** deterministic module contract fixtures + Composer/package/feature acceptance and targeted QA.

### Batch 3 — Native serve, host portability and execution isolation

- [x] Verify Runwire AUTO, explicit native/Swoole drivers, native protocol policy and rejected invalid host settings with hosted CLI tests and live HTTP.
- [x] Verify direct PHP-SAPI and actual nginx → PHP-FPM, Runwire native and Swoole hosts using the same application code. [13-job QA](https://github.com/infocyph/Infbyte/actions/runs/39020047443).
- [x] Verify Infbyte's actual native Webrick health/JSON routes, unknown-route 404/error recovery and compiled HTTP trust boundary across three real host types. Framework-private routing/middleware/streaming/upload semantics remain Webrick/Foundation owned and are not duplicated in Infbyte.
- [x] Run repeated and concurrent requests in long-lived Runwire and Swoole and nginx/FPM hosts; assert valid body and recovery after 404 responses. Cancellation, host-owned coroutine scope and worker drain are delegated to Foundation/Runwire's native implementation and upstream acceptance; no application-level substitute.
- [x] Verify the Infbyte skeleton retains no application-owned mutable host context, worker pool, request factory, reset registry or prefork DB state; use native Foundation and Webrick policy. Stateless concurrent health correctness is proven, while more elaborate auth/DB/lock cross-request internals remain upstream-owned.
- [x] Independently execute **native Runwire, real nginx/PHP-FPM and Swoole** in CI; none waived or inferred from a dry-run. Record measured short-burst latency and supervisor RSS explicitly as shared-runner diagnostics.

**Gate:** focused real hosted concurrency and request-correctness evidence; FPM + Runwire required, Swoole matrix when available.

### Batch 4 — Immutable releases, CLI/worker/scheduler and deployment

- [x] Verify real `optimize` generation, manifest SHA, web/CLI/worker/scheduler trusted loading, and current Webrick/InterMix compiler behavior by actual Foundation 4 candidate source.
- [x] Verify non-root `deploy.sh` preflight and production generation build; trust identity is returned separately for external supervisor injection.
- [x] Exercise missing/bad supervisor trust, tampered and stale manifest rejection, active generation replacement, intentional rollback, and retained data; keep reload/drain/cancel authority with the native Foundation/Runwire runtime.
- [x] Verify trusted compiled CLI/worker/scheduler sections, native system CLI and selected-feature package lifecycles. Optional worker/queue service behavior is native Foundation/Omnibus-owned and not active by default.
- [x] Validate clean archive and `create-project`, installed env/secrets, Core configuration, generation build/clear/replacement, retained SQLite record recovery, and exclusion of generated artifacts.

**Gate:** multi-runtime release acceptance with invalid-manifest negative tests and deployment smoke.

### Batch 5 — Distribution, documentation and retained-data safeguards

- [x] Replace README and `.env.example` descriptions with Foundation 4 architecture, hosting, qualified module IDs, release trust, safe rollback and retained-data recovery. No 3.x compatibility promises.
- [x] Exclude plans, host QA scripts, test infrastructure, build reports, generated caches, private environment material and local artifacts from real create-project; preserve minimal sample tests and application files.
- [x] Real PR-SHA no-dev `composer create-project` verified secure `.env` permission, token-secret generation, native ownership and trusted compiled release.
- [x] Document native Runwire, Swoole, direct PHP-FPM, external immutable trust inputs, worker drain, rollback and selected feature ownership.
- [x] Preserve uploaded files and sample session state through release changes; verify actual SQLite table contents through DBLayer enable/disable/remove and backup restore. Omnibus durable queue and customer-selected external services are not configured in the Core-only skeleton and remain native owner responsibilities.

**Gate:** clean `composer archive`/`create-project` round trip, accurate docs and recovery checks.

### Batch 6 — Full QA, performance and release-readiness (without merge/tag)

- [x] Run focused and complete PHPForge QA on PHP 8.4 and 8.5 using candidate source. **Published-stable and lowest-version matrices deliberately remain in the separate Foundation 4 release workflow**, blocked until actual Foundation 4 publication.
- [x] Run candidate Core and eight independently selected no-dev consumers, full DBLayer lifecycle and SQLite data recovery; PHPForge static/architecture and security checks included. Unselected external service topology is not required for Core-only Infbyte.
- [x] Run live nginx/FPM, native Runwire and Swoole host smoke and concurrent HTTP, 404 recovery, fail-closed digest mismatches, generation rotation/rollback and preserved persistent data on CI.
- [x] Record bounded host p50/p95/p99 and native Runwire supervisor RSS on GitHub-hosted CI for diagnostic evidence only; **no certified baseline throughput comparison claimed**. Controlled-host, matched-hardware sustained performance is an upstream Foundation release qualification, not an Infbyte-specific implementation requirement.
- [ ] Require the **published** Foundation 4.0 version for final release acceptance and stable production Composer resolution. Confirm upstream Foundation release gate independently.
- [x] Audit application changes against `main`: delete Foundation 2/3 migration tests, keep strict current feature IDs, avoid duplicate HTTP/DI/host/cache logic, and verify archive excludes internal QA files. Only the Foundation 4 candidate dependency/VCS entry is intentionally temporary.
- [x] Sync audited candidate acceptance with GitHub run, explicit untested upstream publish conditions and SHA. **Infbyte candidate accepted**, but **stable release qualification not accepted** before Foundation 4 publication.

**Gate:** all required checks demonstrably green; no automatic merge, tag or release.

## 6. Progress tracker

| Batch | Status | Implementation | QA | Evidence |
| --- | --- | --- | --- | --- |
| 0. Audit + plan | **Complete** | Unified branch/tracker and reviewed source refs | **PASS** hosted baseline recorded | [Candidate CI 39015418129](https://github.com/infocyph/Infbyte/actions/runs/39015418129) |
| 1. Bootstrap | **Candidate complete** | Native web/CLI/provider entrypoints, Core config, Foundation 4 dependency | **PASS** PHP 8.4/8.5 + real nginx/FPM + Runwire + full PHPForge | [Candidate CI 39018167417](https://github.com/infocyph/Infbyte/actions/runs/39018167417) |
| 2. Modules/features | **Candidate complete** | 3 modules/8 features, Core-only baseline, real Composer selected-feature lifecycle | **PASS** eight no-dev selected feature consumers + full DBLayer lifecycle | [Candidate CI 39017574029](https://github.com/infocyph/Infbyte/actions/runs/39017574029) |
| 3. Host/runtime | **Infbyte candidate complete** | Direct SAPI, nginx/FPM, Runwire and real Swoole; 240-response parallel/404 recovery probes | **PASS** exact response checks and p50/p95/p99 diagnostics, no untested host claimed | [CI 39020047443](https://github.com/infocyph/Infbyte/actions/runs/39020047443) |
| 4. Releases/deployment | **Infbyte candidate complete** | Multi-runtime trust, tamper/stale rejection, non-root deploy, rotation and explicit rollback | **PASS** generation trust and preserved upload/session state | [CI 39019457126](https://github.com/infocyph/Infbyte/actions/runs/39019457126) |
| 5. Distribution/docs | **Infbyte candidate complete** | Secure create-project, safe rollback docs, excluded QA scripts, SQLite retained-data backup/restore | **PASS** real no-dev consumer, full SQLite retention cycle, archive | [CI 39020047443](https://github.com/infocyph/Infbyte/actions/runs/39020047443) |
| 6. Final release qualification | **Publication-gated only** | Application candidate tested; strict stable-only workflow retained | **PASS** 13/13 candidate jobs; **BLOCKED** published Foundation `^4.0`, lowest/stable release jobs, upstream paired-performance sign-off | [CI 39020047443](https://github.com/infocyph/Infbyte/actions/runs/39020047443) |

**Tracker rules:** mark `[x]` only after implementation plus stated gate evidence; do not equate a prepared plan with delivered code; preserve failed/blocked tests and original results; link exact commit and CI run when complete; update the tracker in the *same commit* as each batch's implementation/evidence. When Foundation 4 upstream changes, append a dated dependency/contract checkpoint before modifying application code.

## 7. Open external blockers and owner control

- Foundation 4.0 PR #20 is **draft**: current full matched-host sustained performance has inconclusive cases and final upstream release approval remains open. Infbyte can plan and test a candidate but cannot claim final stable 4.0 readiness.
- Final public Composer `^4.0` resolution requires a published Foundation 4.0 tag. Do not ship with a development branch alias/VCS override; any isolated candidate check must be explicitly labeled non-release.
- Confirm exact current production bootstrap/deploy/CLI schema and manifest outputs from source when Batch 1/4 begins rather than guessing based on 3.x field names.
- Earlier main-based candidate QA was executed and acted on. The new Foundation 4 candidate matrix validates real Docker nginx/FPM, native Runwire, optional no-dev consumers and full PHPForge; the strictly stable release workflow is separately guarded until Foundation 4.0 publication.
- The user controls merging, release tagging and publication. Do not delete/merge the three historical branches.

## 8. Change journal

- **2026-10-17 — Coordinated release-gate hardening:** Reviewed the expanded workspace with Foundation as framework and Infbyte as application skeleton; sibling native libraries remain mechanism owners. The pending tag-publication wiring skipped stable/quality jobs and had no prerequisite graph; a regression failed before correction. Infbyte publication now waits for `published-core` and `published-database`, transitively requiring the stable-only manifest guard and full PHPForge suite on tag pushes as well. Foundation independently requires its quality, production, evidence and existing native-host gates. Candidate CI and the runtime-contract test no longer reject published 4.x solely because it is not `dev-feature/foundation-3`; they use native Composer installed-version metadata. Ten executions of the actual CI gate cover candidate/stable success and reject old/new majors, prereleases, aliased development, VCS overrides and unbounded requirements. The new workflow test is excluded from application archives. Focused acceptance passes 21 tests / 161 assertions and the detailed local suite passes 51 tests / 395 assertions. Both projects have current direct requirements and zero advisory findings; the abandoned development-only `doctrine/annotations` package is reported separately. Final immutable CI evidence is recorded in PR #11 after committing. Foundation's [reported non-SMT ARM comparison](https://github.com/infocyph/Foundation/actions/runs/39047995755) is a separate pending upstream gate, not Infbyte certification. The real `composer.json` intentionally retains its explicitly labeled candidate dependency until Foundation 4 is published; no stable version is fabricated or aliased.

- **2026-10-17 — Infbyte-only extended non-BC acceptance:** [CI 39020047443](https://github.com/infocyph/Infbyte/actions/runs/39020047443) **13/13 PASS** on the actual PR source before this tracker-only update. Additional coverage: explicit native Swoole extension and hosted HTTP (no simulated driver), 240 concurrent validated responses each on actual FPM and Runwire, 404 recovery, diagnostic p50/p95/p99 and RSS collection with no certification claims, SQLite record retained through full DBLayer feature lifecycle and backup restore, and release manifest rollback plus trusted CLI/worker/scheduler. `tests/ci/http-host-smoke.sh` remains excluded from application archive. Removed Foundation 2/3 generated-artifact compatibility fixture entirely; there is **no BC commitment**. Remaining issues are genuinely upstream publication/stable-resolution and certified Foundation-controlled-host release evidence, not unfinished Infbyte runtime implementation.

- **2026-10-17 — Final checkout/CI verification:** Infbyte candidate source [`a066669`](https://github.com/infocyph/Infbyte/commit/a066669664491202d2d3289f954018f28896cb4c) passed [Foundation 4 Candidate Integration run 39018559438](https://github.com/infocyph/Infbyte/actions/runs/39018559438): **12/12 succeeded**, 0 failed, 0 pending. This is the latest code+workflow validation before this tracker-only commit. Foundation reference remains `feature/foundation-3` at inspected `596825a`; no Foundation changes, merge or tag. Stable 4.0 publication and associated release-only gates remain blocked/unverified.

- **2026-10-17 — Final candidate QA checkpoint:** [run 39018299059](https://github.com/infocyph/Infbyte/actions/runs/39018299059) has **12/12 successful jobs**: full candidate PHPForge for PHP 8.4/8.5, real FPM/Runwire host integration, DBLayer package lifecycle, all eight optional feature no-dev installs and activation, and actual `composer create-project` for the PR SHA. No jobs skipped/ignored to obtain candidate green. Stable `^4.0` release constraints, lowest/stable production matrices, service-backed durable data restore and controlled-host sustained performance remain **untested external/release gates**.

- **2026-10-17 — Batches 2–5 expanded and QA verified:** `Foundation4ModuleIntegrationTest` covers all eight selections and conditional prerequisites; a clean DBLayer no-dev lifecycle and all eight independently selected published native package consumers passed in [run 39017574029](https://github.com/infocyph/Infbyte/actions/runs/39017574029). Real nginx/PHP-FPM with group-restricted release identity, direct SAPI, native Runwire and parallel valid HTTP passed in [run 39018167417](https://github.com/infocyph/Infbyte/actions/runs/39018167417). Release tamper rejection, replacement and retained upload tests plus non-root deployment wrapper passed in [run 39018216025](https://github.com/infocyph/Infbyte/actions/runs/39018216025). All changes are Infbyte-only. A real Composer create-project no-dev consumer at the exact PR SHA then passed in [run 39018299059](https://github.com/infocyph/Infbyte/actions/runs/39018299059), including secure generated secrets, Core-only feature selection and trusted release compilation. Optional retained-data recovery remains open. Unexercised Swoole/deferred response cancellation, specialist restore, production-equivalent soak/performance and the unpublished Foundation 4 tag remain explicit blockers to final release sign-off.

- **2026-10-17 — Batch 2 checkpoint:** added `tests/Feature/Foundation4ModuleIntegrationTest.php` to assert exact 3/8 topology, group isolation, Core-only defaults, package/config ownership, cross-feature dependencies, shared OTP retention, fail-closed unknown Composer roots and explicit planner behavior. Both PHP 8.4/8.5 full candidate CI jobs [passed at `c28d6a40`](https://github.com/infocyph/Infbyte/actions/runs/39017103244). Package mutation and selected-feature no-dev consumers remain **unproven** and therefore unchecked; proceeding to independent host QA does not mark Batch 2 complete.

- **2026-10-17 — Exact-source candidate QA VERIFIED:** Infbyte source [`ba318fc5`](https://github.com/infocyph/Infbyte/commit/ba318fc5f404e742be4b9a9c3fbb600ef45992ef) integrated published native dependencies through candidate Foundation `dev-feature/foundation-3` at [`596825a`](https://github.com/infocyph/Foundation/commit/596825a563f2c3274c4167bd24fbe86a4531fbba), **with no Foundation repository mutation**. [Dedicated candidate workflow 39015418129](https://github.com/infocyph/Infbyte/actions/runs/39015418129) **SUCCESS in both PHP 8.4 and 8.5**: Composer resolution, exact schema-v2 three-module/eight-feature CLI inventory, `app:install`, production config validation, trusted `optimize` compilation + manifest identity, `app:ready`, and complete `composer ic:ci` with Pest, PHPStan, Psalm, PHPCS, Pint, Rector, Deptrac, reference/syntax/duplicate/comment checks and Composer normalization. Earlier failures in assertions and packaging were fixed without weakening checks. This proves the tested candidate, **not** real FPM/Runwire host parity or a tagged 4.0 release.
- **2026-10-17 — Legacy published-release gate intentionally OPEN:** [Security & Standards 39015418322](https://github.com/infocyph/Infbyte/actions/runs/39015418322) fails its stable runtime constraint guard on `dev-feature/foundation-3` (as intended for a publishable consumer). Existing Foundation 3 released consumer/module/migration workflows need native Foundation 4 rewrites. Never report this as passing or suppress the guard. Final sign-off also depends on Foundation 4 publication and its own upstream performance gate.

- **2026-10-17 — Batch 1 initial Infbyte-only implementation:** `composer.json` now resolves development Foundation `dev-feature/foundation-3` from the exact Foundation GitHub repository (temporary VCS integration mode); `config/modules.php` initializes an explicit empty feature map; Core auth and `.env.example` align with the current Foundation 4 defaults; `README.md` now documents Runwire Core, 3 optional modules / 8 feature IDs, native direct FPM and trusted release handoff; `.gitattributes` excludes plan sources and the renamed repository QA test from `composer archive`; updated CLI/module/distribution test expectations and replaced `Foundation3RuntimeLifecycleTest` with `Foundation4RuntimeLifecycleTest`. Existing `public/index.php`, `infbyte`, `bootstrap/app.php` and `bootstrap/providers.php` match the inspected Foundation 4 public contract and were intentionally not rewritten.
- **Release policy updated:** `.github/workflows/security-standards.yml` now runs on main/tag/manual release boundaries, rejects non-stable constraints and VCS overrides, and preserves full PHPForge PHP 8.4/8.5 lowest/stable plus clean Core/DBLayer consumers. The old Foundation 3 migration/obsolete-module workflows are removed from the new 4.0 release contract, with their relevant security checks replaced by current Foundation 4 native tests. **Do not count this stable gate as passed** until a published Foundation `^4.0` is actually resolved.
- **Source reference:** Foundation `feature/foundation-3` HEAD `596825a563f2c3274c4167bd24fbe86a4531fbba` as inspected; current Foundation 4 source remains externally owned and unmodified. Development branch dependency **floats** with upstream changes until dependency source is frozen in a tested Composer lock/evidence. Final release cannot ship the VCS repository entry or a `dev-` constraint.

- **2026-10-17 — Initial consolidation:** inspected all three Infbyte branches against current `main`, reviewed Foundation 4 Core/three-module/eight-feature direction and pending upstream release gates, selected one `main`-based branch and authored the plan. No runtime source edits; no QA claims.
