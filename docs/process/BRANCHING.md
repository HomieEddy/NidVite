# Branching Policy

This document defines the working branching strategy for NidVite before Phase 1 implementation.

## Goals

- Keep `main` production-ready at all times.
- Keep changes small and reversible.
- Enforce quality gates before merge.
- Support fast parallel work without long-lived feature drift.

## Strategy

Use **trunk-based development with short-lived branches**.

### Long-lived branch

- `main` only.

### Short-lived branches

Create from latest `main`:

- `feat/<scope>-<intent>`
- `fix/<scope>-<intent>`
- `chore/<scope>-<intent>`
- `test/<scope>-<intent>`
- `hotfix/<scope>-<intent>` (production critical only)

Examples:

- `feat/anti-abuse-rate-limits`
- `fix/captcha-validation-edge-case`
- `test/report-submission-rate-limit`

## Branch Rules

### `main`

- Protected branch.
- No direct push.
- PR required.
- Branch must be up to date before merge.
- Force push disabled.
- Deletion disabled.

### PR merge requirements

All must pass before merge:

- Pest tests
- Laravel Pint
- PHPStan level 5
- CodeRabbit review complete with no unresolved `request_changes`
- Self-review complete
- PR includes `How to Test`

## Merge Policy

- Default merge method: **Squash and merge**.
- Keep each PR focused on one concern.
- Target PR size: ideally under ~400 changed lines.
- If a task is larger, split into multiple PRs with explicit dependency order.

## Release and Hotfix

### Normal release

- Merge PR into `main` after all checks.
- Railway auto-deploys from `main`.

### Hotfix

1. Branch from `main`: `hotfix/<scope>-<intent>`
2. Apply minimal fix.
3. Run full gates locally.
4. Open PR to `main` with `[HOTFIX]` in title.
5. Squash merge after approvals/checks.

## Required Local Checks Before Opening PR

Run from project root:

```bash
./vendor/bin/pest
./vendor/bin/pint
./vendor/bin/phpstan analyse --level=5
```

## Commit Convention

Use Conventional Commits:

- `feat:`
- `fix:`
- `refactor:`
- `test:`
- `chore:`

Example:

```text
feat(anti-abuse): add per-ip rate limiter for report submission
```

## Suggested Phase 1 Branch Breakdown

For Anti-Abuse Shield, use this order:

1. `feat/anti-abuse-rate-limits`
2. `feat/anti-abuse-captcha-enforcement`
3. `feat/anti-abuse-device-fingerprint`
4. `test/anti-abuse-critical-path`
5. `chore/anti-abuse-docs-and-config`

Merge each branch independently after gates pass.

## PR Template Checklist

Include this in each PR description:

```markdown
## Branching Policy Checklist
- [ ] Branch name follows policy
- [ ] PR scope is single concern
- [ ] `./vendor/bin/pest` passes
- [ ] `./vendor/bin/pint` passes
- [ ] `./vendor/bin/phpstan analyse --level=5` passes
- [ ] CodeRabbit feedback addressed
- [ ] `How to Test` included
- [ ] Self-review complete
```

## Notes

- Existing process docs may still reference a `develop` branch model.
- This policy is the working baseline for new implementation unless superseded by an approved architecture/process decision.
