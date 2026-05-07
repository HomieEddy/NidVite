# Code Structure

> Mapped: 2026-05-06 | Focus: arch

## Directory Tree

```
NidVite/
├── app/                              # Application core (Laravel 11)
│   ├── Actions/Fortify/              # 5 Fortify custom actions
│   ├── Enums/                        # 1 enum (ReportStatus)
│   ├── Events/                       # 1 event (ReportCreated)
│   ├── Filament/                     # Filament v5 admin panel
│   │   ├── Resources/                # 5 resources with split structure
│   │   │   ├── Reports/              #   ReportResource (resource + form + table + pages)
│   │   │   ├── Users/                #   UserResource
│   │   │   ├── RepairJobs/           #   RepairJobResource
│   │   │   ├── Expenses/             #   ExpenseResource
│   │   │   └── Vendors/             #   VendorResource
│   │   └── Widgets/                  # 4 dashboard widgets
│   ├── Http/
│   │   ├── Controllers/              # 3 controllers
│   │   └── Middleware/               # 1 middleware (SetLocale)
│   ├── Mail/                         # 1 mailable (ReportStatusUpdated)
│   ├── Models/                       # 13 Eloquent models
│   ├── Policies/                     # 4 policies
│   ├── Providers/                    # 5 service providers
│   └── Services/                     # 1 service (ExifStripper)
├── bootstrap/                        # Laravel bootstrap
├── config/                           # Laravel config files
├── database/
│   ├── factories/                    # 5 model factories
│   ├── migrations/                   # 27 migrations
│   └── seeders/                      # 6 seeders
├── docs/                             # Project documentation
├── lang/                             # FR/EN translation files
├── public/                           # Web root
├── resources/
│   ├── css/                          # app.css (Tailwind)
│   ├── js/                           # 4 JS files (app, bootstrap, echo, reverb-listener)
│   └── views/                        # 14 Blade templates
│       ├── components/               # Livewire components
│       ├── emails/                   # Email templates
│       ├── filament/                 # Filament custom views
│       ├── layouts/                  # Layout templates
│       └── vendor/                   # Published package views
├── routes/
│   ├── web.php                       # 6 public routes
│   ├── channels.php                  # 1 broadcast channel
│   └── console.php                   # 1 artisan command
├── storage/                          # Laravel storage
├── tests/                            # Test suite
├── composer.json                     # PHP dependencies
├── package.json                      # JS dependencies
├── vite.config.js                    # Vite build config
├── phpunit.xml                       # PHPUnit config
└── phpstan.neon                      # PHPStan static analysis config
```

## App Directory Detail

### Models (13)

| Model | Table | Key Relationships | Notable Traits |
|-------|-------|-------------------|----------------|
| `User` | `users` | `belongsTo Role`, `hasMany RepairJob`, `belongsToMany RepairJob (job_workers)`, `hasMany Expense`, `hasMany MaterialPurchase`, `hasMany JobWorker` | UUID auto-gen, 2FA (Filament AppAuthentication), role check methods (`isAdmin`, `isManager`, etc.) |
| `Role` | `roles` | `hasMany User` | Bilingual labels (`label_en`, `label_fr`), `smallIncrements` PK |
| `Report` | `reports` | `belongsTo ReportCategory`, `belongsToMany RepairJob (job_reports)`, `hasMany JobReport` | PostGIS `setLocation()`/`scopeNear()`, `transitionTo()` state machine, `SoftDeletes`, `InteractsWithMedia`, `LogsActivity`, `validateGeofence()`, UUID auto-gen |
| `ReportCategory` | `report_categories` | `hasMany Report` | Bilingual labels, icon/color, `smallIncrements` PK |
| `RepairJob` | `repair_jobs` | `belongsTo User (creator)`, `belongsToMany Report (job_reports)`, `hasMany JobReport`, `hasMany JobWorker`, `belongsToMany User (job_workers)`, `hasMany Expense`, `hasMany JobMaterial`, `belongsToMany Material (job_materials)` | UUID auto-gen, status enum: planned/in_progress/completed/cancelled |
| `Expense` | `expenses` | `belongsTo RepairJob`, `belongsTo Material`, `belongsTo Vendor`, `belongsTo User (creator)` | Tax calculation fields (QST+GST default 0.14975), `vendor_id` FK added later |
| `Vendor` | `vendors` | `hasMany Expense` | Contact info, active flag |
| `Material` | `materials` | `hasMany MaterialPurchase`, `hasMany Expense`, `belongsToMany RepairJob (job_materials)`, `hasMany JobMaterial` | Inventory tracking (current/reserved/min_stock), avg/last purchase price |
| `MaterialPurchase` | `material_purchases` | `belongsTo Material`, `belongsTo User (creator)` | Purchase record with tax calc, `stock_updated` flag |
| `JobReport` | `job_reports` | `belongsTo RepairJob`, `belongsTo Report` | Pivot model with `cost_allocation_percentage`, unique constraint on (job_id, report_id) |
| `JobWorker` | `job_workers` | `belongsTo RepairJob`, `belongsTo User` | Pivot model with `role_in_job` (lead/assistant), `hours_worked` |
| `JobMaterial` | `job_materials` | `belongsTo RepairJob`, `belongsTo Material` | Pivot model with `quantity_planned`, `quantity_actual`, `unit_cost_at_time` |
| `MontrealBoundary` | `montreal_boundary` | None | Static `contains()` method using `ST_Contains`, `getBoundary()` returns singleton record |

