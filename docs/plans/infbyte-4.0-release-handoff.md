# Infbyte 4.0 release handoff

## Review scope

Infbyte 4 is the application skeleton for Foundation 4. Application bootstrap,
routes, controllers, configuration and deployment remain application-owned.
Foundation composes the native libraries and their request/task lifetimes.

The candidate verifies Core Runwire serving through `php infbyte serve`, AUTO
and explicit native/Swoole selection, and ordinary nginx/PHP-FPM through
`public/index.php`. Its no-dev consumers exercise all eight explicitly selected
optional features. Immutable generation trust, failed trust checks, rollback,
retained SQLite records and a real `composer create-project` are covered.

This major release removes Foundation 3 compatibility assumptions, old module
aliases and obsolete compiled-artifact loading. Core is always installed;
optional groups are data, security and execution, with eight qualified features.
No Infbyte router, container, worker manager or event loop is introduced.

## Prepared stable cutover

The [unapplied cutover patch](infbyte-4.0-stable-cutover.patch) changes only
`composer.json` and `README.md`. It requires Foundation `^4.0`, removes the VCS
repository override and replaces candidate installation guidance with published
release guidance. The current development manifest remains usable until the
required package is published. The patch passes `git apply --check` and its
prospective Composer manifest passes strict validation.

The patch is preparation, not proof that Foundation 4 exists on Packagist.
No stable alias, invented package version or local repository substitutes for
published-package acceptance. This handoff and patch are excluded from the
application archive by the existing `/docs/plans export-ignore` rule.

## Operator-controlled release sequence

1. Complete Foundation's current-source performance acceptance and release
   review. Merging, tagging and publication require the operator's decision.
2. Publish Foundation 4 and verify its source identity and normal Composer
   availability. Only then apply the prepared Infbyte manifest/README patch.
3. Resolve actual published dependencies and run the full configured checks.
   Review the resulting dependency identities and security audit separately
   from development-only abandoned packages.
4. Commit the stable cutover and run both application candidate acceptance and
   the published-release workflow on that exact branch revision. Require PHP
   8.4/8.5, lowest/stable PHPForge checks, published Core and DBLayer no-dev
   consumers, native host acceptance and actual exported project installation.
5. Review the final source, artifacts and release notes before any Infbyte
   merge, tag or publication. A candidate run cannot close published resolution.

After step 2, run from the Infbyte repository:

```bash
git apply --check docs/plans/infbyte-4.0-stable-cutover.patch
git apply docs/plans/infbyte-4.0-stable-cutover.patch
composer update --with-all-dependencies --no-interaction --prefer-dist
composer validate --strict
composer ic:doctor
composer ic:list-config
composer ic:active-config
composer ic:ci
composer audit
```

After committing that stable cutover, manually dispatch Security & Standards
against the same branch before considering a merge:

```bash
gh workflow run security-standards.yml --repo infocyph/Infbyte --ref feature/infbyte-4
```

Manual dispatch performs release qualification; its tag-only publication job
does not run. Verify the resulting run's exact SHA and all required jobs rather
than treating dispatch success as validation.
