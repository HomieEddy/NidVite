# Testing Analysis

> Mapped: 2026-05-06 | Focus: quality

## Test Framework

**Runner:**
- Pest 2.x (`pestphp/pest: ^2.0`) with Laravel plugin (`pestphp/pest-plugin-laravel: ^2.0`) and Faker plugin (`pestphp/pest-plugin-faker: ^2.0`)
- PHPUnit 10.5 underneath (Pest wraps PHPUnit)
- Config: `phpunit.xml` — two test suites: `Unit` and `Feature`

**Assertion Library:**
- Pest's `expect()` API (fluent chaining) is the primary style
- PHPUnit assertions used only in legacy scaffolded files (`tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php`)

**Base Test Case:**
- `tests/TestCase.php` — minimal, extends `Illuminate\Foundation\Testing\TestCase`, no custom helpers
- `tests/Pest.php` — binds `TestCase::class` to Feature suite via `uses(TestCase::class)->in('Feature')`

**Run Commands:**
```bash
./vendor/bin/pest                          # Run all tests
./vendor/bin/pest --colors=never           # CI mode (no colors)
./vendor/bin/pest --parallel               # Parallel execution (if needed)
./vendor/bin/pest --coverage               # Coverage report
./vendor/bin/pest tests/Feature            # Feature tests only
./vendor/bin/pest tests/Unit               # Unit tests only
```

## Test Inventory

### Feature Tests (10 files)

| File | Tests | Coverage Area | Key Assertions |
|------|-------|---------------|----------------|
| `AdminDashboardTest.php` | 2 | Dashboard widgets, expense column naming | `toBeInt()`, `toBeFloat()`, `toBeGreaterThan(0)`, `toHaveCount()`, `toBeFalse()` |
| `EmailNotificationTest.php` | 6 | Mail queue on status change, locale, null email | `Mail::assertQueued()`, `Mail::assertNothingQueued()`, `hasTo()`, `str_contains()` |
| `ExampleTest.php` | 1 | Scaffolded smoke test | `assertStatus(200)` |
| `GeofencingTest.php` | 5 | Montreal boundary contains(), geofence validation | `toBeTrue()`, `toBeFalse()`, `toThrow(ValidationException::class)` |
| `ModelsTest.php` | 6 | UUID auto-gen, PostGIS setLocation, status scope | `not->toBeNull()`, `toBeGreaterThan()`, `toBeLessThan()` |
| `RbacPolicyTest.php` | 14 | RBAC for Report, User, RepairJob, Expense policies | `toBeTrue()`, `toBeFalse()` — uses `->with()` data provider for role permutations |
| `ReportFormTest.php` | 2 | Public form display, categories | `assertStatus(200)`, `assertSee()`, `toBeGreaterThan(0)` |
| `ReportStateMachineTest.php` | 15 | Enum transitions, model transitionTo, activity log | `toBe()`, `toThrow(InvalidArgumentException::class)`, `not->toBeNull()` |
| `ReportTrackingTest.php` | 6 | UUID tracking page, 404, status labels | `assertStatus(200)`, `assertStatus(404)`, `assertSee()` |
| `ReverbNotificationTest.php` | 4 | Broadcast event name, payload, channel auth | `toBe()`, `toHaveKey()`, `toHaveCount()` |

**Total Feature tests: ~57 test cases**

### Unit Tests (1 file)

| File | Tests | Coverage Area | Key Assertions |
|------|-------|---------------|----------------|
| `ExampleTest.php` | 1 | Scaffolded smoke test | `$this->assertTrue(true)` |

**Total Unit tests: 1 (trivial scaffold)**

## Test Structure & Patterns

### Pest Syntax Style (Primary)
```php
// Setup: uses() + beforeEach()
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(ReportCategorySeeder::class);
});

// Test: it() with expect()
it('allows transition from received to verified', function () {
    $report = createReport(['status' => 'received']);
    $report->transitionTo('verified');
    expect($report->fresh()->status)->toBe('verified');
});
```

### PHPUnit Syntax Style (Legacy only)
```php
// Only in scaffolded ExampleTest.php files
class ExampleTest extends TestCase
{
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }
}
```
**Convention:** All new tests MUST use Pest syntax. CodeRabbit review config enforces this.

