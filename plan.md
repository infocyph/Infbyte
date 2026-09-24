# InfByte — Foundation 3 Post-Release Handoff Plan

**Target:** InfByte release compatible with the published Foundation 3.0 package.  
**Branch:** `foundation-3/runtime-lifecycle`.  
**PR:** #9.  
**Status:** Foundation 3.0 is released; stable-package cutover is complete and post-release consumer/module/runtime certification is in progress.  
**Updated:** 2026-09-24.

This is the canonical continuation plan for the InfByte skeleton after Foundation 3 is released. It intentionally preserves the consumer-side work that was previously recorded in Foundation planning files so those Foundation plan files can be removed without losing the handoff.

The rule for this work is simple: **do not reopen Foundation 3 runtime/module ownership to make the skeleton easier.** InfByte consumes the released Foundation contract, supplies application defaults/layout/documentation, and proves the released package works as a real skeleton.

---

## 1. Preserved Foundation handoff

The following Foundation planning records were reviewed before their removal:

- `docs/plans/archive/README.md` — archive index only; no consumer task to transfer.
- `docs/plans/archive/foundation-3-release-readiness-evidence.md` — preserves the final consumer-boundary fixes and explicit post-release InfByte handoff.
- `docs/plans/archive/foundation-3-runtime-plan-pre-epicrypt3-integration.md` — historical runtime/lower-library ledger; its remaining consumer action was the final InfByte handoff.
- `docs/plans/foundation-3-final-runtime-development-plan.md` — closes Foundation release readiness and makes InfByte the next independent task.
- `docs/plans/foundation-3-module-system-hardening-plan.md` — transfers core-cache/module defaults, especially `config/cache.php`, and the final module lifecycle contract.
- `docs/plans/foundation-3-uid-5-utilization-evidence.md` — no InfByte-specific remaining action.
- `docs/plans/foundation-3.0-improvement-and-release-plan.md` — final Foundation 3 release plan; F30-09 and its deferred post-publication checklist are absorbed here.

This file is self-contained. Completion must not depend on those Foundation plan paths continuing to exist.

### Foundation contract that InfByte must preserve

1. Four application modes remain `web`, `cli`, `worker`, and `scheduler`.
2. Production consumes a trusted immutable Foundation release generation. Missing/invalid trust inputs must fail closed rather than silently rebuilding through development bootstrap.
3. Webrick owns request adaptation/response emission. InfByte must not add a second production HTTP kernel/emitter.
4. Optional specialist packages remain cold until explicitly selected by application capability topology.
5. CacheLayer is **Foundation core infrastructure**, not a specialist module. `cache`/`cachelayer` must not reappear in `module:*` lifecycle.
6. Core cache schemas use `cache:schema:status` and `cache:schema:install`.
7. Specialist modules remain the seven namespaces: `auth`, `communication`, `database`, `filesystem`, `messaging`, `security`, and `validation`. Foundation also retains built-in configuration/schema surfaces such as logging, operations, resources, and session.
8. Module package installation, feature selection, capability activation, configuration publication, schema applicability, and readiness are separate states.
9. Release generations are dependency/configuration-bound; stale or incompatible artifacts must fail closed.
10. Foundation owns generation activation/replacement and drain-safe generation retention; InfByte only supplies deployment UX and writable layout.
11. The declared Foundation 3.0 support scope must not be broadened in InfByte documentation into unverified native-host compatibility claims.
12. Migration is cumulative from Foundation 2.x to 3.0 and must account for configuration, generated artifacts, auth/session state, schemas, providers, and queued/persisted payload contracts where applicable.

---

## 2. Current PR #9 baseline

Foundation release baseline: tag `3.0`, source commit `441ccd713233036d927df98f402a0b36cd4d91da`. Stable InfByte cutover was validated by Security & Standards run #55 before the deeper consumer-rehearsal additions.

Already implemented on the InfByte branch:

