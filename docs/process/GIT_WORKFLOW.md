# Versioning & Git Workflow

We use trunk-based development with one long-lived branch: `main`.

---

## Branching Strategy

| Branch | Purpose | Long-lived? |
|--------|---------|-------------|
| `main` | Production-ready branch. Only fully tested, stable code. Protected. | Yes |
| `feat/*` | New features (e.g., `feat/anti-abuse-rate-limits`). | No |
| `fix/*` | Bug fixes (e.g., `fix/captcha-validation-edge-case`). | No |
| `chore/*` | Maintenance/config/docs updates. | No |
| `test/*` | Test-only changes. | No |
| `hotfix/*` | Critical production fixes branched from `main`. | No |

### Branch Rules

- **`main`**: Protected. No direct pushes. Accepts PRs from short-lived branches only.
- Pull request required.
- Branch must be up to date before merge.
- Force push disabled.
- Deletion disabled.
- Required checks: Pest, Pint, PHPStan Level 5, CodeRabbit review, and self-review.

---

## Conventional Commits

Commit messages must follow the standard format to make the changelog readable:

| Type | Description |
|------|-------------|
| `feat` | A new feature for the user. |
| `fix` | A bug fix. |
| `refactor` | Code changes that neither fix a bug nor add a feature. |
| `test` | Adding missing tests or correcting existing tests. |
| `chore` | Updating build tasks, package manager configs, etc. |

**Example:**
```
feat(map): add postgis clustering for high-density reports
```

---

## Pull Request (PR) Rules

1. **Description**: Every PR must include a summary of changes and a "How to Test" section.
2. **Automated Checks**:
   - Pest tests must pass (100% pass rate).
   - Laravel Pint must return zero formatting errors.
   - Static Analysis: Run `phpstan` (Level 5+) to ensure type safety.
3. **AI Code Review (CodeRabbit)**:
   - CodeRabbit reviews every non-draft PR.
   - All `request_changes` comments must be fixed before merge.
   - After fixes are pushed, trigger re-review and wait for green status.
   - CodeRabbit checks PostGIS safety, Laravel best practices, type safety, and test coverage.
4. **Self-Review**: Before requesting a review or merging, the author must review their own diff on GitHub.

### CodeRabbit Configuration

CodeRabbit is configured via `.coderabbit.yaml` in the project root. Key settings:
- **Profile**: `assertive` — catches bugs, enforces standards, provides actionable feedback.
- **Auto-review**: Enabled for all non-draft PRs on `main`, `feat/*`, `fix/*`, `chore/*`, `test/*`, and `hotfix/*` branches.
- **Path-based rules**: Custom review instructions for `app/Actions/`, `app/Livewire/`, `app/Filament/`, `database/migrations/`, and `tests/`.
- **Tool integration**: PHPStan Level 5 and Laravel Pint are run automatically.

### CodeRabbit CLI Workflow

After pushing a branch and opening a PR, use this review and remediation loop:

**1. Check review status:**
```bash
gh pr view <branch> --json statusCheckRollup
```
Look for `state: "SUCCESS"` (passed), `PENDING` (processing), or `FAILURE` (issues found).

**2. Read review comments:**
```bash
gh pr view <branch> --json comments
```
Address all `request_changes` comments before merging. Comments from `coderabbitai` are auto-generated.

**3. Apply fixes:**
Fix every actionable CodeRabbit issue in the branch.

**4. Re-run local quality gates after fixes:**
```bash
./vendor/bin/pest
./vendor/bin/pint
./vendor/bin/phpstan analyse --level=5
```

**5. Commit and push fixes:**
```bash
git add .
git commit -m "fix(review): address CodeRabbit feedback"
git push
```

**6. Fix configuration errors (if present):**
If CodeRabbit reports a `.coderabbit.yaml` parsing error:
- Read the error in the PR comment (e.g., `Expected 'default' | '0' | ... received number`)
- Fix the config value type (e.g., quote numbers: `level: "5"` instead of `level: 5`)
- Commit and push the fix

**7. Trigger re-review:**
After fixing issues or config errors:
```bash
gh pr comment <pr-number> --body "@coderabbitai review"
```

**8. Handle skipped reviews:**
CodeRabbit skips reviews if a PR exceeds **150 files**. This typically happens with initial scaffold PRs. For large PRs:
- Ensure local checks pass (Pest, Pint, PHPStan)
- Perform manual self-review of critical paths
- Document the skip reason in the PR description
- Future feature PRs will be smaller and within limits

**9. Merge criteria:**
- CodeRabbit status must be `SUCCESS` (or documented skip reason)
- No unresolved `request_changes` comments
- All local checks (Pest, Pint, PHPStan) must pass

---

## Merge & Deployment Logic

### Single-Gate Merge Strategy

```
feat/*|fix/*|chore/*|test/*|hotfix/* ──PR──> main ──Deploy──> Railway
```

Each PR to `main` requires:

- All CI checks green
- CodeRabbit review completed and addressed
- One human approval (recommended)
- Self-review completed

Merge method: **Squash and merge**.

### Hotfix Exception

For critical production bugs:
1. Branch from `main`: `git checkout -b hotfix/critical-fix`
2. Fix, test, open PR targeting `main`.
3. Complete CodeRabbit review-and-fix loop.
4. Merge into `main`.

### Deployment

Merging into `main` triggers an automatic build on **Railway**.

### Database Migrations

Migrations are run automatically via Railway's predeploy command. **Always include a `down` method in your migrations.**

```php
public function down(): void
{
    Schema::dropIfExists('reports');
}
```

---

## Workflow Summary

1. Create a short-lived branch from `main`: `git checkout main && git checkout -b feat/my-feature`
2. Make commits following Conventional Commits.
3. Ensure all checks pass locally (Pest, Pint, PHPStan).
4. Open a PR targeting `main` with a clear description and "How to Test" section.
5. Self-review your diff on GitHub.
6. Check CodeRabbit status via CLI: `gh pr view <branch> --json statusCheckRollup`.
7. Address CodeRabbit feedback, commit fixes, rerun local checks, and push updates.
8. Trigger re-review with `@coderabbitai review`.
9. Merge only when CodeRabbit is green and all checks pass.
10. Railway auto-deploys after merge to `main`.

---

*This workflow is a living document. Propose changes via PR with rationale.*