### describe() Grouping
Used in 3 files to organize related tests:
- `GeofencingTest.php`: `describe('MontrealBoundary', ...)`, `describe('Report geofence validation', ...)`, `describe('Report creation with geofence', ...)`
- `RbacPolicyTest.php`: `describe('ReportPolicy', ...)`, `describe('UserPolicy', ...)`, `describe('RepairJobPolicy', ...)`, `describe('ExpensePolicy', ...)`
- `ReportStateMachineTest.php`: `describe('ReportStatus enum', ...)`, `describe('Report state machine', ...)`, `describe('Report activity logging', ...)`

### Data Providers (Pest `->with()`)
Used in `RbacPolicyTest.php` for role permutation testing:
```php
it('allows all roles to view any report', function (string $roleSlug) {
    $user = User::factory()->create(['role_id' => $this->roles[$roleSlug]->id]);
    expect($user->can('viewAny', Report::class))->toBeTrue();
})->with(['admin', 'manager', 'service_worker', 'accountant', 'viewer']);
```

### Helper Functions
Defined in-file when needed:
```php
// ReportStateMachineTest.php
function createReport(array $attributes = []): Report
{
    /** @var Report $report */
    $report = Report::factory()->create($attributes);
    return $report;
}
```

### Database Setup Pattern
Every Feature test that touches the database uses:
```php
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(ReportCategorySeeder::class);
});
```
Specific tests add `MontrealBoundarySeeder::class` when testing geofencing.

### Mail Testing Pattern
```php
beforeEach(function () {
    Mail::fake();
});

it('sends email notification when report status changes', function () {
    $report = Report::factory()->create([...]);
    $report->transitionTo('verified');
    Mail::assertQueued(ReportStatusUpdated::class, function (ReportStatusUpdated $mail) use ($report) {
        return $mail->hasTo('citizen@example.com')
            && $mail->report->id === $report->id
            && $mail->oldStatus === 'received';
    });
});
```

### Error/Exception Testing Pattern
```php
it('prevents invalid transition from received to repaired', function () {
    $report = createReport(['status' => 'received']);
    expect(fn () => $report->transitionTo('repaired'))
        ->toThrow(InvalidArgumentException::class, "Cannot transition from 'received' to 'repaired'");
});
```

## Factory Usage

### Available Factories (5)
| Factory | File | Key Defaults |
|---------|------|--------------|
| `UserFactory` | `database/factories/UserFactory.php` | `name`, `email` (unique), `password` (hashed) |
| `ReportFactory` | `database/factories/ReportFactory.php` | `reporter_email`, `preferred_locale: 'fr'`, `status: 'received'`, `category_id` (nested factory), `is_spam: false` |
| `ReportCategoryFactory` | `database/factories/ReportCategoryFactory.php` | — |
| `RepairJobFactory` | `database/factories/RepairJobFactory.php` | `title`, `status: 'planned'`, `created_by: null` |
| `ExpenseFactory` | `database/factories/ExpenseFactory.php` | Calculates `subtotal`, `tax_rate` (0.14975 QST), `tax_amount`, `total` from `quantity` × `unit_cost` |

### Missing Factories
- `MontrealBoundary` — no factory (seeded via `MontrealBoundarySeeder`)
- `Role` — no factory (seeded via `RoleSeeder` with fixed slugs)
- `JobReport`, `JobWorker`, `Material`, `MaterialPurchase`, `Vendor` — no factories

### Factory Pattern
```php
// Nested factory for relationships
'category_id' => ReportCategory::factory(),

// Override defaults in tests
Report::factory()->create([
    'status' => 'in_progress',
    'reporter_email' => 'citizen@example.com',
]);
```

## Coverage Analysis

### Well-Covered Areas ✅

| Area | Tests | Depth |
|------|-------|-------|
| Report state machine | 15 tests | All valid transitions, all invalid transitions, terminal states, activity logging |
| RBAC policies | 14 tests | All 5 roles × all CRUD operations for 4 policy classes |
| Email notifications | 6 tests | Queue, locale, null email, rejection reason, ShouldQueue interface |
| Geofencing | 5 tests | Inside/outside Montreal, validation exception, boundary model |
| Report tracking page | 6 tests | UUID lookup, 404, status labels, rejection display, category |

