# Architecture

> Mapped: 2026-05-06 | Focus: arch

## System Overview

NidVite is a Montreal-focused pothole reporting platform with two distinct interfaces: a **public Citizen PWA** for anonymous pothole submissions and tracking, and a **Filament v5 Admin Panel** for city staff to manage reports, repairs, expenses, and vendors. The system enforces a Montreal geofence on all submissions, uses a state machine for report lifecycle, broadcasts real-time notifications to admins, and sends status-update emails to reporters — all bilingual (FR/EN).

## Architectural Pattern

**Dual-Interface Monolith** — A single Laravel 11 application serving two radically different UX surfaces:

1. **Citizen PWA** — Server-rendered Blade + Livewire 3 + Alpine.js, zero-auth, mobile-first
2. **Admin Panel** — Filament v5 SPA, Fortify auth with 2FA, role-based access

Shared concerns (models, PostGIS, state machine, policies) live in `app/` and serve both interfaces.

### Key Characteristics

- **Zero-auth citizen flow** — No login required to submit or track reports
- **PostGIS spatial layer** — All location data stored as `GEOGRAPHY(POINT,4326)`, geofence via `ST_Contains`
- **State machine enforcement** — `ReportStatus` enum governs all status transitions
- **Bilingual by default** — FR/EN via Laravel localization, locale stored per-report and per-user
- **Event-driven notifications** — Reverb WebSocket broadcast + queued email on status change
- **Activity logging** — Spatie activitylog on report status/priority/notes changes

## Subsystems

### Citizen PWA (Public Interface)

**Purpose:** Allow anonymous Montreal citizens to report potholes and track existing reports.

**Components:**
- `routes/web.php` — 6 public routes (home, report, tracking, map, API, locale switch)
- `app/Http/Controllers/MapController.php` — Public map page + GeoJSON API endpoint
- `app/Http/Controllers/ReportTrackingController.php` — Report detail page + lookup API
- `resources/views/layouts/citizen.blade.php` — PWA shell with PWA meta, header, bottom nav
- `resources/views/components/⚡report-form.blade.php` — Livewire 3 anonymous component (the core form)
- `resources/views/welcome.blade.php` — Home page with stats + tracking modal (Alpine.js)
- `resources/views/report.blade.php` — Report submission page (wraps Livewire form)
- `resources/views/tracking.blade.php` — Report tracking page with Leaflet map + timeline
- `resources/views/map.blade.php` — Public map page with Leaflet + GeoJSON markers

**Flow:**
1. Citizen visits `/` → sees stats + "Report" and "Track" buttons
2. Click "Report" → `/signaler` → Livewire form loads with Leaflet map
3. Form captures: email, category (pothole-only MVP), description, address, location (GPS or manual geocode via Nominatim), photos (max 5)
4. On submit: `ExifStripper` strips photo metadata → `Report::create()` in DB transaction → `setLocation()` writes PostGIS point → `validateGeofence()` checks Montreal boundary → photos added via Spatie MediaLib → `ReportCreated` event dispatched
5. Success state shows UUID for tracking
6. Tracking via `/suivi/{uuid}` or API lookup at `/api/reports/{uuid}/lookup`

**Anti-spam:**
- Spatie Honeypot on the Livewire form
- `is_spam` flag on reports (admin-set, not auto-detected — spam detection columns were removed in cleanup migration)

**Location handling:**
- Browser Geolocation API → `$wire.latitude/longitude` → `Report::setLocation()` (raw SQL `ST_SetSRID(ST_MakePoint(...),4326)::geography`)
- Address blur → Nominatim reverse geocode fills address/neighborhood/borough
- Manual address → Nominatim forward geocode sets lat/lng

### Admin Panel (Filament v5)

**Purpose:** Authenticated admin interface for managing the full report-to-repair lifecycle.

**Components:**
- `app/Providers/Filament/AdminPanelProvider.php` — Panel config (path: `/admin`, amber theme, 2FA, Reverb scripts injected)
- `app/Filament/Resources/Reports/` — ReportResource with form, table, 3 pages (list/create/edit)
- `app/Filament/Resources/Users/` — UserResource with form, table, 3 pages
- `app/Filament/Resources/RepairJobs/` — RepairJobResource with form, table, 3 pages
- `app/Filament/Resources/Expenses/` — ExpenseResource with form, table, 3 pages
- `app/Filament/Resources/Vendors/` — VendorResource with form, table, 3 pages
- `app/Filament/Widgets/` — 4 dashboard widgets (overview stats, line chart, bar chart, embedded map)

