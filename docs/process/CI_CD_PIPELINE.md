# CI/CD Pipeline

This document defines the continuous integration and continuous deployment (CI/CD) pipeline for NidVite. It ensures code quality, automated testing, and safe deployment to production.

---

## Pipeline Overview

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│   Developer │───>│    GitHub   │───>│    CI/CD    │───>│   Railway   │
│   Machine   │    │    PR       │    │   Checks    │    │   Deploy    │
└─────────────┘    └─────────────┘    └─────────────┘    └─────────────┘
       │                  │                  │                  │
       ▼                  ▼                  ▼                  ▼
Local testing        Branch: feat/*       GitHub Actions    Production
(Pest, Pint,         → main               - Pest tests       Environment
 PHPStan)            Branch: hotfix/*     - Laravel Pint
                     → main               - PHPStan L5
                                          - CodeRabbit AI
```

---

## Stages

### Stage 1: Local Development (Pre-PR)

Before opening any PR, the developer must run:

```bash
# 1. Tests
./vendor/bin/pest

# 2. Formatting
./vendor/bin/pint

# 3. Static Analysis
./vendor/bin/phpstan analyse --level=5
```

**Gate**: All three must pass. If they fail, fix locally before pushing.

---

### Stage 2: Pull Request (GitHub)

#### PR to `main` (Primary Gate)

Triggered by: Opening a PR from `feat/*`, `fix/*`, `chore/*`, `test/*`, or `hotfix/*` → `main`

**Automated Checks:**
1. **GitHub Actions CI** runs:
   - `composer install`
   - `npm ci`
   - `./vendor/bin/pest`
   - `./vendor/bin/pint`
   - `./vendor/bin/phpstan analyse --level=5`
2. **CodeRabbit AI Review** runs:
   - PostGIS query safety
   - Laravel best practices
   - Type safety
   - PER coding style
   - Test coverage assessment

**Merge Requirements:**
- [ ] All GitHub Actions checks pass (green)
- [ ] CodeRabbit review completed
- [ ] All CodeRabbit `request_changes` feedback fixed and pushed
- [ ] Re-review requested after fixes (`@coderabbitai review`)
- [ ] Final CodeRabbit status is green (`SUCCESS`) or skip reason documented
- [ ] Author self-review completed
- [ ] PR description includes "How to Test"

**Merge Method**: Squash and Merge
- Condenses branch commits into a single clean commit on `main`

**Branch Protection Rules for `main`:**
```
✓ Require a pull request before merging
  ✓ Require approvals: 1
  ✓ Dismiss stale PR approvals when new commits are pushed
  ✓ Require review from Code Owners (optional)
✓ Require status checks to pass before merging
  ✓ GitHub Actions CI (Pest, Pint, PHPStan)
  ✓ CodeRabbit AI Review
✓ Require branches to be up to date before merging
✓ Restrict pushes that create files larger than 100MB
✓ Allow force pushes: No
✓ Allow deletions: No
```

### CodeRabbit Review and Fix Loop (Required)

After opening a PR, run this loop until green:

1. Check review status:

```bash
gh pr view <branch> --json statusCheckRollup
```

2. Read review comments:

```bash
gh pr view <branch> --json comments
```

3. Apply all actionable fixes.
4. Re-run local quality gates:

```bash
./vendor/bin/pest
./vendor/bin/pint
./vendor/bin/phpstan analyse --level=5
```

5. Commit and push review fixes:

```bash
git add .
git commit -m "fix(review): address CodeRabbit feedback"
git push
```

6. Trigger re-review:

```bash
gh pr comment <pr-number> --body "@coderabbitai review"
```

7. Merge only when:
- CodeRabbit status is `SUCCESS` (or documented skip reason)
- No unresolved `request_changes`
- CI checks are green

---

### Stage 3: Deployment (Railway)

Triggered by: Merge into `main`

**Railway Pipeline:**
1. **Build**: Railway detects push to `main`, builds the Docker image
2. **Pre-deploy**: Run `php artisan migrate --force`
3. **Deploy**: Start application containers
4. **Health Check**: Railway verifies the application responds to HTTP requests

**Environment Variables (Production):**
- `APP_ENV=production`
- `APP_DEBUG=false`
- `FILESYSTEM_DISK=r2`
- `QUEUE_CONNECTION=database`
- `RESEND_API_KEY`
- `MAPTILER_API_KEY`
- `NOCAPTCHA_SECRET`, `NOCAPTCHA_SITEKEY`

---

## Hotfix Pipeline

For critical production bugs requiring immediate deployment:

```
main ──branch──> hotfix/critical-fix ──PR──> main ──deploy──> Railway
```

1. Branch from `main`: `git checkout main && git checkout -b hotfix/critical-fix`
2. Apply minimal fix, test locally
3. Open PR to `main` with `[HOTFIX]` prefix in title
4. Complete required CodeRabbit review/fix/re-review loop
5. Expedited human review if needed
6. Merge to `main` → auto-deploy

---

## GitHub Actions Workflow File

Create `.github/workflows/ci.yml`:

```yaml
name: CI

on:
  pull_request:
    branches: [main]
  push:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      postgres:
        image: postgis/postgis:15-3.4
        env:
          POSTGRES_DB: nid_vite_test
          POSTGRES_USER: sail
          POSTGRES_PASSWORD: password
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
        ports:
          - 5432:5432

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, pgsql, zip
          coverage: none

      - name: Install Composer dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Install Node dependencies
        run: npm ci

      - name: Copy environment file
        run: cp .env.example .env

      - name: Generate application key
        run: php artisan key:generate

      - name: Run migrations
        env:
          DB_CONNECTION: pgsql
          DB_HOST: localhost
          DB_PORT: 5432
          DB_DATABASE: nid_vite_test
          DB_USERNAME: sail
          DB_PASSWORD: password
        run: php artisan migrate

      - name: Enable PostGIS
        env:
          DB_CONNECTION: pgsql
          DB_HOST: localhost
          DB_PORT: 5432
          DB_DATABASE: nid_vite_test
          DB_USERNAME: sail
          DB_PASSWORD: password
        run: |
          php artisan tinker --execute="DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');"

      - name: Run tests (Pest)
        env:
          DB_CONNECTION: pgsql
          DB_HOST: localhost
          DB_PORT: 5432
          DB_DATABASE: nid_vite_test
          DB_USERNAME: sail
          DB_PASSWORD: password
        run: ./vendor/bin/pest

      - name: Check formatting (Pint)
        run: ./vendor/bin/pint --test

      - name: Run static analysis (PHPStan)
        run: ./vendor/bin/phpstan analyse --level=5 --no-progress
```

---

## CI/CD Checklist

Before merging to `main`:
- [ ] Local tests pass (`pest`, `pint`, `phpstan`)
- [ ] PR opened with description + "How to Test"
- [ ] GitHub Actions CI passes
- [ ] CodeRabbit review completed
- [ ] CodeRabbit feedback fixed and pushed
- [ ] Re-review requested and final status is green
- [ ] Self-review completed
- [ ] 1 human reviewer approved
- [ ] Branch is up to date with `main`
- [ ] No breaking migrations without rollback plan

---

*This pipeline is a living document. Propose changes via PR with rationale.*
