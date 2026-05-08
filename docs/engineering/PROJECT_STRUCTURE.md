# Project Structure & Conventions

> Last updated: 2026-05-06 — Audited against actual codebase

---

## Actual Directory Layout

```
app/
├── Actions/                         # Fortify action classes (not business logic actions)
│   └── Fortify/
│       ├── CreateNewUser.php
│       ├── PasswordValidationRules.php
│       ├── ResetUserPassword.php
│       ├── UpdateUserPassword.php
│       └── UpdateUserProfileInformation.php
├── Enums/
│   └── ReportStatus.php            # State machine: received→verified→scheduled→in_progress→repaired/rejected
├── Events/
│   └── ReportCreated.php           # Broadcasts on private-admin.reports channel
├── Filament/
│   ├── Resources/
│   │   ├── ReportResource.php      # CRUD with status/priority badges, filters, location modal
│   │   ├── UserResource.php        # Basic CRUD
│   │   ├── RepairJobResource.php   # Basic CRUD
│   │   ├── ExpenseResource.php     # CRUD with vendor selector
│   │   └── VendorResource.php      # Basic CRUD
│   └── Widgets/
│       ├── ReportsOverview.php     # 4 KPI cards
│       ├── ReportsChart.php        # 30-day line chart
│       ├── ReportsByNeighborhood.php  # Top 10 bar chart
│       └── ReportsMap.php          # Iframe of public map
├── Http/
│   ├── Controllers/
│   │   ├── Controller.php          # Base (empty)
│   │   ├── MapController.php      # /carte page + GeoJSON API
│   │   └── ReportTrackingController.php  # /suivi/{uuid} + JSON lookup
│   └── Middleware/
│       └── SetLocale.php           # Session-based locale switching
├── Mail/
│   └── ReportStatusUpdated.php     # Queued mailable, bilingual via preferred_locale
├── Models/                         # 13 Eloquent models
│   ├── User.php                    # Role checks, UUID auto-gen, HasAppAuthentication
│   ├── Role.php                    # 5 roles: admin, manager, service_worker, accountant, viewer
│   ├── Report.php                  # State machine, LogsActivity, SoftDeletes, InteractsWithMedia
│   ├── ReportCategory.php         # Lookup table (pothole, graffiti, etc.)
│   ├── RepairJob.php              # UUID auto-gen, BelongsToMany reports/workers/materials
│   ├── Expense.php                # GST+QST tax fields, BelongsTo vendor
│   ├── Vendor.php                 # Supplier management
│   ├── Material.php               # current_stock, reserved_stock, min_stock_alert
│   ├── MaterialPurchase.php       # Purchase logging with stock update tracking
│   ├── JobReport.php              # Pivot: repair_job + report, cost_allocation_percentage
│   ├── JobWorker.php              # Pivot: repair_job + user, role_in_job, hours_worked
│   ├── JobMaterial.php            # Pivot: repair_job + material, quantity_planned/actual
│   └── MontrealBoundary.php      # Static contains(lat,lng) for geofencing
├── Policies/
│   ├── ReportPolicy.php           # Admin/Manager can update; Admin only can delete
│   ├── UserPolicy.php             # Admin-only access
│   ├── RepairJobPolicy.php        # Admin/Manager/ServiceWorker can create/update
│   └── ExpensePolicy.php          # Admin/Accountant can create/update
├── Providers/
│   ├── AppServiceProvider.php
│   ├── AuthServiceProvider.php    # Policy registrations
│   ├── BroadcastServiceProvider.php
│   ├── EventServiceProvider.php
│   ├── Filament/
│   │   └── AdminPanelProvider.php # Filament v5 panel, 2FA, Reverb scripts hook
│   └── FortifyServiceProvider.php
└── Services/
    └── ExifStripper.php           # Strips EXIF from uploaded images (Imagick + GD)

database/
├── migrations/                     # 27 migration files
│   ├── 0001_01_01_*               # Laravel defaults (users, cache, jobs)
│   ├── 2024_01_01_*               # Core tables (media, activity_log, roles, reports, etc.)
│   ├── 2026_05_04_*               # Passkeys, nullable location, telescope
│   └── 2026_05_06_*               # Cleanup: drop clutter, add vendors, fix constraints
├── seeders/
│   ├── AdminUserSeeder.php        # Creates default admin user
│   ├── DatabaseSeeder.php         # Main seeder
│   ├── MontrealBoundarySeeder.php # Imports GeoJSON boundary
│   ├── ReportCategorySeeder.php   # Pothole, graffiti, etc.
│   ├── RoleSeeder.php             # 5 RBAC roles
│   └── TestDataSeeder.php         # Development test data
└── factories/                      # 5 factories: User, Report, ReportCategory, RepairJob, Expense

resources/
├── views/
│   ├── welcome.blade.php           # Citizen homepage: stats, tracking modal (Alpine.js)
│   ├── report.blade.php            # Report form page with <livewire:report-form />
│   ├── tracking.blade.php          # Report tracking with timeline + Leaflet map
│   ├── map.blade.php               # Public map: Leaflet + GeoJSON + color markers
│   ├── layouts/
│   │   └── citizen.blade.php      # PWA meta, Inter font, FR/EN switch, mobile nav
│   ├── components/
│   │   └── ⚡report-form.blade.php # Anonymous Livewire component (inline class)
│   ├── emails/
│   │   └── report-status-updated.blade.php  # Markdown email template
│   └── filament/
│       ├── widgets/reports-map.blade.php     # Iframe for admin dashboard
│       └── modals/report-location.blade.php  # Location viewer modal
├── js/
│   ├── app.js                      # Main entry
│   ├── bootstrap.js                # Axios setup
│   ├── echo.js                     # Laravel Echo + Reverb config
│   └── reverb-listener.js          # Admin notification listener
├── css/
│   └── app.css                     # Tailwind imports
└── lang/                           # Bilingual translations
    ├── fr/                         # French (default)
    │   ├── report.php
    │   ├── map.php
    │   ├── tracking.php
    │   ├── email.php
    │   └── dashboard.php
    └── en/                         # English
        ├── report.php
        ├── map.php
        ├── tracking.php
        ├── email.php
        └── dashboard.php

routes/
├── web.php                         # 7 routes: home, /signaler, /suivi/{uuid}, /carte, /locale/{locale}, API endpoints
├── channels.php                    # 1 channel: private-admin.reports
└── console.php                     # Default inspire command only

tests/
├── Pest.php                        # Pest bootstrap
├── TestCase.php                    # Base test case
├── Unit/
│   └── ExampleTest.php             # Default
└── Feature/
    ├── ReportStateMachineTest.php  # ~17 tests
    ├── GeofencingTest.php          # ~5 tests
    ├── ReportFormTest.php          # ~2 tests
    ├── ReportTrackingTest.php      # ~5 tests
    ├── EmailNotificationTest.php   # ~6 tests
    ├── RbacPolicyTest.php          # ~16 tests
    ├── ReverbNotificationTest.php  # ~4 tests
    ├── AdminDashboardTest.php      # ~2 tests
    ├── ModelsTest.php              # ~6 tests
    └── ExampleTest.php             # Default
```

