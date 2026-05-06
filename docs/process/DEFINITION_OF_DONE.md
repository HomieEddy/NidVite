# Definition of Done

A feature is not complete until all items on this checklist are satisfied. This applies to every PR.

---

## 1. Test Coverage: Critical Path

We require **critical-path coverage**, not 100% line coverage.

**Definition of Critical Path:**
- The **happy path** (expected successful flow).
- The **two most likely failure modes** (validation errors, edge cases).

**Example for "Submit Report":**
```php
// Happy path
it('creates a report with valid data')->todo();

// Failure mode 1: outside Montreal
it('rejects reports outside the geofence')->todo();

// Failure mode 2: rate limit exceeded
it('blocks submission when rate limit is reached')->todo();
```

**Exclusions:**
- Blade views do not require unit tests (covered by Livewire component tests).
- Filament resource boilerplate (tables, forms) does not require separate tests if the underlying model/Action is tested.

---

## 2. Automated Quality Gates

All of the following must pass before a PR is opened:

```bash
# 1. Tests
./vendor/bin/pest
# Expected: 100% pass rate

# 2. Formatting
./vendor/bin/pint
# Expected: zero errors

# 3. Static Analysis
./vendor/bin/phpstan analyse --level=5
# Expected: zero errors
```

**CI Enforcement:** These commands run automatically on every PR via GitHub Actions. A failing check blocks merge.

## 3. AI Code Review (CodeRabbit)

- [ ] CodeRabbit has completed its review on the PR.
- [ ] All `request_changes` comments from CodeRabbit are resolved.
- [ ] CodeRabbit's PHPStan and Pint integrations report zero issues.

**Note:** CodeRabbit is configured in `.coderabbit.yaml`. It enforces Laravel best practices, PostGIS query safety, and type safety beyond what CI catches.

---

## 4. Type Safety

All new PHP methods must declare:
- Scalar type hints on parameters
- Return types

```php
// Good
public function findNearby(Point $location, float $radiusKm): Collection

// Bad
public function findNearby($location, $radiusKm)
```

Exceptions: Closure callbacks inside collection pipelines where the type is already inferred by PHPStan.

---

## 5. Filament Compliance (Dashboard Features)

If the feature touches the entrepreneur dashboard:

- [ ] `ReportResource` (or relevant resource) defines `form()`, `table()`, and `getPages()`.
- [ ] Form fields have validation rules matching the model's constraints.
- [ ] Table columns are sortable and searchable where appropriate.
- [ ] Actions (Edit, Delete, Bulk Delete) are authorized via policies.
- [ ] Map widget updates reflect the new data state.
- [ ] **State machine transitions are validated** (e.g., `pending` cannot jump to `repaired`).
- [ ] **RBAC policies enforce role-based access**.
- [ ] **Audit logging captures data changes**.

---

## 6. PWA Compliance (Citizen Features)

If the feature touches the citizen-facing PWA:

- [ ] Works on viewports >= 320px (responsive design).
- [ ] Works offline or degrades gracefully (e.g., form shows a message if offline).
- [ ] Touch targets are >= 44x44px.
- [ ] Form inputs have associated `<label>` elements.
- [ ] Images have `alt` text.

---

## 7. Security Compliance

- [ ] **Rate limiting** applied to new endpoints (both public `/api/*` and admin `/admin/*`).
- [ ] **Admin endpoints** have stricter rate limits (30 req/min).
- [ ] **Input validation** via Form Requests or Livewire rules.
- [ ] **Output escaping** in Blade (`{{ }}` not `{!! !!}` unless sanitized).
- [ ] **No `DB::raw()` without parameterization** — CodeRabbit enforces this.
- [ ] **XSS prevention**: Strip HTML from citizen inputs, escape output.
- [ ] **No secrets** committed to Git.
- [ ] **Policies** protect all Filament resources.

---

## 8. Migration Safety

Every new migration must include a reversible `down()` method:

```php
public function down(): void
{
    Schema::dropIfExists('new_table');
}
```

**Forbidden:** Migrations that drop columns without `down()` support, or that modify data without a rollback path.

---

## 9. State Machine Testing