### Controllers (3)

| Controller | Routes | Responsibility |
|------------|--------|----------------|
| `Controller` | None (abstract base) | Base controller (empty, Laravel default) |
| `MapController` | `GET /carte`, `GET /api/reports/geojson` | Public map page + GeoJSON API for Leaflet markers |
| `ReportTrackingController` | `GET /suivi/{uuid}`, `GET /api/reports/{uuid}/lookup` | Report tracking page + JSON API for progress bar + timeline |

### Filament Resources (5)

All resources use the **split class structure**: Resource class delegates to separate `Schemas\XxxForm` and `Tables\XxxTable` classes, with separate `Pages\` directory.

| Resource | Model | Pages | Notable Features |
|----------|-------|-------|-----------------|
| `ReportResource` | `Report` | List, Create, Edit | Custom `getEloquentQuery()` hides spam/rejected from non-admins; bypasses `SoftDeletingScope`; map location modal on "Map" column; status/priority badge colors; TrashedFilter; grouping by status/neighborhood/borough/priority/date |
| `UserResource` | `User` | List, Create, Edit | Basic CRUD; role_id relationship select; 2FA fields in form |
| `RepairJobResource` | `RepairJob` | List, Create, Edit | Status badge (planned/in_progress/completed/cancelled); money columns CAD; grouping by status/creator/scheduled month |
| `ExpenseResource` | `Expense` | List, Create, Edit | Vendor/Material relationship selects; tax rate default 0.14975 (QST+GST); grouping by vendor/job/month; policy-restricted create/update (admin/accountant) |
| `VendorResource` | `Vendor` | List, Create, Edit | Simple CRUD; searchable name; active toggle; grouping by active status |

### Filament Widgets (4)

| Widget | Type | Data Source | Sort |
|--------|------|-------------|------|
| `ReportsOverview` | `StatsOverviewWidget` | 4 stats: open reports, repairs this week, money spent (Expense::sum), avg repair time | Default |
| `ReportsChart` | `ChartWidget` (line) | Report counts per day, last 30 days, non-rejected | Default |
| `ReportsByNeighborhood` | `ChartWidget` (bar) | Top 10 neighborhoods by report count, non-rejected | Default |
| `ReportsMap` | `Widget` (custom Blade) | Embeds `/carte?embed=1` iframe in Filament section | -10 (top position) |

### Policies (4)

| Policy | Model | Key Rules |
|--------|-------|-----------|
| `ReportPolicy` | `Report` | viewAny/view: all roles; create/update: admin+manager; delete/restore/forceDelete: admin only |
| `UserPolicy` | `User` | All operations: admin only; delete: admin + cannot delete self |
| `RepairJobPolicy` | `RepairJob` | viewAny/view: all roles; create/update: admin+manager+service_worker; delete/restore/forceDelete: admin only |
| `ExpensePolicy` | `Expense` | viewAny/view: all roles; create/update: admin+accountant; delete/restore/forceDelete: admin only |

### Services (1)

| Service | Location | Responsibility |
|---------|----------|----------------|
| `ExifStripper` | `app/Services/ExifStripper.php` | Strips EXIF metadata from uploaded images by re-encoding via Intervention Image. Auto-detects imagick or gd extension. Returns temp file path for Media Library consumption. |

### Events (1)

| Event | Channel | Broadcast As | Payload | Queue |
|-------|---------|-------------|---------|-------|
| `ReportCreated` | `private-admin.reports` | `report.created` | `id, uuid, status, address, category (label_fr), created_at` | `ShouldBroadcastNow` (no queue) |

### Mail (1)

| Mailable | Trigger | Template | Queue |
|----------|---------|----------|-------|
| `ReportStatusUpdated` | `Report::transitionTo()` → `sendStatusNotification()` | `emails.report-status-updated` (Markdown) | `ShouldQueue` |

### Enums (1)

| Enum | Values | Usage |
|------|--------|-------|
| `ReportStatus` | `received`, `verified`, `scheduled`, `in_progress`, `repaired`, `rejected` | Backed string enum. `transitions()` returns allowed next states. `isTerminal()` for repaired/rejected. Enforced by `Report::transitionTo()` and DB CHECK constraint. |

### Providers (5)

| Provider | Registers |
|----------|-----------|
| `AppServiceProvider` | Empty (Laravel default) |
| `AuthServiceProvider` | 4 model→policy mappings: Report→ReportPolicy, User→UserPolicy, RepairJob→RepairJobPolicy, Expense→ExpensePolicy |
| `AdminPanelProvider` | Filament panel config: id=admin, path=/admin, amber theme, auto-discover resources/pages/widgets, 2FA (AppAuthentication with recovery), Reverb render hook, standard middleware stack |
| `FortifyServiceProvider` | Fortify actions (CreateNewUser, UpdateUserProfileInformation, UpdateUserPassword, ResetUserPassword), rate limiters for login/2FA/passkeys |
| `TelescopeServiceProvider` | Telescope debug assistant, local auto-enable, sensitive details hidden in production, gate-based access |

### Fortify Actions (5)

| Action | Location | Purpose |
|--------|----------|---------|
| `CreateNewUser` | `app/Actions/Fortify/CreateNewUser.php` | Validates name+email+password, creates User with hashed password |
| `PasswordValidationRules` | `app/Actions/Fortify/PasswordValidationRules.php` | Shared password validation rules trait |
| `ResetUserPassword` | `app/Actions/Fortify/ResetUserPassword.php` | Resets user password with hash |
| `UpdateUserPassword` | `app/Actions/Fortify/UpdateUserPassword.php` | Updates password with validation |
| `UpdateUserProfileInformation` | `app/Actions/Fortify/UpdateUserProfileInformation.php` | Updates name/email with unique email check |

## Migrations (27)

### Timeline & Schema Evolution

**Phase 1 — Core Schema (0001_01_01 + 2024_01_01):**
- `0001_01_01_000000` — users, password_reset_tokens, sessions
- `0001_01_01_000001` — cache table
- `0001_01_01_000002` — jobs (queue)
- `2024_01_01_000000` — media (Spatie)
- `2024_01_01_000001` — roles (admin, manager, service_worker, accountant, viewer)
- `2024_01_01_000002` — users table update (add uuid, role_id, 2FA fields, locale, is_active)
- `2024_01_01_000003` — report_categories (bilingual, icon/color)
- `2024_01_01_000004` — reports (PostGIS enabled, full spam tracking columns, soft deletes)
- `2024_01_01_000005` — repair_jobs
- `2024_01_01_000006` — job_reports (pivot: job ↔ report)
- `2024_01_01_000007` — job_workers (pivot: job ↔ user with role/hours)
- `2024_01_01_000008` — expense_categories (later dropped)
- `2024_01_01_000009` — materials (inventory tracking)
- `2024_01_01_000010` — expenses (with category_id, vendor string, receipt_media_id)
- `2024_01_01_000011` — material_purchases
- `2024_01_01_000012` — job_materials (pivot: job ↔ material with planned/actual quantities)
- `2024_01_01_000001` — activity_log (Spatie)

**Phase 2 — Refinements (2026_05_04 – 2026_05_06):**
- `2026_05_04_174205` — passkeys table (Laravel Passkeys)
- `2026_05_04_193043` — reports.location made nullable
- `2026_05_04_194357` — telescope_entries
- `2026_05_05_200000` — reports.reporter_email made nullable (for anonymous submissions)
- `2026_05_06_000000` — montreal_boundary table (PostGIS polygon)
- `2026_05_06_033000` — Fix reports status CHECK constraint (pending→received, add verified)
- `2026_05_06_050000` — Remove clutter from reports (drop: ip_address_hash, ip_address_raw, user_agent_hash, submission_duration_ms, spam_score, geofence_checked_at, email_verified_at, location_accuracy)
- `2026_05_06_051000` — Remove clutter from all tables (drop: users.email_verified_at, repair_jobs.weather_conditions, job_reports cost_override_reason+repair_notes, expenses vendor_contact+receipt_media_id, material_purchases vendor_contact+receipt_media_id)
- `2026_05_06_060000` — Drop expense_categories table + expenses.category_id column
- `2026_05_06_070000` — Create vendors table + add expenses.vendor_id FK

## Routes

### Web Routes (`routes/web.php`)

| Method | URI | Handler | Purpose | Auth |
|--------|-----|---------|---------|------|
| GET | `/` | Closure | Home page with stats + tracking modal | No |
| GET | `/signaler` | Closure → `report` view | Report submission form | No |
| GET | `/suivi/{uuid}` | `ReportTrackingController@show` | Report tracking page | No |
| GET | `/carte` | `MapController@index` | Public map page | No |
| GET | `/api/reports/geojson` | `MapController@geojson` | GeoJSON API for Leaflet markers | No |
| GET | `/api/reports/{uuid}/lookup` | `ReportTrackingController@lookup` | JSON API for report progress | Rate: 60/min |
| GET | `/locale/{locale}` | Closure | Switch FR/EN locale in session | No |

### Broadcast Channels (`routes/channels.php`)

| Channel | Authorization |
|---------|--------------|
| `private-admin.reports` | `$user->isAdmin() || $user->isManager()` |

### Console Routes (`routes/console.php`)

| Command | Schedule |
|---------|----------|
| `inspire` | Hourly |

## Views Structure

```
resources/views/
├── welcome.blade.php              # Home page — stats grid + Alpine.js tracking modal
├── report.blade.php               # Report form page — extends citizen layout, loads Leaflet
├── tracking.blade.php             # Report tracking — timeline + Leaflet mini-map
├── map.blade.php                  # Public map — full-screen Leaflet + GeoJSON fetch
├── layouts/
│   └── citizen.blade.php          # PWA shell — header + bottom nav + @laravelPWA + Vite
├── components/
│   └── ⚡report-form.blade.php    # Livewire 3 anonymous component — the full report form
├── emails/
│   └── report-status-updated.blade.php  # Markdown email — status change notification
├── filament/
│   ├── widgets/
│   │   └── reports-map.blade.php  # Embedded iframe of public map in Filament dashboard
│   └── modals/
│       └── report-location.blade.php  # OSM embed modal for report location in admin table
└── vendor/
    ├── filament/
    │   └── reverb-scripts.blade.php    # Conditional Echo+listener script injection for admin/manager
    ├── laravelpwa/
    │   ├── meta.blade.php             # PWA meta tags
    │   └── offline.blade.php          # Offline fallback page
    └── honeypot/
        └── honeypotFormFields.blade.php  # Honeypot spam trap fields