- [x] production `public/index.php` uses `FoundationReleaseBootstrap::fromEnvironment(...)` and the trusted release web runtime;
- [x] production does not use `Request::fromGlobals()` or `AutoEmitter`;
- [x] development/application bootstrap remains separate from the production trusted-release entrypoint;
- [x] explicit `APP_CAPABILITIES` / `app.capabilities` skeleton configuration exists;
- [x] `config/cache.php` is included as the core CacheLayer application default;
- [x] legacy Foundation 2 route/container cache layout and switches are removed from the skeleton;
- [x] `storage/releases` is the writable release-generation location;
- [x] deployment guidance supplies trusted release-root/manifest identity from outside the writable generation;
- [x] `optimize`, `optimize:report`, `app:ready`, and `optimize:clear` are exercised through skeleton tests;
- [x] distribution/archive tests cover the Foundation 3 release layout;
- [x] module expectations exclude `cache`/`cachelayer` from the module catalog and use DBLayer `^5.1`;
- [x] temporary pre-tag Foundation dependency is explicit: `dev-foundation-3/close-26.6 as 3.0.0`;
- [x] temporary **Foundation 3 Pre-Tag Handoff** workflow is green on PHP 8.4/8.5 × prefer-lowest/prefer-stable (run #12).

The normal Security & Standards workflow is expected to reject the pre-tag branch constraint: its QA rows stop at PHPForge's stable-runtime constraint guard because `infocyph/foundation` is still a development branch. Analysis, clean install, and benchmarks otherwise reached their expected pre-tag results. This exception must disappear after the Foundation tag is published; it must not be bypassed.

---

## 3. Execution tracker

| Batch | Scope | Status | Completion gate |
| --- | --- | --- | --- |
| **A** | Pre-tag migration implementation | **DONE** | PR #9 baseline above remains green under the temporary handoff workflow. |
| **B** | Stable Foundation 3 package cutover | **DONE** | Stable `^3.0`, no temporary workflow, normal PHPForge release guard/clean install green in run #55. |
| **C** | Final released-contract resync | **DONE** | Released catalog/docs/config audited; direct/transitive/ownership-unknown package reporting and stable consumer gates are proven in run #103. |
| **D** | Fresh-install and capability scenarios | **DONE** | Lean, specialist-module, and full production auth/session scenarios pass against released Foundation 3.x in Security & Standards run #114. |
| **E** | Module/config/schema lifecycle | **DONE** | Released module/config/schema lifecycle, cache schema lifecycle, repair/removal, aggregate sync, and connection/applicability checks pass in run #103. |
| **F** | Production release/runtime rehearsal | **DONE** | Trusted build/request, stale rejection, reload, drain-safe retention, rollback consumption, failed-build retention, and read-only source pass in run #103. |
| **G** | 2.1 → 3.0 migration rehearsal | **DONE** | Representative 2.1 application upgrade, provider/route preservation, persisted-data boundary, session incompatibility handling, runtime build/readiness, and real request pass in run #103. |
| **H** | Final CI/docs/publication evidence | **DONE** | Security & Standards run #114 is fully green on released Foundation 3.x, including the normal PHPForge matrix, create-project, consumers, module lifecycle, generation lifecycle, 2.1→3.0 migration, and production auth/session lifecycle. |

---

## 4. Batch B — stable Foundation 3 package cutover

Start only after Foundation 3.0 is published through normal Composer resolution.

- [x] Confirm the released Foundation tag and source SHA.
- [x] Replace `"infocyph/foundation": "dev-foundation-3/close-26.6 as 3.0.0"` with the intended stable Foundation 3 constraint, normally `^3.0`.
- [x] Remove `.github/workflows/foundation3-handoff.yml`; it is a temporary pre-tag exception only.
- [x] Remove README/PR wording that points consumers at the Foundation development branch.
- [x] Resolve dependencies using normal Composer repositories with no path/VCS branch workaround.
- [x] Run `composer validate --strict`.
- [x] Run PHPForge's stable runtime constraint guard unchanged.
- [x] Verify no development-only Foundation/PHPForge dependency leaks into production `--no-dev` installation.

**Acceptance:** DONE. Foundation `3.0` resolves normally through `^3.0`; the temporary handoff workflow is removed; PHPForge run #55 passed the stable release guard, all four QA variants, analysis, benchmarks and clean production install.

---

## 5. Batch C — resync against the released contract

PR #9 predates the final Foundation 3 release-closure work, so re-audit it against the tagged package rather than assuming the pre-tag candidate is identical.

### Configuration and topology

- [x] Compare every checked-in InfByte config template with the released Foundation defaults/public contract.
- [x] Keep `config/cache.php` as a default application config, while keeping the cache capability cold when explicit topology omits `cache`.
- [x] Verify `APP_CAPABILITIES=` still creates the intended lean skeleton.
- [x] Verify omitted topology is documented only as compatibility/development inference where Foundation supports it; production examples should prefer explicit topology.
- [x] Verify provider registration remains application-owned and does not duplicate lower-library mechanics.

### Module contract

- [x] Verify the seven specialist module names/aliases/features against the released catalog.
- [x] Verify built-in logging/operations/resources/session surfaces remain non-installable built-ins.
- [x] Verify `cache`/`cachelayer` are rejected as module lifecycle targets.
- [x] Verify current specialist package floors exposed by `module:show`/planning match the released Foundation catalog.
- [x] Verify direct/transitive/ownership-unknown package reporting and constraint compatibility remain visible rather than inferred incorrectly.
- [x] Keep `module:install`, feature selection, enablement, config publication, schema applicability and readiness separate in docs/examples.

### Runtime/release contract

- [x] Recheck `public/index.php`, `bootstrap/app.php`, `deploy.sh`, writable paths, and archive filters against the released APIs.
- [x] Confirm the release manifest/dependency fingerprint contract used by InfByte matches the tagged Foundation implementation.
- [x] Confirm release trust remains external deployment/service configuration and is never learned from the writable generation itself.
- [x] Confirm documentation does not claim native FPM/Runwire/FrankenPHP/RoadRunner/Swoole certification beyond Foundation's declared 3.0 support statement.

**Acceptance:** DONE. Released configuration/module/runtime contracts and ownership-state reporting are proven by run #103.

---

## 6. Batch D — fresh install and capability scenarios

Test the **published** package, not only the repository checkout.

### D1 — clean create-project

- [x] Create a fresh project through the normal published InfByte package/create-project flow.
- [x] Verify `app:install` provisions environment secrets without requiring optional Epicrypt.
- [x] Verify production `--no-dev` installation/autoload.
- [x] Verify shipped archive excludes repository-only tests/plans/tooling while retaining required writable-directory placeholders.
- [x] Verify `plan.md` remains excluded from distributed skeleton archives.

### D2 — lean application

- [x] Keep explicit capability topology empty.
- [x] Run configuration validation and `app:ready`.
- [x] Build the production generation.
- [x] Prove optional database/auth/messaging/filesystem/security/validation/communication packages are not required merely to boot the lean skeleton.
- [x] Prove inactive auth/cache policy does not falsely block readiness.

### D3 — auth/session application

- [x] Enable the required auth/session topology explicitly.
- [x] Install/configure the selected auth feature(s), including OTP/passkey only when selected.
- [x] Provision applicable schemas.
- [x] Exercise login/session issuance, rotation/invalidation, logout and one negative/stale-session path through the skeleton.
- [x] Verify secrets/tokens/cookies are not exposed by public errors/readiness output.

### D4 — representative specialist application

Use at least one package-backed module such as `database` or `messaging`.

- [x] Run `module:plan` before mutation.
- [x] Install the module through the skeleton CLI.
- [x] Publish only applicable config.
- [x] Provision only applicable schemas.
- [x] Verify the selected capability becomes ready while unrelated modules remain cold.

**Acceptance:** DONE. Run #114 proves the released Foundation 3.x lean, specialist-module, and production auth/session scenarios, including login/session issuance, session-ID rotation, stale-session rejection, invalidation, and logout.

---

## 7. Batch E — module/config/schema lifecycle rehearsal

Exercise the public lifecycle exactly as an application operator would.

- [x] `module:list --json`
- [x] `module:show <module> --json`
- [x] `module:doctor <module> --json`
- [x] `module:plan <module> ...`
- [x] `module:install <module> ...`
- [x] module enable/disable semantics where applicable
- [x] module config publication without overwriting application-owned config unless explicitly forced
- [x] `module:schema:status` / `module:schema:install` for applicable Foundation-owned module schemas
- [x] `cache:schema:status` / `cache:schema:install` for configured database-backed core cache stores
- [x] `module:repair` on a deliberately interrupted/partial install fixture
- [x] removal/dependency checks without deleting application config/data
- [x] aggregate schema sync follows active capability topology and does not provision unrelated schemas
- [x] module/schema commands may inspect/use a connection when applicable without inventing a synthetic database-capability prerequisite before applicability is known

**Acceptance:** DONE. Module/config/schema lifecycle is green in run #103.

---

## 8. Batch F — production release/runtime rehearsal

### F1 — build and trust

- [x] Run `config:validate --production`.
- [x] Run `optimize` and capture generation, manifest and SHA-256.
- [x] Run `optimize:report`.
- [x] Run `app:ready`.
- [x] Start the production entrypoint with deployment-supplied trusted manifest identity.
- [x] Prove missing/wrong manifest identity fails closed.
- [x] Prove tampered/stale dependency-bound generations fail closed.
- [x] Prove source config/routes/providers are not rediscovered on steady production requests after compilation.

### F2 — real HTTP request

- [x] Serve the released skeleton through the supported default Foundation/Webrick path.
- [x] Perform at least one real HTTP health/application request against the production generation.
- [x] Confirm Webrick performs the single response write; InfByte must not add a second emitter/kernel path.

### F3 — generation lifecycle

- [x] Rebuild after a configuration/topology change and verify atomic activation.
- [x] Prove a failed/staged build leaves the previous valid generation active.
- [x] Exercise process reload/replacement.
- [x] Exercise rollback to the previous compatible generation.
- [x] Verify draining processes retain the generation they need and pruning respects Foundation's generation lease/drain contract.
- [x] Verify read-only application source with separate writable storage/release directory.
- [x] Keep code rollback separate from schema/data rollback; document expand/contract expectations for mixed generations.

**Acceptance:** DONE. Build/trust/serve/replacement/stale-rejection/reload/drain/rollback/read-only-source behavior is green in run #103.

---

## 9. Batch G — representative Foundation 2.1 → 3.0 upgrade

Use a representative InfByte/Foundation 2.1 application fixture rather than validating only a fresh install.

- [x] Record the starting Composer constraints/configuration/bootstrap/layout.
- [x] Upgrade to the released Foundation 3-compatible InfByte contract.
- [x] Replace implicit package activation with explicit capabilities.
- [x] Migrate retired route/container generated-artifact assumptions to the release-generation model.
- [x] Preserve application-owned custom providers and routes.
- [x] Review auth credentials/tokens, browser sessions, OAuth/passkey data, module schemas and queued/durable payload compatibility where the fixture uses them.
- [x] Apply additive/expand-contract schema changes before incompatible cleanup.
- [x] Verify old generated artifacts cannot be trusted by the new dependency/configuration identity.
- [x] Run application tests, `module:doctor`, `optimize`, `app:ready`, and one real request after migration.
- [x] Document required manual changes and anything that cannot be automatically migrated.

**Acceptance:** DONE. Run #103 proves the representative Foundation 2.1 → 3.0 application upgrade and explicit persisted-state compatibility boundary.

---

### Stable Foundation 3 qualification result

The earlier production-auth compilation failure was traced to an incomplete explicit capability topology in the certification fixture: TalkingBytes-backed auth requires both `communication` and Foundation's native `notifications` capability. With that topology declared, released Foundation 3.0 compiles and runs the full production auth/session lifecycle without a development-branch or patch-package override. InfByte remains on `infocyph/foundation:^3.0`.

## 10. Batch H — final release qualification

- [x] Run normal Security & Standards with no temporary handoff workflow or release-constraint exception.
- [x] PHP 8.4 / prefer-lowest QA passes.
- [x] PHP 8.4 / prefer-stable QA passes.
- [x] PHP 8.5 / prefer-lowest QA passes.
- [x] PHP 8.5 / prefer-stable QA passes.
- [x] PHP 8.4/8.5 analysis/audit passes.
- [x] Clean production install passes.
- [x] Applicable InfByte release benchmark/report jobs pass without weakening PHPForge thresholds/configuration.
- [x] Fresh published-package create-project passes.
- [x] Lean + auth/session + specialist-module scenarios pass.
- [x] Production release/real-request/reload/rollback rehearsal passes.
- [x] 2.1 → 3.0 migration rehearsal passes.
- [x] README and examples use stable Foundation package/docs references, not development-branch URLs.
- [x] PR description is updated with final Foundation tag/SHA, InfByte head SHA and final CI run IDs.
- [x] Record final package/source/CI identities in a concise release evidence section or release notes.

**Completion definition:** InfByte is a clean, stable Foundation 3 consumer whose published skeleton can be created normally, configured explicitly, extended through the supported module lifecycle, compiled into a trusted production generation, served through the released runtime contract, upgraded from a representative 2.1 application, and validated by the normal PHPForge release gates.

### Final release evidence

- Foundation package constraint: `infocyph/foundation:^3.0`
- Foundation release tag: `3.0`
- Foundation tag object: `d4d73bf9e2e6650e1665577387e2457c43ef07ca`
- Foundation 3.0 source commit: `441ccd713233036d927df98f402a0b36cd4d91da`
- InfByte release-qualified implementation head: `a105a687817c2a8f3918017500c4745fcffa0f9d`
- Final implementation qualification: Security & Standards run #114 (`36017258288`)
- Production auth/session job: `107693287053` — success
- Released generation lifecycle job: `107693286851` — success
- Released module lifecycle job: `107693286874` — success
- Foundation 2.1 → 3.0 migration job: `107693286899` — success
- Release-candidate create-project job: `107693286932` — success
- Released PHP 8.4 consumer job: `107693286987` — success
- Released PHP 8.5 consumer job: `107693287013` — success
- Normal PHPForge PHP 8.4/8.5 lowest/stable QA, analysis, clean-install, and benchmark rows: success
- No PHPForge/PHPProbe bypass, threshold weakening, temporary handoff workflow, or Foundation development-branch dependency is required.

---

## 11. Non-blocking application backlog preserved from Foundation review

These are **not Foundation 3 / InfByte migration blockers**. Re-evaluate them only against concrete application needs after the stable handoff is complete:

- application observability/correlation context and optional tracing exporter;
- opt-in queued notifications using Omnibus/TalkingBytes rather than a second queue engine;
- transactional outbox composition using DBLayer + Omnibus;
- improved application test ergonomics and deterministic time/session helpers;
- reusable application feature packages through explicit build-time registration;
- tenant-aware application composition with scoped immutable tenant identity;
- scheduler operating refinements such as DST/missed-run/heartbeat policy;
- application idempotency recipe using CacheLayer/DBLayer atomic primitives;
- optional browser-session read-only/early-close mode only if measurements prove lock contention.

Each item needs a separate small design, compatibility decision, tests and performance criteria before implementation.

---

## 12. Guardrails

- Do not edit Foundation merely to satisfy an InfByte preference unless the **released Foundation contract itself is objectively broken** and reproduced independently of the skeleton.
- Do not restore Foundation 2 route-cache/container-resolver switches.
- Do not make optional package installation imply capability activation.
- Do not reintroduce cache as a module.
- Do not hide release failures with development bootstrap, skipped tests, PHPProbe/PHPForge exclusions or weakened thresholds.
- Do not merge PR #9 automatically; final merge/release remains owner-controlled.