**Authentication:**
- Laravel Fortify for login/registration/password management
- Filament `Authenticate` middleware on all admin routes
- App-based 2FA via Filament's `AppAuthentication` (TOTP with recovery codes)
- Laravel Passkeys support (migration exists)
- Rate limiting: 5/min login, 5/min 2FA, 10/min passkeys

**Authorization — Role hierarchy:**
| Role | Slug | Can do |
|------|------|--------|
| Administrator | `admin` | Everything — view/create/update/delete all resources |
| Manager | `manager` | Create/update reports + repair jobs; view all |
| Service Worker | `service_worker` | Create/update repair jobs; view reports + expenses |
| Accountant | `accountant` | Create/update expenses; view all |
| Viewer | `viewer` | Read-only access to reports, jobs, expenses |

**Admin query scoping:**
- `ReportResource::getEloquentQuery()` — Non-admins don't see spam/rejected reports; admins bypass `SoftDeletingScope`

### State Machine

**Implementation:** `app/Enums/ReportStatus.php` — PHP 8.1 backed enum with transition rules.

**States:**
```
received → verified → scheduled → in_progress → repaired
    ↘          ↘          ↘           ↘
  rejected  rejected  rejected    rejected
```

**Terminal states:** `repaired`, `rejected` (no further transitions allowed)

**Enforcement:** `Report::transitionTo()` validates via `ReportStatus::canTransitionTo()`, throws `InvalidArgumentException` on invalid transition, logs activity, triggers email notification.

**Database constraint:** `reports_status_check` CHECK constraint enforced at DB level (fixed by migration `2026_05_06_033000` — original had `pending` instead of `received`).

### Real-time Layer

**Stack:** Laravel Reverb (WebSocket server) + Laravel Echo (client) + Pusher protocol

**Components:**
- `app/Events/ReportCreated.php` — `ShouldBroadcastNow` event (no queue delay)
- `routes/channels.php` — Channel authorization: `admin.reports` private channel restricted to admin/manager users
- `resources/js/echo.js` — Echo client initialization with Reverb config from Vite env vars
- `resources/js/reverb-listener.js` — Client listener: `Echo.private('admin.reports').listen('.report.created', ...)`
- `resources/views/vendor/filament/reverb-scripts.blade.php` — Conditionally loads Echo scripts for admin/manager users only (injected via Filament `renderHook`)
- `app/Providers/Filament/AdminPanelProvider.php` — `renderHook('panels::body.start')` includes reverb-scripts

**Flow:**
1. Citizen submits report → `event(new ReportCreated($report))` in Livewire component
2. `ReportCreated` broadcasts on `private-admin.reports` as `report.created`
3. Admin's browser (if admin/manager) receives event via Echo
4. Browser notification shown (if `Notification.permission === 'granted'`)
5. Filament notification attempted (if `$wire.notify` available)

### Email Pipeline

**Stack:** Laravel Queue + Mailable + Resend (assumed from typical Laravel 11 setup)

**Components:**
- `app/Mail/ReportStatusUpdated.php` — Queued mailable (`implements ShouldQueue`)
- `resources/views/emails/report-status-updated.blade.php` — Markdown email template
- `app/Models/Report::sendStatusNotification()` — Triggered by `transitionTo()`

**Flow:**
1. Admin changes report status via Filament → `transitionTo()` called
2. Status saved, activity logged
3. `sendStatusNotification()` checks if `reporter_email` exists
4. If yes → `Mail::to(reporter_email)->send(new ReportStatusUpdated(...))`
5. Mailable resolves locale from `report->preferred_locale` (defaults to `fr`)
6. Email includes: status change, rejection reason (if applicable), tracking link

**Email content (bilingual):**
- Greeting with UUID
- Status change description (old → new)
- Rejection reason (if rejected)
- Info panel: UUID, date, current status
- CTA button: link to `/suivi/{uuid}` tracking page
- Footer + signature

## Data Flow Diagrams

### Report Submission Flow

