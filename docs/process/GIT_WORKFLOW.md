# Versioning & Git Workflow

We utilize a **GitHub Flow variant with a `develop` branch**—optimized for safe continuous delivery.

---

## Branching Strategy

| Branch | Purpose | Long-lived? |
|--------|---------|-------------|
| `main` | The "Production-Ready" branch. Only fully tested, stable code. **Protected.** | Yes |
| `develop` | Integration branch. All feature/bugfix branches merge here first. **Never deleted.** | Yes |
| `feature/*` | New features (e.g., `feature/pothole-photo-upload`). | No |
| `bugfix/*` | Issue resolution (e.g., `bugfix/map-centering-issue`). | No |
| `hotfix/*` | Critical production fixes. Branch from `main`, merge back to both `main` and `develop`. | No |

### Branch Rules

- **`main`**: Protected. No direct pushes. Only accepts PRs from `develop` (or `hotfix/*` in emergencies). Requires passing CI, CodeRabbit approval, and 1 human review.
- **`develop`**: Protected. No direct pushes. Accepts PRs from `feature/*` and `bugfix/*` branches. Requires passing CI and CodeRabbit approval.
- **`develop` is never deleted**. It persists as the active integration branch throughout the project lifecycle.

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
   - CodeRabbit automatically reviews all PRs targeting `develop` and `main`.
   - Address all `request_changes` comments before merging.
   - CodeRabbit checks: PostGIS safety, Laravel best practices, type safety, and test coverage.
4. **Self-Review**: Before requesting a review or merging, the author must review their own diff on GitHub.

### CodeRabbit Configuration

CodeRabbit is configured via `.coderabbit.yaml` in the project root. Key settings:
- **Profile**: `assertive` — catches bugs, enforces standards, provides actionable feedback.
- **Auto-review**: Enabled for all non-draft PRs on `develop`, `main`, `feature/*`, `bugfix/*`, and `hotfix/*` branches.
- **Path-based rules**: Custom review instructions for `app/Actions/`, `app/Livewire/`, `app/Filament/`, `database/migrations/`, and `tests/`.
- **Tool integration**: PHPStan Level 5 and Laravel Pint are run automatically.

---

## Merge & Deployment Logic

### Two-Gate Merge Strategy

```
feature/* ──PR──> develop ──PR──> main ──Deploy──> Railway
```

**Gate 1: `feature/*` → `develop`**
- Squash and merge after local testing + CI + CodeRabbit pass.
- This is the "integration" gate.

**Gate 2: `develop` → `main`**
- Open a release PR when `develop` is stable.
- Requires: all CI green, CodeRabbit approved, and 1 human review.
- Merge method: **Merge Commit** (preserves feature history in `develop`, but `main` gets a clean merge commit).
- **Only `develop` and `hotfix/*` branches may target `main`.**

### Hotfix Exception

For critical production bugs:
1. Branch from `main`: `git checkout -b hotfix/critical-fix`
2. Fix, test, open PR targeting `main`.
3. After merge to `main`, immediately back-merge `main` into `develop`.

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

1. Create a feature branch from `develop`: `git checkout develop && git checkout -b feature/my-feature`
2. Make commits following Conventional Commits.
3. Ensure all checks pass locally (Pest, Pint, PHPStan).
4. Open a PR targeting `develop` with a clear description and "How to Test" section.
5. Self-review your diff on GitHub.
6. **Wait for CodeRabbit AI review** and address any `request_changes` comments.
7. Squash and merge into `develop`.
8. When ready to release, open a PR from `develop` → `main`.
9. After human review + CI + CodeRabbit, merge into `main`.
10. Railway auto-deploys.

---

*This workflow is a living document. Propose changes via PR with rationale.*
