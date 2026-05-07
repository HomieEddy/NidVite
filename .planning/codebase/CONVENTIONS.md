# Coding Conventions

> Mapped: 2026-05-06 | Focus: quality

## General Style

**EditorConfig (` .editorconfig`):**
- Charset: UTF-8
- End of line: LF
- Indent: 4 spaces (PHP), 2 spaces (YAML)
- Insert final newline: yes
- Trim trailing whitespace: yes (except `.md`)

**Line Length:** No explicit limit enforced, but Pint PER compliance keeps lines reasonable.

**Enforced by:** Laravel Pint with PER (PHP Evolving Standards) preset — CI blocks on style violations via `./vendor/bin/pint --test`.

## PHP / Laravel Conventions

### Namespace & Directory Layout
- App namespace: `App\` → `app/`
- Models: `App\Models\` → `app/Models/` (Singular class names: `Report`, `User`, `Expense`)
- Policies: `App\Policies\` → `app/Policies/` (Model name + "Policy": `ReportPolicy`)
- Enums: `App\Enums\` → `app/Enums/` (Singular: `ReportStatus`)
- Events: `App\Events\` → `app/Events/` (Past tense: `ReportCreated`)
- Mail: `App\Mail\` → `app/Mail/` (Descriptive: `ReportStatusUpdated`)
- Services: `App\Services\` → `app/Services/` (Noun: `ExifStripper`)
- Controllers: `App\Http\Controllers\` → `app/Http/Controllers/`
- Factories: `Database\Factories\` → `database/factories/`
- Seeders: `Database\Seeders\` → `database/seeders/`

### Model Conventions
- **Auto-UUID via `booted()` hook:**
  ```php
  protected static function booted(): void
  {
      static::creating(function (Report $report) {
          $report->uuid ??= (string) Str::uuid();
      });
  }
  ```
  Files: `app/Models/Report.php`, `app/Models/User.php`, `app/Models/RepairJob.php`

- **Mass assignment protection:** Use `$fillable` (never `$guarded`). Example: `app/Models/Report.php` line 36–56.

- **Casts:** Use `$casts` property or `casts()` method (User model uses `casts()` method — Laravel 11 convention). Both patterns coexist:
  ```php
  // Report.php — property style
  protected $casts = [
      'geofence_passed' => 'boolean',
      'completed_at' => 'datetime',
  ];

  // User.php — method style (Laravel 11 preferred)
  protected function casts(): array
  {
      return [
          'password' => 'hashed',
          'is_active' => 'boolean',
      ];
  }
  ```

- **Relationship methods:** Always have return type hints. Use BelongsTo, HasMany, BelongsToMany.
  ```php
  public function category(): BelongsTo
  {
      return $this->belongsTo(ReportCategory::class, 'category_id');
  }
  ```

- **Scopes:** Prefix with `scope`, type-hint Builder, return Builder.
  ```php
  public function scopeNear(Builder $query, float $latitude, float $longitude, int $radiusMeters = 1000): Builder
  ```

- **Soft deletes:** Used on primary domain models (`Report` uses `SoftDeletes` trait).

- **Activity logging:** Models that need audit trails use `LogsActivity` trait from Spatie, with `getActivitylogOptions()` method.
  File: `app/Models/Report.php` lines 68–74.

- **Media handling:** Models with file uploads implement `HasMedia`, use `InteractsWithMedia`, define `registerMediaCollections()`.
  File: `app/Models/Report.php` lines 76–80.

### Controller Conventions
- Thin controllers — business logic in models, actions, or services.
- Type-hinted return types: `View`, `JsonResponse`.
- Use route model binding (`whereUuid` constraint).
  ```php
  public function show(string $uuid): View
  ```
  Files: `app/Http/Controllers/MapController.php`, `app/Http/Controllers/ReportTrackingController.php`

### Enum Conventions
- Backed enums (`string`) in `app/Enums/`.
- PascalCase case names, lowercase string values:
  ```php
  enum ReportStatus: string
  {
      case Received = 'received';
      case InProgress = 'in_progress';
  }
  ```
- Include business logic methods: `transitions()`, `canTransitionTo()`, `isTerminal()`.
  File: `app/Enums/ReportStatus.php`

### Policy Conventions
- All methods return `bool`.
- Order: `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`.
- Role checks use User helper methods (`isAdmin()`, `isManager()`, etc.) — never raw role slug comparisons.
  ```php
  public function create(User $user): bool
  {
      return $user->isAdmin() || $user->isManager();
  }
  ```
  Files: `app/Policies/ReportPolicy.php`, `app/Policies/UserPolicy.php`, `app/Policies/ExpensePolicy.php`, `app/Policies/RepairJobPolicy.php`

### Mail Conventions
- Mailables implement `ShouldQueue` for async delivery.
- Constructor promotes public properties (`public Report $report`).
- Locale-aware subject and content using `__()` with explicit `$locale` parameter.
  ```php
  $locale = $this->report->preferred_locale ?? 'fr';
  ```
  File: `app/Mail/ReportStatusUpdated.php`

### Event / Broadcasting Conventions
- Events implement `ShouldBroadcastNow` for real-time admin notifications.
- Define `broadcastOn()`, `broadcastAs()`, `broadcastWith()` with return type hints.
  File: `app/Events/ReportCreated.php`

### Service Class Conventions
- Stateless services with static methods when no state is needed.
  File: `app/Services/ExifStripper.php`

## Filament Conventions

### Resource Structure (Filament v5)
Resources are decomposed into separate class files under `app/Filament/Resources/{ResourceName}/`:
```
ReportResource.php          # Main resource (model, icon, pages, query)
├── Schemas/ReportForm.php  # Form schema (configure static method)
├── Tables/ReportsTable.php # Table schema (configure static method)
└── Pages/
    ├── ListReports.php     # List page
    ├── CreateReport.php    # Create page
    └── EditReport.php      # Edit page
