# Infbyte 4.0 release handoff

Foundation is the reusable application composition library; Infbyte is its
Core-only starter. No duplicated framework mechanisms or default risk activation.

## Published dependency cutover — 2026-11-24

[Foundation 4.0](https://github.com/infocyph/Foundation/releases/tag/4.0) is
published. The Infbyte manifest now requires `^4.0` without VCS overrides;
the prepared cutover patch is retired. GitHub publication is verified, normal
Packagist resolution must pass on hosted workers.

## Remaining release gates

- [x] Foundation 4 publication.
- [x] Stable Infbyte dependency, minimal README and stable guard on PRs.
- [ ] Normal Composer resolution and exact resolved Foundation identity.
- [ ] PHP 8.4–8.6 lowest/stable QA, PHPStan and Psalm; no skipped tests.
- [ ] All eight selected no-dev features and retained database state.
- [ ] Native/direct HTTP hosts, release trust and rollback.
- [ ] Independent create-project and published Core/DBLayer archive consumers.
- [ ] Close review findings and verify final release evidence.
- [ ] Maintainer approval, merge, tag and publication.

The detailed [integration tracker](infbyte-4.0-foundation-integration-plan.md)
preserves historical source and CI evidence. Do not treat an incomplete run as
green or infer production throughput equivalence from shared-runner diagnostics.