```
Citizen browser                Server                              Database
─────────────                  ──────                              ────────
1. Fill form
2. Capture GPS          →  3. Livewire validate()
4. Submit               →  5. Honeypot check
                           6. validateGeofence()
                              → MontrealBoundary::contains()
                                → ST_Contains SQL
                           7. DB::transaction()
                              → Report::create()
                              → Report::setLocation()
                                → ST_SetSRID(ST_MakePoint)
                              → ExifStripper::process()
                                → Intervention Image re-encode
                              → addMedia() × N photos
                           8. event(ReportCreated)
                              → Broadcast on private-admin.reports
                           9. Return success + UUID
10. Show UUID            ←
```

### Report Status Update Flow

```
Admin (Filament)              Server                              External
────────────────              ──────                              ────────
1. Change status         →  2. transitionTo(newStatus)
                              3. ReportStatus::canTransitionTo()
                              4. Update report.status
                              5. Set rejection_reason (if rejected)
                              6. Save
                              7. activity() log
                              8. sendStatusNotification()
                                 → Mail::to(reporter_email)  →  9. Resend delivers
                                    → ReportStatusUpdated     →  10. Reporter inbox
                           ←  11. Filament refresh
```

### Real-time Notification Flow

```
Citizen submits               Reverb Server              Admin browser
────────────────              ─────────────              ──────────────
1. ReportCreated event
   → broadcastOn()       →  2. Pusher protocol
      private-admin.reports   3. Authorize channel
                               (channels.php checks
                                isAdmin || isManager)  ←  4. Echo.private()
                                                         5. .listen('.report.created')
                                                         6. Browser Notification
                                                         7. Filament notification
```

## Key Design Decisions

### Zero-auth Citizen Flow
Reports require no login — only an email for tracking. UUID-based tracking URLs are unguessable (128-bit). This removes the biggest friction point for pothole reporting.

### PostGIS Geofencing
All location data uses `GEOGRAPHY(POINT,4326)` for accurate distance calculations on a sphere. The `montreal_boundary` table stores a `GEOMETRY(POLYGON,4326)` of the Island of Montreal. `MontrealBoundary::contains()` uses `ST_Contains` to reject reports outside the boundary at submission time.

### Bilingual by Default
Every user-facing string is localized. Reports store `preferred_locale` at submission time; emails use that locale. Admin users store `locale` preference. The `SetLocale` middleware reads from session. The `/locale/{locale}` route switches session locale.

### PWA-First Citizen Interface
The citizen layout includes PWA meta tags, `manifest.json` link, `@laravelPWA` directive, and mobile-specific UX patterns (bottom nav, `safe-top`/`safe-bottom` insets, `btn-touch` targets).

### EXIF Stripping
All uploaded photos pass through `ExifStripper::process()` which re-encodes via Intervention Image, removing GPS coordinates and camera metadata that citizens shouldn't expose.

### Soft Deletes on Reports
Reports use `SoftDeletes` + `SoftDeletingScope` bypass for admins. This preserves audit trail while allowing cleanup of spam/rejected reports.

## Cross-cutting Concerns

### Authentication
- **Citizens:** None — zero-auth flow
- **Admins:** Fortify (email/password) + Filament 2FA (TOTP) + Passkeys
- **Rate limiting:** Login (5/min), 2FA (5/min), Passkeys (10/min), API lookup (60/min)

### Media Management
- Spatie Laravel Media Library on `Report` model
- Single collection: `report-photos` (accepts jpeg, png, gif, webp)
- EXIF stripped before storage via `ExifStripper`

### Activity Logging
- Spatie `LogsActivity` trait on `Report` model
- Tracks: `status`, `priority`, `admin_notes`, `rejection_reason`
- Log only dirty, don't submit empty logs
- Status transitions logged via explicit `activity('report_status')` call in `transitionTo()`

### Geofencing
- `MontrealBoundary` model with `ST_Contains` query
- Boundary seeded as simplified 14-point polygon of Island of Montreal
- `Report::validateGeofence()` throws `ValidationException` if outside
- `geofence_passed` boolean stored on report (set by form logic)

### Localization
- Locale stored at two levels: `Report.preferred_locale` (citizen) and `User.locale` (admin)
- `SetLocale` middleware reads `session('locale')` and sets `app()->getLocale()`
- All Filament widgets and table labels use `__()` translation
- Email templates use `$locale` parameter for per-recipient language

### Observability
- Laravel Telescope registered (gate-based access in non-local)
- Sensitive request details hidden in production
- Activity log on reports provides audit trail