### Partial Coverage ⚠️

| Area | What's tested | What's missing |
|------|---------------|----------------|
| Dashboard widgets | 2 tests verify queries return results | No widget rendering tests, no edge cases (empty data, zero expenses) |
| Report model | UUID gen, PostGIS location, status scope | `sendStatusNotification()` only tested via email tests; `validateGeofence()` only tested via GeofencingTest |
| Public report form | Display + categories | No form submission test, no validation test, no file upload test, no spam prevention test |
| Map controller | No direct test | Tested indirectly via `ReportFormTest` (categories) |

### Uncovered Areas ❌

| Area | Risk Level | Details |
|------|------------|---------|
| **Livewire ReportForm component** | 🔴 HIGH | The core citizen submission flow has no test. Form validation, file upload, geolocation capture, anti-spam — all untested |
| **MapController** | 🟡 MEDIUM | `geojson()` endpoint returns report data for map; no test for GeoJSON structure, filtering, or empty results |
| **RepairJob model** | 🟡 MEDIUM | No model tests (UUID gen, factory, scopes, relationships) |
| **Expense model** | 🟡 MEDIUM | Only tested indirectly via AdminDashboardTest column name check |
| **User model** | 🟡 MEDIUM | UUID gen tested, but 2FA, login, locale preferences untested |
| **SetLocale middleware** | 🟡 MEDIUM | Session-based locale switching not tested |
| **Broadcasting channels** | 🟡 MEDIUM | `routes/channels.php` authorization only tested via `isAdmin()` check in ReverbNotificationTest, not actual channel auth |
| **MontrealBoundary model** | 🟢 LOW | Tested via GeofencingTest, but no unit test for the `contains()` method in isolation |
| **ExifStripper service** | 🟡 MEDIUM | No tests at all for EXIF metadata stripping |
| **ReportResource Filament** | 🟢 LOW | Admin CRUD operations untested (common for Filament, policies are tested separately) |
| **Rate limiting** | 🟡 MEDIUM | `throttle:60,1` on API lookup not tested |
| **Vendor model** | 🟢 LOW | No factory, no tests |
| **Material/MaterialPurchase** | 🟢 LOW | No factory, no tests |
| **Locale switch route** | 🟢 LOW | `/locale/{locale}` route not tested |

## Test Quality Observations

### Strengths
1. **Pest-first approach:** All domain tests use Pest `it()` + `expect()` syntax consistently
2. **Good use of `describe()` blocks:** Logical grouping makes test output readable
3. **Data providers for RBAC:** Role permutations tested efficiently with `->with()`
4. **Specific error messages:** `toThrow()` assertions check exact exception messages
5. **Mail faking:** Proper use of `Mail::fake()` + `Mail::assertQueued()` with closure inspection
6. **Factory-driven:** Tests use factories with targeted overrides, not manual DB inserts (except ModelsTest line 48 which uses raw `DB::selectOne`)

### Weaknesses
1. **No unit tests at all:** Only the scaffolded `ExampleTest.php` exists. All business logic (enum methods, model scopes, service classes) is tested through Feature tests with database.
2. **Raw SQL in tests:** `ModelsTest.php` line 48 uses `DB::selectOne()` to verify PostGIS — acceptable for spatial testing but should be isolated.
3. **No HTTP tests for protected routes:** No test authenticates as admin/user and verifies Filament panel access or API authorization.
4. **Missing `reporter_email` nullable test:** The migration `make_reporter_email_nullable.php` exists but only `EmailNotificationTest` covers `null` email (for mail skip). No test for creating a report without email.
5. **No negative geofence test for report creation:** `GeofencingTest` tests `validateGeofence()` but doesn't test that report creation via HTTP rejects out-of-bounds submissions.
6. **AdminDashboardTest tests widget queries, not widgets:** It replicates the query logic from widgets rather than testing the actual widget output or Filament rendering.