```

### Layout: `citizen.blade.php`

- PWA-optimized: `<meta name="theme-color">`, `<meta name="apple-mobile-web-app-capable">`, manifest link
- Google Fonts: Inter
- Top header: logo + desktop nav + FR/EN toggle
- Main content: `@yield('content')`
- Bottom nav: mobile-only sticky (Signaler, Carte, Accueil)
- Accessibility: skip-to-content link, `btn-touch` class for 44px+ touch targets

### Livewire Component: `⚡report-form.blade.php`

**Anonymous Livewire 3 component** — Class definition inline in Blade file (no separate PHP class).

**State:** `reporter_email`, `category_id` (default: pothole), `description`, `address`, `neighborhood`, `borough`, `latitude/longitude`, `photos[]`, `photoPreviews[]`, `submitted` flag

**Computed properties:** `categories` (active ReportCategories), `neighborhoods` (45 Montreal neighborhoods hardcoded), `boroughs` (19 Montreal boroughs hardcoded), `potholeCategory`

**Submit flow:** Validate → `protectAgainstSpam()` → `Report::validateGeofence()` → `DB::transaction(create + setLocation + ExifStripper + addMedia)` → `event(ReportCreated)` → set `submitted=true`

**Alpine.js map integration:** Leaflet map initialized on `$nextTick`, GPS capture via `navigator.geolocation`, reverse geocode via Nominatim, forward geocode on address blur

## Database Schema Summary

```
┌─────────────┐     ┌──────────────────┐     ┌──────────────────────┐
│    roles     │     │ report_categories│     │   montreal_boundary  │
│─────────────│     │──────────────────│     │──────────────────────│
│ id (smallPK)│←──┐ │ id (smallPK)     │←──┐ │ id                   │
│ slug        │   │ │ slug             │   │ │ name                 │
│ label_en    │   │ │ label_en         │   │ │ boundary (POLYGON)   │
│ label_fr    │   │ │ label_fr         │   │ └──────────────────────┘
│ sort_order  │   │ │ icon, color      │   │
└─────────────┘   │ │ is_active        │   │    ┌──────────────────┐
                  │ │ sort_order       │   │    │     vendors      │
                  │ └──────────────────┘   │    │──────────────────│
                  │           │             │    │ id               │
                  │           │             │    │ name             │
            ┌─────┴──────┐   │             │    │ contact_name     │
            │    users    │   │             │    │ email, phone     │
            │────────────│   │             │    │ address, website │
            │ id         │   │  ┌────────────────┼ notes           │
            │ uuid       │   │  │ reports        │ │ is_active       │
            │ name       │   │  │───────────────│─┘└──────────────────┘
            │ email      │   ├──│ id            │        │
            │ password   │   │  │ uuid          │        │ vendor_id (FK)
            │ role_id FK │   │  │ reporter_email│        │
            │ 2FA fields │   │  │ preferred_locale│  ┌───────────────┐
            │ locale     │   │  │ location(PT)  │  │   expenses    │
            │ is_active  │   │  │ address       │  │───────────────│
            └─────┬──────┘   │  │ neighborhood  │←─│ id            │
                  │          │  │ borough       │  │ repair_job_id  │
                  │          │  │ status (enum) │  │ material_id   │
      ┌───────────┤          │  │ priority      │  │ vendor_id     │
      │           │          │  │ category_id FK│  │ description   │
      │     ┌─────┴────┐     │  │ geofence_passed│ │ quantity/unit │
      │     │repair_jobs│    │  │ is_spam       │  │ costs + tax   │
      │     │──────────│     │  │ rejection_reason│ │ incurred_at   │
      │     │ id       │     │  │ admin_notes   │  │ created_by FK │
      │     │ uuid     │     │  │ timestamps×5  │  └───────────────┘
      │     │ title    │     │  │ soft_deletes  │
      │     │ status   │     │  └──────────────┘
      │     │created_by│←────┤         │
      │     │ costs    │     │         │
      │     └────┬─────┘     │    ┌────┴─────────┐
      │          │           │    │  job_reports  │
      │    ┌─────┼───────┐   │    │──────────────│
      │    │     │       │   │    │ repair_job_id │←──┐
      │    │     │  ┌────┴──┐│    │ report_id    │←──┤
      │    │     │  │job_workers││  │ cost_alloc_% │   │
      │    │     │  │─────────││    └──────────────┘   │
      │    │     │  │repair_job_id│                     │
      │    │  ┌─┤  │ user_id  │                  ┌─────┴──────┐
      │    │  │  │  │ role_in_job│                 │ job_materials│
      │    │  │  │  │ hours_worked│                │────────────│
      │    │  │  │  └─────────┘                    │ repair_job_id│
      │    │  │  │                                  │ material_id │
      │    │  │  │  ┌────────────┐  ┌────────────┐ │ qty_planned │
      │    │  │  │  │  materials │  │ mat_purchases│ │ qty_actual  │
      │    │  │  │  │────────────│  │────────────│ │ unit_cost   │
      │    │  │  │  │ id         │←─│ id         │ └────────────┘
      │    │  │  │  │ sku        │  │ material_id│
      │    │  │  │  │ name       │  │ quantity   │
      │    │  │  │  │ unit       │  │ costs+tax  │
      │    │  │  │  │ stock vals │  │ vendor     │
      │    │  │  │  │ prices     │  │ created_by │
      │    │  │  │  │ is_active  │  └────────────┘
      │    │  │  │  └────────────┘
      │    │  │  │
      ↓    ↓  ↓  ↓
   (created_by on expenses, material_purchases, repair_jobs → users.id)
