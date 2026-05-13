# Retroactive CodeRabbit Fix Sweep

## Goal

Run a repeatable, codebase-wide remediation cycle in a dedicated branch that catches:

- unresolved review debt from previously merged PRs,
- regressions not covered by current PR scope,
- quality/security issues hidden by environment drift.

## Branch Strategy

Create one dedicated branch from develop:

```bash
git checkout develop
git pull
git checkout -b chore/retroactive-coderabbit-fix-sweep
```

Use small commits by domain (auth, geofence, notifications, etc.), then open one umbrella PR back to develop.

## 1) Collect Historical Review Debt

Use GitHub PR search to list merged PRs and inspect unresolved conversations or bot comments.

Automated option (recommended in this repo):

```powershell
pwsh -NoProfile -ExecutionPolicy Bypass -File scripts/audit-pr-review-debt.ps1
```

This generates:

- `docs/process/RETROACTIVE_CODERABBIT_DEBT_REPORT.md` (ranked unresolved thread counts)
- `docs/process/RETROACTIVE_CODERABBIT_DEBT_DETAILS.md` (path/line/comment excerpts)

Suggested manual scope:

- Last 60 to 120 merged PRs into develop and main.
- Focus files under app, config, routes, tests, and database.
- Prioritize comments about security, state transitions, authorization, validation, query safety, and caching.

If GitHub CLI is available, start with:

```bash
gh pr list --state merged --base develop --limit 120
gh pr list --state merged --base main --limit 120
```

Then inspect each high-risk PR:

```bash
gh pr view <PR_NUMBER> --comments
```

Build a checklist grouped by module before writing fixes.

## 2) Run Baseline Quality Gates

Run the same guards as CI first:

```bash
composer validate --no-check-publish
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --configuration=phpstan.neon --no-progress --memory-limit=1G
composer quality:phpstan-stage6
composer quality:rector-dry-run
php artisan test tests/Feature/QualityGateDependencyHygieneTest.php tests/Feature/ArchitectureBoundaryGuardTest.php
php artisan security:check-headers
```

## 3) Run Full Regression Sweep in a Correct Test Environment

Most false-negative/false-positive outcomes come from DB environment mismatch.

For local non-Sail runs, force DB host to localhost during sweep:

```bash
DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=5432 DB_DATABASE=testing DB_USERNAME=sail DB_PASSWORD=password ./vendor/bin/pest --colors=never
```

Windows PowerShell equivalent:

```powershell
$env:DB_CONNECTION='pgsql'
$env:DB_HOST='127.0.0.1'
$env:DB_PORT='5432'
$env:DB_DATABASE='testing'
$env:DB_USERNAME='sail'
$env:DB_PASSWORD='password'
./vendor/bin/pest --colors=never
```

If Postgres/PostGIS is not running locally, start it first (Docker or local service), then rerun.

## 4) Fix in Priority Order

Apply fixes in this order:

1. Security/authz/authn issues (RBAC, access control, lockout, session hardening).
2. Data integrity/state machine issues (status transitions, deduplication, validation).
3. Query correctness and geospatial logic (PostGIS, coordinate handling, filtering).
4. Notification and async workflow consistency.
5. UI/admin contract regressions and response payload stability.

For each cluster:

- add or update tests first,
- implement minimal fixes,
- rerun targeted tests,
- rerun full gate set before commit.

## 5) Definition of Done

A sweep is complete when all are true:

- all agreed historical review checklist items are marked done or explicitly waived,
- CI quality job passes,
- CI tests job passes,
- no unresolved high-severity review comments remain in the umbrella PR.

## 6) Recommended PR Structure

In the umbrella PR description include:

- historical-review checklist (with links to source PRs/comments),
- grouped commits by domain,
- explicit risk notes and rollback plan,
- before/after test and static-analysis summary.