---

## Conventions

### 1. Anonymous Livewire Components

The report form uses an anonymous Livewire component (inline class in the Blade file) rather than a traditional `app/Livewire/` class. The `app/Livewire/` directory does not exist.

```blade
{{-- resources/views/components/⚡report-form.blade.php --}}
@php
new class extends Component {
    use WithFileUploads, UsesSpamProtection;
    // ...
}
@endphp
```

### 2. Business Logic in Models

Unlike the original convention of using Action classes, business logic currently lives in models and Livewire components. For example:
- `Report::transitionTo()` handles state machine + email + activity logging
- `MontrealBoundary::contains()` handles geofence validation
- The report-form component handles geocoding, EXIF stripping, and media storage inline

**Future direction:** Extract complex operations into Action classes as the codebase grows.

### 3. PostGIS Logic in Models and Controllers

Spatial queries are in Eloquent scopes and the MontrealBoundary model. Raw SQL is minimal:

```php
// app/Models/Report.php
public function scopeNear($query, $lat, $lng, $radiusKm)
public function scopeStatus($query, $status)

// app/Models/MontrealBoundary.php
public static function contains(float $lat, float $lng): bool
```

### 4. Filament Resources Follow the Full Lifecycle

Filament Resource classes define `form()`, `table()`, and `getPages()`. Authorization via corresponding Policy classes.

### 5. One Model, One Policy

Currently implemented for 4 of 13 models. Missing policies: Vendor, Material, MaterialPurchase, ReportCategory, Role.

### 6. Type Hints Are Mandatory

All methods declare scalar type hints and return types. Enforced by PHPStan Level 5 in CI.

### 7. Media Collections Are Named

```php
// Report model
public function registerMediaCollections(): void
{
    $this->addMediaCollection('report-photos')
        ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
        ->maxNumberOfFiles(5);
}
```

**Note:** Only `report-photos` (before) collection exists. `after_photos` collection is not yet registered.

### 8. Migrations Must Be Reversible

Every migration includes a `down()` method. Critical for Railway predeploy safety.

### 9. Tests Mirror the Source Structure

Feature tests cover: state machine, geofencing, report form, tracking, email, RBAC, Reverb, dashboard, models.

### 10. Bilingual by Default

All user-facing strings use `__()` translation keys. French is the default locale (`app.locale = 'fr'`). Language files exist in `lang/fr/` and `lang/en/`.

---

## Naming Conventions

| Layer | Naming Rule | Example |
|-------|-------------|---------|
| Filament Resources | `NounResource.php` | `ReportResource.php` |
| Policies | `NounPolicy.php` | `ReportPolicy.php` |
| Migrations | Laravel timestamp convention | `2024_01_01_000000_create_reports_table.php` |
| Test Classes | `FeatureTest.php` | `ReportStateMachineTest.php` |
| Mail | `NounVerb.php` | `ReportStatusUpdated.php` |
| Events | `NounVerb.php` | `ReportCreated.php` |
| Enums | `NounStatus.php` | `ReportStatus.php` |

---

*This structure is a living document. Propose changes via PR with rationale.*