If the feature involves report or job status transitions:

- [ ] **Valid transitions** are tested (e.g., `pending → scheduled`).
- [ ] **Invalid transitions** are blocked (e.g., `pending → repaired` fails).
- [ ] **Role-based transition permissions** tested (only Manager can schedule).
- [ ] **Side effects** verified (e.g., job completion decrements inventory).

**Example:**
```php
it('allows manager to schedule a report')->todo();
it('blocks service worker from scheduling')->todo();
it('prevents status regression from repaired to pending')->todo();
```

---

## 10. Translation Compliance

All features with user-facing text must be bilingual (FR/EN):

- [ ] **No hardcoded strings** in Blade, Livewire, or Filament (`{{ __('key') }}` required).
- [ ] **Keys added to `lang/fr.json`** (French first — primary/default language).
- [ ] **Keys added to `lang/en.json`** (English secondary).
- [ ] **Both files have identical key sets** (no missing translations).
- [ ] **Semantic dot-notation keys** used: `domain.component.element`.
- [ ] **Dynamic content** (database labels) has `_fr` and `_en` columns.
- [ ] **Dates localized**: `->locale(app()->getLocale())->isoFormat('LL')`.
- [ ] **Numbers localized**: `number_format()` with locale awareness.
- [ ] **Emails** respect `reports.preferred_locale` (default 'fr').

**Forbidden:**
- English text as translation keys
- Vague keys (`submit`, `title`)
- Missing French translation (FR is never optional)

---

## 11. Documentation

If the feature introduces a new architectural concept or changes an integration:

- [ ] Update the relevant ADR in `docs/adr/` (or create a new one).
- [ ] Update `TECH_STACK.md` if new dependencies are added.
- [ ] Update `SCHEMA_OVERVIEW.md` if the database changes.
- [ ] Update `SECURITY_PRIVACY.md` if new security controls are added.
- [ ] Update `TRANSLATION_GUIDE.md` if new translation patterns are introduced.

---

## 12. PR Hygiene

- [ ] Branch follows naming convention (`feature/*`, `bugfix/*`, `hotfix/*`).
- [ ] Feature branches target `develop`, not `main`.
- [ ] Commit messages follow Conventional Commits (`feat:`, `fix:`, `refactor:`, `test:`, `chore:`).
- [ ] PR description includes:
  - Summary of changes
  - "How to Test" section with step-by-step instructions
  - Screenshots (if UI changes)
- [ ] Self-review completed on GitHub diff before requesting review.

---

## 13. Performance (Soft Gate)

For features expected to handle >100 concurrent users:

- [ ] Database queries are inspectable via Debugbar. N+1 queries are eliminated.
- [ ] PostGIS queries use GIST indexes.
- [ ] Heavy operations (clustering, email sending) are dispatched to the queue.
- [ ] Response caching applied to read-heavy endpoints.

**MVP Note:** This is a soft gate. Performance optimization is required only if Debugbar shows query counts >10 per request.

---

## Final Checklist Template

Copy this into every PR description:

```markdown
## Definition of Done

### For PR to `develop` (Integration)
- [ ] Critical-path tests written and passing (happy path + 2 failures)
- [ ] `./vendor/bin/pest` passes
- [ ] `./vendor/bin/pint` passes
- [ ] `./vendor/bin/phpstan analyse --level=5` passes
- [ ] CodeRabbit AI review completed and all comments resolved
- [ ] Type hints and return types present on new methods
- [ ] `down()` method present on new migrations
- [ ] Security compliance items verified (rate limiting, input validation, XSS prevention)
- [ ] State machine transitions tested (if applicable)
- [ ] **Translation compliance verified** (no hardcoded strings, both FR/EN updated, French first)
- [ ] Self-review completed
- [ ] Documentation updated (if applicable)

### For PR to `main` (Release)
- [ ] All checks from `develop` branch are green
- [ ] CodeRabbit approved
- [ ] 1 human reviewer approved
- [ ] No breaking changes without rollback plan
- [ ] `develop` branch is up to date with `main`
```

---

*This document is a living record. Propose changes via PR with rationale.*