```

### Key Table Details

**reports** — PostGIS enabled, `GEOGRAPHY(POINT,4326)` for location, GiST spatial index. Status CHECK constraint enforces enum values at DB level. Soft deletes. Multiple composite indexes for common query patterns.

**pivot tables** — `job_reports` (repair_job ↔ report), `job_workers` (repair_job ↔ user), `job_materials` (repair_job ↔ material). All have unique constraints on the FK pair. Job workers has `role_in_job` enum (lead/assistant).

**vendors** — Added late (migration `2026_05_06_070000`), replacing the string `vendor` column on expenses with a proper FK relationship.

## Seeders (6)

| Seeder | Seeds |
|--------|-------|
| `DatabaseSeeder` | Calls: RoleSeeder → ReportCategorySeeder → MontrealBoundarySeeder → AdminUserSeeder |
| `RoleSeeder` | 5 roles: admin, manager, service_worker, accountant, viewer (bilingual labels) |
| `ReportCategorySeeder` | Report categories (bilingual, with icon/color) |
| `MontrealBoundarySeeder` | Simplified 14-point polygon of Island of Montreal as PostGIS POLYGON |
| `AdminUserSeeder` | Default admin user |
| `TestDataSeeder` | Test/sample data for development |

## Factories (5)

| Factory | Model |
|---------|-------|
| `UserFactory` | `User` |
| `ReportFactory` | `Report` |
| `ReportCategoryFactory` | `ReportCategory` |
| `RepairJobFactory` | `RepairJob` |
| `ExpenseFactory` | `Expense` |

## Where to Add New Code

### New Public Page
- Route: `routes/web.php` (follow pattern: `Route::get('/path', [Controller::class, 'method'])->name('route.name')`)
- Controller: `app/Http/Controllers/` (if complex logic) or closure in routes (if simple)
- View: `resources/views/{page}.blade.php` extending `layouts.citizen`

### New Livewire Component
- Anonymous: Create `resources/views/components/⚡{name}.blade.php` with inline class (like report-form)
- Class-based: Create `app/Livewire/{Name}.php` + `resources/views/livewire/{name}.blade.php`

### New Filament Resource
- Run `php artisan make:filament-resource {Model}` — generates split structure under `app/Filament/Resources/{ModelName}/`
- Resource class at `app/Filament/Resources/{ModelName}/{ModelName}Resource.php`
- Form schema at `app/Filament/Resources/{ModelName}/Schemas/{ModelName}Form.php`
- Table config at `app/Filament/Resources/{ModelName}/Tables/{ModelName}Table.php`
- Pages at `app/Filament/Resources/{ModelName}/Pages/` (List, Create, Edit)

### New Model
- Create in `app/Models/`
- Add UUID auto-generation in `static::booted()` if needed
- Register factory in `database/factories/`
- Create migration in `database/migrations/`

### New Policy
- Create in `app/Policies/`
- Register in `app/Providers/AuthServiceProvider.php` `$policies` array

### New Broadcast Event
- Create in `app/Events/` implementing `ShouldBroadcast` or `ShouldBroadcastNow`
- Add channel authorization in `routes/channels.php`
- Add client listener in `resources/js/reverb-listener.js`

### New Email
- Create mailable in `app/Mail/`
- Create template in `resources/views/emails/`
- Queue with `implements ShouldQueue`

### New Migration
- `php artisan make:migration {description}` — file lands in `database/migrations/`
- Follow the naming convention: `YYYY_MM_DD_HHMMSS_{description}.php`