```
Files: `app/Filament/Resources/Reports/`

- Form and Table logic extracted into dedicated `Schemas/` and `Tables/` classes with `configure()` static method pattern.
- Widget classes live in `app/Filament/Widgets/` with descriptive names: `ReportsOverview`, `ReportsChart`, `ReportsMap`, `ReportsByNeighborhood`.
- All Filament labels/descriptions use `__('key')` for i18n compliance.
- Widgets use private helper methods for query logic (`getOpenReportsCount()`, etc.).

**Anti-pattern note:** The `ReportForm.php` Filament form schema currently has raw English strings for labels (e.g., `->label('UUID')`, `->label('Report')`) instead of `->label(__('key'))`. This should be fixed.

## Blade / Frontend Conventions

### Template Organization
- Public citizen views: `resources/views/` root (`report.blade.php`, `tracking.blade.php`, `map.blade.php`, `welcome.blade.php`)
- Email templates: `resources/views/emails/` (`report-status-updated.blade.php`)
- Layouts: `resources/views/layouts/` (`citizen` layout for PWA views)
- Components: `resources/views/components/`
- Filament modals: `resources/views/filament/modals/`

### Template Patterns
- **Layout inheritance:** `@extends('layouts.citizen')` + `@section('content')` + `@section('title', ...)`
- **Asset injection:** `@push('styles')` and `@push('scripts')` for page-specific CSS/JS
- **Leaflet maps:** CDN-hosted with SRI integrity hashes
- **i18n in Blade:** Always `{{ __('key') }}`, never hardcoded user-facing text
- **Tailwind CSS:** Utility-first, amber-600 as primary accent color, responsive with `max-w-3xl mx-auto`
- **PWA touch targets:** `.btn-touch` class for minimum 44px touch areas

### Email Template Pattern
- Markdown Mailables using `<x-mail::message>`, `<x-mail::panel>`, `<x-mail::button>`
- Locale passed as `$locale` variable, all `__()` calls include `$locale` parameter:
  ```blade
  {{ __('email.status_updated.greeting', ['uuid' => $report->uuid], $locale) }}
  ```
  File: `resources/views/emails/report-status-updated.blade.php`

## Database Conventions

### Migration Naming
- Original schema: `YYYY_MM_DD_HHMMSS_create_{table}_table.php`
- Alterations: `YYYY_MM_DD_HHMMSS_{descriptive_action}.php`
- Examples: `fix_reports_status_constraint.php`, `remove_clutter_from_reports_table.php`, `make_reporter_email_nullable.php`

### Column Naming
- Foreign keys: `{related}_id` (e.g., `category_id`, `role_id`, `created_by`)
- Boolean flags: `is_{property}` (e.g., `is_spam`, `is_active`, `geofence_passed`)
- Timestamps: `{event}_at` (e.g., `completed_at`, `first_scheduled_at`, `expires_at`)
- UUIDs: `uuid` (auto-generated via `booted()`)
- Locale: `preferred_locale` (5-char string, defaults to `'fr'`)
- Monetary: `subtotal`, `tax_rate`, `tax_amount`, `total` (not `amount`)

### Index Naming
- Laravel default: `{table}_{column}_index`
- Custom spatial: `idx_{table}_{column}_gist` (PostGIS GIST indexes)
- Composite: `idx_{table}_{col1}_{col2}`

### Foreign Key Conventions
- `->references('id')->on('{table}')->nullOnDelete()` for optional relationships
- `onDelete` behavior specified explicitly

### PostGIS Patterns
- Geography columns added via raw `DB::statement('ALTER TABLE ...')` after Schema builder
- GIST indexes created manually: `DB::statement('CREATE INDEX ... USING GIST(...)')`
- Point creation: `ST_SetSRID(ST_MakePoint(lng, lat), 4326)::geography` (note: longitude first in PostGIS)
- Reading points: `ST_Y(location::geometry) as lat, ST_X(location::geometry) as lng`

### Migrations: down() Method
- All migrations must have reversible `down()` methods (CodeRabbit enforces this).
- Schema create → `Schema::dropIfExists()` in down.
- Column drops → re-add columns in down.

## Internationalization

### Approach
- **French-first:** Default locale is `fr`. All `preferred_locale` defaults to `'fr'`.
- **Two languages:** `lang/fr/` and `lang/en/` with identical key structures.
- **Translation files:** Domain-based PHP files (`report.php`, `tracking.php`, `email.php`, `map.php`, `dashboard.php`).

### Key Patterns
- **Dot notation for nesting:** `report.status.received`, `report.validation.outside_montreal`, `email.status_updated.subject`
- **Parameterized keys:** `__('email.status_updated.subject', ['status' => ...])`
- **Locale-aware calls:** `__('key', [], $locale)` — explicit locale override when user preference differs from app locale

### Translation File Structure (per domain file)
```php
return [
    'title' => 'Signaler un nid-de-poule',     // top-level key
    'status' => [                                 // nested group
        'received' => 'Reçu',
    ],
    'validation' => [                             // nested group
        'outside_montreal' => 'Cet emplacement...',
    ],
];
```

### Anti-pattern in tracking.php
- `lang/fr/tracking.php` uses French text as keys (e.g., `'Suivi de signalement' => 'Suivi de signalement'`, `'Numéro' => 'Numéro'`). This is fragile — English key lookups will fail. Should use semantic dot-notation keys like `tracking.title`, `tracking.number`.
  Files: `lang/fr/tracking.php`, `lang/en/tracking.php`

## API / Route Conventions

### Route Naming
- Public citizen routes: French URLs (`/signaler`, `/suivi/{uuid}`, `/carte`)
- API routes: English namespacing (`/api/reports/geojson`, `/api/reports/{uuid}/lookup`)
- Named routes: dot notation (`report.create`, `report.tracking`, `map.public`, `api.reports.geojson`, `locale.switch`)
- Route file: `routes/web.php` only (no `api.php` or `console.php usage`)

### Route Patterns
- UUID constraint: `->whereUuid('uuid')`
- Rate limiting: `->middleware('throttle:60,1')` on API lookup route
- Controller routes: `Route::get('/path', [Controller::class, 'method'])->name('name')`
- Closure routes: Used for simple views (`/signaler`, `/`, `/locale/{locale}`)

### Broadcasting Channels
- Defined in `routes/channels.php`
- Private channels for admin: `Broadcast::channel('admin.reports', ...)` with role check

## Git & CI Conventions

### Branch Strategy
- Main branches: `main`, `develop`
- Feature branches: `feature/*`
- Bugfix branches: `bugfix/*`
- Hotfix branches: `hotfix/*`

### CI Pipeline (`.github/workflows/ci.yml`)
- **Two parallel jobs:** `quality` and `tests`
- **Quality job:** Pint check + PHPStan analysis (no tests)
- **Tests job:** Full Pest suite with PostGIS-enabled PostgreSQL
- **Database:** `postgis/postgis:15-3.4` Docker image for CI
- **PHP version:** 8.2
- **Node:** 20 (for `npm run build`)
- **Concurrency:** Cancel in-progress runs on same branch

### CodeRabbit Review (`.coderabbit.yaml`)
- Profile: **assertive** (strict reviews)
- Auto-review enabled on develop, main, feature/*, bugfix/*, hotfix/*
- Path-specific instructions for Actions, Livewire, Filament, Models, Policies, Enums, Migrations, Tests, Routes, Blade, Lang
- Tools: PHPStan level 5, Pint enabled

### Static Analysis
- **PHPStan Level 5** via Larastan (`nunomaduro/larastan`)
- Config: `phpstan.neon` — scans `app/` and `tests/`
- One ignored error: `Undefined variable: $this` (Pest test files)
- PHPStan suppressions inline: `@phpstan-ignore property.notFound` for PostGIS raw select columns, `@phpstan-ignore identical.alwaysFalse` for null checks

## Anti-patterns Detected

### 1. French Text as Translation Keys
- **Files:** `lang/fr/tracking.php`, `lang/en/tracking.php`
- **Issue:** Keys like `'Numéro' => 'Number'` instead of semantic keys like `'number' => 'Number'`. This means `__('tracking.Numéro')` is the lookup, which is fragile and unsearchable.
- **Fix:** Convert to dot-notation semantic keys.

### 2. Filament Form Labels Not Translated
- **File:** `app/Filament/Resources/Reports/Schemas/ReportForm.php`
- **Issue:** Form field labels are raw English strings (`->label('UUID')`, `->label('Report')`) instead of `->label(__('key'))`.
- **Fix:** Add translation keys and use `->label(__('report.field.uuid'))` pattern.

### 3. Inline PostGIS Queries in Controllers
- **Files:** `app/Http/Controllers/MapController.php`, `app/Http/Controllers/ReportTrackingController.php`, `app/Filament/Resources/Reports/Tables/ReportsTable.php`
- **Issue:** Raw `DB::selectOne('SELECT ST_Y(...)')` queries duplicated across controllers and table columns instead of being encapsulated in model methods or scopes.
- **Fix:** Add a `getLocationAttribute()` accessor or `getCoordinates()` method on `Report`.

### 4. Hardcoded Route Logic in web.php Root
- **File:** `routes/web.php` lines 8–26
- **Issue:** The home page route contains substantial query logic (stats calculations, locale handling) in an inline closure instead of a controller.
- **Fix:** Extract to a `HomeController` or `WelcomeController`.

### 5. Mixed Cast Styles Across Models
- **Issue:** `Report` uses `$casts` property array, `User` uses `casts()` method. Should be consistent.
- **Fix:** Adopt `casts()` method (Laravel 11 convention) across all models.

### 6. No Livewire Directory Yet
- **Issue:** `report.blade.php` uses `<livewire:report-form />` but `app/Livewire/` directory does not exist (likely in a pending phase or built elsewhere).
- **Impact:** Tests like `ReportFormTest.php` reference `/signaler` which depends on this component.

## Style Enforcement

| Tool | Config | Purpose |
|------|--------|---------|
| Laravel Pint | Default PER preset | Code formatting (CI: `./vendor/bin/pint --test`) |
| PHPStan / Larastan | `phpstan.neon` level 5 | Type safety + static analysis |
| EditorConfig | `.editorconfig` | Indent, charset, line endings |
| CodeRabbit | `.coderabbit.yaml` assertive | AI-assisted PR review |
| PHPUnit/Pest | `phpunit.xml` | Test runner configuration |

**Run locally:**
```bash
./vendor/bin/pint                    # Auto-fix style
./vendor/bin/pint --test             # Check only (CI mode)
./vendor/bin/phpstan analyse         # Static analysis
./vendor/bin/pest                    # Run tests
```

---

*Convention analysis: 2026-05-06*
