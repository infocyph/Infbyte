# Infbyte — Foundation 2.0 Live Work Plan

> This is the evidence-driven source of truth for Infbyte's Foundation 2.0 integration. Foundation owns reusable framework/runtime behavior; Infbyte owns the opinionated application skeleton and host composition.

## Branches

- Infbyte: `feature/foundation-2.0`
- Foundation: `feature/foundation-2.0`
- Integration PR: draft PR #5, `feature/foundation-2.0` → `main`

## Current checkpoint — 2026-08-25

- Verified Infbyte source/docs checkpoint: `f2e81b09a843a3d9ea162ed349790107078cf1f8`
- Verified Foundation prerelease checkpoint consumed by the integration line: `f559355b785d4edc56edfde2eadd1aa652c69360`
- Authoritative Infbyte integration Security & Standards run: `32837108534`
- Result: PHP 8.4/8.5 × prefer-lowest/prefer-stable quality suites PASS; clean production install PASS.
- Current phase: **source/dependency/API/docs audit complete; stable Foundation 2.0 publication, constraint cutover, normal release-workflow restoration, and final stable run remain**.

## Ownership boundary

Foundation owns reusable framework/runtime behavior: explicit runtimes, DI/provider activation, CLI, scheduler/worker composition, purpose modules, schema orchestration, optimization, operational controls, application contracts, and specialist-package integration.

Infbyte owns only the opinionated host skeleton: application bootstrap, app-specific defaults, routes/application code, writable layout, deployment conventions, distribution/archive rules, and final developer experience.

Specialist packages retain their own database/cache/messaging/communication/crypto/validation/filesystem engines.

## Frozen Infbyte structure

- root `infbyte` delegates to Foundation `CommandDispatcher`;
- Web bootstrap delegates to `Foundation::web()`;
- Foundation exposes exactly Web, CLI, Worker, Scheduler runtimes;
- provider groups are `common|web|cli|worker|scheduler`;
- checked-in config remains deliberately only `app.php`, `auth.php`, `router.php`;
- checked-in routes are `api.php`, `console.php`, `schedule.php`, `workers.php`;
- optional capability config is module-published on demand;
- `routes/console.php` registers application commands;
- `routes/schedule.php` registers schedule definitions;
- `routes/workers.php` registers non-message maintenance workers;
- generated optimized artifacts are not committed;
- deployment optimization uses `php infbyte optimize`;
- Foundation 1 convenience APIs/runtime hierarchy are not restored.

## Foundation dependency baseline consumed by Infbyte

Core Foundation runtime:

- PHP `^8.4`
- `infocyph/arraykit ^5.1.1`
- `infocyph/intermix ^9.2`
- `infocyph/uid ^5.0`
- `infocyph/webrick ^4.0.2`

Optional capability packages owned by Foundation modules:

- `infocyph/cachelayer ^3.2.0`
- `infocyph/dblayer ^5.0`
- `infocyph/epicrypt ^2.1`
- `infocyph/omnibus ^2.5`
- `infocyph/otp ^6.0`
- `infocyph/pathwise ^3.1`
- `infocyph/reqshield ^3.1`
- `infocyph/talkingbytes ^2.0`
- `web-auth/webauthn-lib ^5.3.5`
- `infocyph/phpforge dev-main@dev`

During branch development Infbyte intentionally requires:

```json
"infocyph/foundation": "dev-feature/foundation-2.0 as 2.0.x-dev"
```

Final release alignment must replace this with the stable Foundation `^2.0` constraint after Foundation 2.0 is published.

## Frozen purpose-first modules

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

## Config/schema lifecycle inherited from Foundation

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

- `auth` → Foundation auth schema;
- `cache` → CacheLayer public PDO/SQLite/invalidation schemas;
- `session` → Foundation database-session schema.

Schema status is read-only. Explicit installation owns mutation. Module removal never drops schema/data.

Infbyte does not copy optional config into the skeleton merely because Foundation supports the capability.

## Application API rule

Foundation `Application` is a narrow runtime/composition object. Infbyte application code resolves concrete services through constructor injection or `Application::make()`.

Do not rely on or recreate retired convenience facades such as:

- `$app->auth()` / `$app->authManager()`;
- `$app->session()` / `$app->browserSession()`;
- `$app->router()`;
- `$app->responses()`;
- `$app->testing()`;
- `$app->messaging()`;
- `$app->ids()`;
- generic cache/database/filesystem/security/validation manager methods.

## Application contracts available to Infbyte apps

### Validation

- Foundation `FormRequest` composes Webrick request input with ReqShield;
- custom rules implement ReqShield `Contracts\Rule` directly;
- generators: `create:request`, `create:rule`.

### Notifications/mail

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

### Messaging/jobs

- `Job`
- `JobContext`
- `JobMiddleware`
- Omnibus-backed handler pipeline
- generators: `create:job`, `create:handler`, `create:job-middleware`.

### Resources/testing

- `JsonResource::resolve(): mixed`;
- `create:resource` targets the current contract;
- resolve `JsonDispatchResponseFactory`, `AuthServices`, and `TestKit` through DI rather than Application shortcuts.

## Runtime/operations inherited by Infbyte

### Omnibus 2.5 workers

- single messaging workers use native Omnibus `WorkerLifecycle` for heartbeat/reload/stop;
- no `pcntl` requirement solely for single-worker generation polling;
- Unix `WorkerPool` remains pcntl/posix based and retains the Foundation watchdog;
- provider-only workers remain messaging-lazy.

### Scheduler/runtime control