## CI Test Pipeline

### GitHub Actions Workflow (`.github/workflows/ci.yml`)

**Job 1: Code Quality** (runs on push/PR to main, develop)
- PHP 8.2 + PostgreSQL 15 with PostGIS 3.4
- Steps: checkout → setup PHP → composer install → key generate → PostGIS enable → migrate
- Checks: `./vendor/bin/pint --test` + `./vendor/bin/phpstan analyse --configuration=phpstan.neon --no-progress --memory-limit=1G`
- No test execution in this job

**Job 2: Tests** (runs on push/PR to main, develop)
- Same PHP + PostGIS setup
- Additional: Node.js 20, `npm ci`, `npm run build`
- Runs: `./vendor/bin/pest --colors=never`
- Coverage: not collected (`coverage: none` in setup-php)

**Concurrency:** `ci-${{ github.ref }}` — cancel in-progress on same branch

**Missing from CI:**
- No code coverage report/upload
- No Dusk/browser tests
- No separate job for different test suites
- No artifact upload for test results

## Static Analysis

**PHPStan via Larastan:**
- Level: **5** (out of 9)
- Paths scanned: `app/`, `tests/`
- Ignored errors: `Undefined variable: $this` (Pest compatibility)
- Inline suppressions used sparingly:
  - `@phpstan-ignore property.notFound` — for PostGIS raw select columns (latitude/longitude from `selectRaw`)
  - `@phpstan-ignore identical.alwaysFalse` — for null check after `tryFrom()`

**Run:**
```bash
./vendor/bin/phpstan analyse --configuration=phpstan.neon --no-progress --memory-limit=1G
```

## Testing Gaps & Recommendations

### Priority 1 — Critical Path (No Tests)

1. **Livewire ReportForm submission flow**
   - Test valid report submission (all fields)
   - Test required field validation
   - Test geolocation capture + geofence validation on submit
   - Test photo upload + EXIF stripping
   - Test anti-spam (honeypot, recaptcha)
   - Test duplicate submission prevention
   - Files affected: `app/Livewire/ReportForm.php` (not yet in codebase), `resources/views/report.blade.php`

2. **MapController GeoJSON endpoint**
   - Test `/api/reports/geojson` returns valid FeatureCollection
   - Test filtered results (no spam, no rejected)
   - Test empty result set
   - File: `app/Http/Controllers/MapController.php`

### Priority 2 — Model Unit Tests

3. **Report model unit tests**
   - `canTransitionTo()` logic
   - `isTerminal()` logic
   - `scopeNear()` spatial query
   - `validateGeofence()` static method
   - `setLocation()` PostGIS write
   - File: `app/Models/Report.php`

4. **ReportStatus enum unit tests** (currently tested via Feature suite)
   - Extract enum-only tests to `tests/Unit/ReportStatusTest.php`
   - `values()`, `transitions()`, `canTransitionTo()`, `isTerminal()`

5. **ExifStripper service unit test**
   - Test EXIF data is stripped from JPEG
   - Test unsupported file types throw exception
   - File: `app/Services/ExifStripper.php`

### Priority 3 — HTTP Integration Tests

6. **Authenticated admin tests**
   - Test Filament panel access (admin vs viewer)
   - Test report CRUD operations via admin panel
   - Test policy enforcement at HTTP level

7. **Rate limiting test**
   - Test `/api/reports/{uuid}/lookup` respects `throttle:60,1`
   - Test 429 response after exceeding limit

8. **Locale switching test**
   - Test `/locale/fr` sets session and redirects
   - Test invalid locale is ignored
   - File: `routes/web.php`

### Priority 4 — Edge Cases & Robustness

9. **Dashboard widget edge cases**
   - Test with zero reports/expenses (division by zero, null averages)
   - Test ReportsChart with no data in last 30 days
   - File: `app/Filament/Widgets/ReportsOverview.php`

10. **Report tracking edge cases**
    - Test tracking for report with no category
    - Test tracking for report with no address
    - Test API lookup returns correct progress percentage
    - File: `app/Http/Controllers/ReportTrackingController.php`

---

*Testing analysis: 2026-05-06*
