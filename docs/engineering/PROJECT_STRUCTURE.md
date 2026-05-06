# Project Structure & Conventions

This document defines the directory layout and coding conventions for NidVite. All developers must follow these rules to maintain consistency.

---

## Directory Layout

```
app/
├── Actions/                    # Business logic units (single responsibility)
│   ├── Reports/
│   │   ├── CreateReport.php         # Handles zero-auth report creation
│   │   ├── ValidateGeofence.php     # PostGIS check: is this in Montreal?
│   │   └── ClusterNearbyReports.php # 5m proximity clustering (queue job logic)
│   └── Mail/
│       └── SendJobCompletionEmail.php # Resend email dispatch
├── Filament/
│   ├── Resources/
│   │   └── ReportResource.php       # CRUD + map widget for entrepreneur
│   └── Widgets/
│       └── MapWidget.php            # MapLibre embed in dashboard
├── Livewire/
│   ├── ReportForm.php               # Citizen: submit a report
│   ├── PhotoUploader.php            # Citizen: camera/photo handling
│   └── TrackReport.php              # Citizen: status lookup by UUID
├── Models/
│   ├── Report.php                   # Core entity: reports table
│   └── User.php                     # Entrepreneur login only
├── Rules/
│   └── WithinMontrealGeofence.php   # Custom validation rule for PostGIS
└── Providers/
    └── AppServiceProvider.php       # Container bindings

database/
├── migrations/                      # All migrations must have down()
└── seeders/
    └── MontrealBoundarySeeder.php   # GeoJSON boundary import

resources/
├── views/
│   ├── components/                  # Reusable Blade/MaryUI components
│   ├── livewire/                    # Livewire component views
│   └── layouts/                     # App layouts (PWA + Dashboard)
└── js/
    └── maplibre-config.js           # MapLibre center, zoom, style

routes/
├── web.php                          # PWA routes (report, track)
└── admin.php                        # Filament handles its own, but custom admin routes go here

tests/
├── Feature/                         # Pest feature tests (HTTP, Livewire)
│   ├── ReportSubmissionTest.php
│   └── GeofencingTest.php
├── Unit/                            # Pest unit tests (Actions, Rules)
│   ├── Actions/
│   └── Rules/
└── Browser/                         # Dusk tests (if adopted later)
```

---

## Conventions

### 1. Actions Over Fat Controllers

All business logic belongs in `app/Actions/`. Livewire components and controllers are thin orchestrators.

**Bad:**
```php
class ReportForm extends Component
{
    public function submit()
    {
        // 50 lines of PostGIS logic inline
    }
}
```

**Good:**
```php
class ReportForm extends Component
{
    public function submit()
    {
        $report = (new CreateReport)->execute($this->state);
        redirect()->route('track', $report->uuid);
    }
}
```

### 2. PostGIS Logic Lives in Actions Only

Raw spatial queries (`ST_DWithin`, `ST_Within`, `ST_ClusterDBSCAN`) must never appear in controllers, Livewire components, or Blade views. They belong in dedicated Action classes or Eloquent query scopes within the model.

```php
// app/Models/Report.php
public function scopeWithinMontreal($query)
{
    return $query->whereRaw('ST_Within(location, (SELECT boundary FROM montreal_boundary LIMIT 1))');
}
```

### 3. Filament Resources Follow the Full Lifecycle

Filament `Resource` classes must define:
- `form()` — with validation rules
- `table()` — with filters and actions
- `getPages()` — for custom map views
- Authorization via `ReportPolicy`

### 4. One Model, One Policy

Even if the policy is simple, every model must have a corresponding policy.

```php
// app/Policies/ReportPolicy.php
public function update(User $user, Report $report): bool
{
    return $user->isEntrepreneur();
}
```

### 5. Type Hints Are Mandatory

All methods must declare scalar type hints and return types.

```php
public function findNearby(Point $location, float $radiusKm): Collection
{
    // ...
}
```

### 6. Media Collections Are Named

Use semantic collection names on the `Report` model:

```php
public function registerMediaCollections(): void
{
    $this->addMediaCollection('before_photos')
        ->acceptsMimeTypes(['image/jpeg', 'image/png'])
        ->maxNumberOfFiles(3);

    $this->addMediaCollection('after_photos')
        ->acceptsMimeTypes(['image/jpeg', 'image/png'])
        ->maxNumberOfFiles(3);
}
```

### 7. Migrations Must Be Reversible

Every migration must include a `down()` method. This is critical for Railway predeploy safety.

```php
public function down(): void
{
    Schema::dropIfExists('reports');
}
```

### 8. Tests Mirror the Source Structure

If a class lives in `app/Actions/Reports/CreateReport.php`, its test lives in `tests/Unit/Actions/Reports/CreateReportTest.php`.

---

## Naming Conventions

| Layer | Naming Rule | Example |
|-------|-------------|---------|
| Actions | `VerbNoun.php` | `CreateReport.php` |
| Livewire | `NounVerb.php` | `ReportForm.php` |
| Filament Resources | `NounResource.php` | `ReportResource.php` |
| Policies | `NounPolicy.php` | `ReportPolicy.php` |
| Rules | `AdjectiveNoun.php` | `WithinMontrealGeofence.php` |
| Migrations | Laravel timestamp convention | `2024_01_15_000000_create_reports_table.php` |
| Test Classes | `ClassNameTest.php` | `CreateReportTest.php` |

---

*This structure is a living document. Propose changes via PR with rationale.*