- overlap/single-server locks refresh during child execution;
- lost lease terminates/fails the child;
- schedule history uses stable identity;
- `schedule:test` reports actual failure status;
- file/cache generation-map mutations are atomic/serialized;
- cache-backed runtime control requires suitable shared visibility and coordination;
- runtime registry visibility is `host|shared`, default `host`;
- process registry is observability metadata, not process-supervision truth.

### Other operations

- supervised child commands do not duplicate `--profile` output;
- `log:tail --follow` handles truncation/rotation;
- production OTP validation uses production topology assumptions;
- environment encryption remains Epicrypt-backed with external key material only;
- cache schema status does not create a missing SQLite file.

## CLI inheritance

The root executable delegates directly to Foundation, so Infbyte inherits the Foundation command catalog without duplicate command classes.

Global controls include `--quiet`, `--silent`, `-v|-vv|-vvv`, `--profile`, `--json`, `--env`, `--no-interaction`, help/version/completion.

## Resolved integration issues — evidence

The current draft PR #5 head `f2e81b09a843a3d9ea162ed349790107078cf1f8` is validated by run `32837108534`.

Resolved items:

- removed stale Foundation 1 test usage such as `Foundation::console()`, `$app->testing()`, broad manager/facade methods and retired schema command names;
- verified the exactly-four-runtime contract and narrow `Application` surface;
- made Web response tests format-independent by decoding JSON;
- aligned route/config/command cache tests to current Foundation 2 CLI contracts and canonical cache paths;
- isolated CLI cache tests in temporary skeletons so runtime artifacts cannot race with distribution tests;
- verified canonical `database` module naming, built-in module status, readiness JSON and `php infbyte module:install database` guidance;
- aligned `.gitattributes` with Foundation 2 cache artifacts (`commands.php`, `schedule.php`, `optimize.php`, `config/`, `container/`, `routes/`);
- aligned create-project archive/deploy/`optimize:clear` tests with current cache-tree ownership;
- removed stale expected `ids.php` base config; checked-in config remains `app.php`, `auth.php`, `router.php`;
- removed obsolete PHPForge reusable-workflow inputs that caused workflow startup failure;
- preserved PHPForge's stable runtime-constraint guard instead of weakening release policy;
- corrected the README skeleton tree so it no longer advertises nonexistent `routes/web.php` or `routes/auth.php` files.

### CI policy during prerelease integration

PHPForge's stable-runtime constraint guard correctly rejects the temporary branch alias as a release dependency. Therefore this feature branch currently runs an explicit integration matrix with the same PHPForge quality suite after dependency resolution:

- PHP 8.4 prefer-lowest: PASS;
- PHP 8.4 prefer-stable: PASS;
- PHP 8.5 prefer-lowest: PASS;
- PHP 8.5 prefer-stable: PASS;
- clean production install on PHP 8.5: PASS.

This is temporary. Once Foundation 2.0 is published and Infbyte changes to `^2.0`, restore the normal reusable PHPForge Security & Standards workflow so the stable-runtime constraint guard is a release gate again.

## Final prerelease audit — 2026-08-25

Completed against the active feature branch:

- Composer remains intentionally minimal: PHP, Foundation, and PHPForge only; Infbyte does not duplicate Foundation's specialist dependencies.
- Foundation module/dependency baseline in this plan is current: CacheLayer 3.2.0, DBLayer 5.0, Omnibus 2.5, ReqShield 3.1 and the other frozen specialist versions.
- no stale `4.1`, `2.4`, or `3.0.1` integration-version references remain in indexed Infbyte release content;
- no active `Foundation::console`, old manager classes, `module:install db`, or retired hyphenated module-schema command usage remains;
- README, Composer, workflow, distribution rules and exercised integration tests are aligned with the actual branch tree;
- draft PR #5 remains mergeable and intentionally draft until the stable Foundation dependency exists.

## Distribution contract now verified

A create-project archive:

- retains the application example tests and writable-directory placeholders;
- excludes repository-only verification tests and development metadata;
- contains no generated PHP cache artifacts;
- contains no pre-created `bootstrap/cache/config`, `container`, or `routes` runtime trees;
- provisions `.env` idempotently with secure permissions and generated auth secret;
- `deploy.sh` builds Foundation 2 config/route/command/schedule/optimize/container artifacts;
- `optimize:clear` removes every managed artifact and the complete dedicated config cache tree.

## Verification status

Completed with evidence:

1. [x] Composer dependency resolution on PHP 8.4/8.5 lowest/stable.
2. [x] PHPForge quality suite on PHP 8.4/8.5 lowest/stable (`32837108534`).
3. [x] clean production install (`32837108534`).
4. [x] four-runtime and narrow-Application integration tests.
5. [x] canonical Web handling and route cache consumption.
6. [x] config/route/command cache lifecycle through the Infbyte CLI wrapper.
7. [x] canonical module/readiness/install-guidance contracts.
8. [x] clean create-project/archive/install/deploy/optimize-clear path.
9. [x] Foundation 2 cache/distribution `.gitattributes` alignment.
10. [x] stale Foundation 1 test/API expectations removed from the exercised integration suite.
11. [x] Finish source/docs/dependency stale-version and retired-API audit across the whole Infbyte branch (`f2e81b09a843a3d9ea162ed349790107078cf1f8`, `32837108534`).

Remaining before stable release:

12. [ ] Publish Foundation 2.0 and replace `dev-feature/foundation-2.0 as 2.0.x-dev` with stable `^2.0`.
13. [ ] Restore the normal reusable PHPForge release workflow and obtain a full green stable-constraint run.
14. [ ] Record final post-publication Infbyte + Foundation source/CI checkpoints and close all release ambiguities.

## Do not regress

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
- no generated optimized artifacts committed;
- no weakening of PHPForge stable-release dependency policy to accommodate a prerelease branch alias.
