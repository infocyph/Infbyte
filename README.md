# Infbyte

Infbyte is the minimal application skeleton for [Infocyph Foundation](https://github.com/infocyph/Foundation). It owns application routes, controllers, configuration, project structure and deployment decisions. Foundation and its native dependencies own the framework and runtime mechanisms.

## Foundation 4 development integration

**This development branch follows Foundation `feature/foundation-3`, the Foundation 4.0 candidate.** Its `composer.json` temporarily requires `dev-feature/foundation-3` using the Foundation GitHub VCS source. That is an integration dependency, **not** a stable Foundation 4 publication or a production release instruction.

- PHP 8.4+ (64-bit) and Composer 2.
- [Foundation candidate source](https://github.com/infocyph/Foundation/tree/feature/foundation-3)
- [Current Foundation 4 contract](https://github.com/infocyph/Foundation/blob/feature/foundation-3/docs/foundation-4-api-map.md)
- [Infbyte integration tracker](docs/plans/infbyte-4.0-foundation-integration-plan.md)

After Foundation 4.0 is published and verified, the release-ready Infbyte manifest must use `"infocyph/foundation": "^4.0"`, remove the development VCS override, and pass clean normal Composer resolution.

## Development

```bash
composer install
php infbyte --version
php infbyte module:list
php infbyte serve
```

The `serve` command belongs to Foundation and delegates to **Core Runwire**. Runwire selects its available host (AUTO by default); a supported explicit native selection is `php infbyte serve --runtime=swoole`. Host extensions/capabilities must be installed before using their drivers. `serve` does not reimplement a PHP listener in the skeleton.

For regular nginx/Apache + PHP-FPM, configure the document root to `public/` and invoke `public/index.php` **without** `serve`. The entrypoint only loads an externally trusted Foundation release and hands native HTTP handling to Webrick. There is no Infbyte-owned request factory or response emitter.

The `infbyte` root executable delegates to Foundation's `CommandDispatcher`. The available commands and parameters are those supported by the **installed Foundation source**, not a second application CLI implementation.

## Application structure

```text
app/                      Application classes/controllers
bootstrap/app.php         Explicit Foundation web bootstrap
bootstrap/providers.php   Per-runtime service provider declarations
config/app.php            Identity, capabilities and development settings
config/auth.php           Core authentication application policy
config/cache.php          Core CacheLayer resources
config/modules.php        Explicit optional feature activation
config/notifications.php  Core notification defaults
config/router.php         Webrick route source and matcher policy
public/index.php          Trusted direct PHP/FPM entrypoint
routes/api.php            Application Webrick route registrations
routes/console.php        Application CLI command declarations
routes/workers.php        Application maintenance worker declarations
routes/schedule.php       Application schedule
storage/releases/         Generated immutable Foundation releases
deploy.sh                 Project deployment preparation
```

Foundation provides four distinct application modes: **web**, **cli**, **worker** and **scheduler**. The `bootstrap/providers.php` keys match those modes. Provider graph definitions and the runtime execution lifecycle are Foundation/InterMix concerns. Routing and response adaptation are Webrick concerns.

Production capability topology remains explicit through `app.capabilities` (the `APP_CAPABILITIES` environment value). It is separate from `modules.features`: enabling a feature does not imply adding every optional capability to the compiled application, and merely installing a package never activates it.

## Core and optional features

CacheLayer, InterMix, Runwire, Webrick, ArrayKit, UID and PSR Log are **Foundation Core** dependencies. Core auth, sessions, notifications, resources, logging and operations are **not installable modules**. PHPForge is development/QA tooling.

Foundation 4 has exactly **three optional groups and eight independently selected features**:

| Group | Feature | Owner |
| --- | --- | --- |
| data | `data.database` | DBLayer |
| data | `data.filesystem` | Pathwise |
| security | `security.crypto` | Epicrypt |
| security | `security.validation` | ReqShield |
| security | `security.mfa` | OTP |
| security | `security.passkey` | OTP and WebAuthn |
| execution | `execution.messaging` | Omnibus |
| execution | `execution.communication` | TalkingBytes |

A fresh skeleton ships `config/modules.php` with `features: []`: **Core-only by default**. Selection is explicit; no group-wide implicit installation or auto-enabling:

```bash
php infbyte module:list
php infbyte module:show data
php infbyte module:plan data.database
php infbyte module:install data.database --dry-run
php infbyte module:install data.database
php infbyte module:enable data.database
php infbyte module:config:publish data.database
php infbyte module:doctor data.database
```

Module machine output is schema version 2. `module:install` reconciles the requested native package and missing config but never enables the feature. `module:enable` applies explicit selection to `config/modules.php`. Use `module:repair` after interrupted package work. Removing a feature requires disabling it first and does not delete application-owned data or schema.

OTP-backed MFA requires an explicitly selected `security.crypto` when the OTP MFA driver is used; selecting passkeys does not enable MFA. Durable Omnibus messaging requires `data.database`. Native packages, migration schemas, and extension requirements are enforced by Foundation's planner.

See the [Foundation module reference](https://github.com/infocyph/Foundation/blob/feature/foundation-3/docs/modules.md) and [configuration ownership reference](https://github.com/infocyph/Foundation/blob/feature/foundation-3/docs/configuration.md) for their current contracts.

## Configuration and application safety

```bash
php infbyte config:validate
php infbyte config:cache
php infbyte config:clear
php infbyte cache:schema:status
```

Only selected feature owners publish optional files. `config/cache.php` and `config/notifications.php` stay Core-owned application configuration. Build-time ArrayKit caches may be `sharded` or `single`; immutable production generations instead consume their own normalized `config.php` snapshot.

Keep secrets (including `AUTH_TOKEN_SECRET`), signed manifest trust values and service credentials in the deployment environment, not committed configuration. Development auth defaults are not a substitute for durable production authentication/session policy. Do not retain request/principal/session state in long-lived application singletons or mutable static fields when using persistent workers.

## Trusted deployment

```bash
php infbyte config:validate --production
php infbyte optimize --json=1
php infbyte app:ready
# Or run the project deployment wrapper:
bash deploy.sh
```

`deploy.sh` prepares writable runtime directories without root and expects Foundation to return the release root and trusted manifest SHA-256. Deploy the two external inputs to the host supervisor **outside the writable release directory**:

```text
INFOCYPH_FOUNDATION_RELEASE_ROOT
INFOCYPH_FOUNDATION_RELEASE_MANIFEST_SHA256
```

A trusted release comprises the compiled native Webrick 6 routing artifact, InterMix 11 ABI-3 application graphs, normalized configuration and generation identity for web/CLI/worker/scheduler. It must fail closed when metadata or artifacts are missing/mismatched; the production request path must not fall back to mutable source configuration.

Do not use this Foundation 4 candidate branch for a final production release until Foundation itself is published and the Infbyte acceptance plan is complete. The [development plan](docs/plans/infbyte-4.0-foundation-integration-plan.md) tracks the remaining host, runtime, release and Composer QA gates.

## QA

Repository verification uses PHPForge's configured commands and the GitHub Security & Standards workflow. In a clean checkout with the Foundation candidate installed:

```bash
composer ic:ci
```

Qualification of a final published Foundation 4 integration additionally requires PHP 8.4/8.5, lowest/stable dependency consumers, create-project archive, supported direct FPM and Runwire hosts, isolated/parallel requests, immutable releases and reproducible end-to-end performance. Historical Foundation 3 CI does not certify this branch.
