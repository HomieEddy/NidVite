# Codebase Concerns

> Mapped: 2026-05-06 | Focus: concerns

## Critical Concerns

These issues will cause failures in production or represent significant security gaps.

### Security Risks

| Risk | Severity | Current State | Required Action |
|------|----------|---------------|----------------|
| No rate limiting on report submission | **HIGH** | Only honeypot protects the form; no IP/device throttling on `/signaler` route | Add rate-limit middleware to `routes/web.php` for report creation endpoint |
| reCAPTCHA not enforced | **HIGH** | `anhskohbo/no-captcha` installed, `config/captcha.php` exists, but the report form has no CAPTCHA validation — `recaptcha_response` property exists on the component but is never validated in `submit()` | Add `'recaptcha_response' => 'required|captcha'` to validation rules in `⚡report-form.blade.php` `submit()` method |
| No brute force lockout | **MEDIUM** | `FortifyServiceProvider.php` limits to 5 requests/minute per email+IP, but there is no lockout period (config.json calls for "5 attempts per 15 min lockout") | Add `RateLimiter::for('login')` with decaying limit: `Limit::perMinutes(15, 5)` and return `Limit::perMinute(0)` after threshold |
| Secure headers not configured | **MEDIUM** | `bepsvpt/secure-headers` installed but no `config/secure-headers.php` published — headers are not being applied | Run `php artisan vendor:publish --tag=secure-headers-config` and configure CSP, HSTS, X-Frame-Options |
| IP tracking columns removed | **MEDIUM** | Migration `2026_05_06_050000` dropped `ip_address_hash`, `ip_address_raw`, `user_agent_hash` — no abuse tracking possible | Implement middleware to hash IP + user agent and store on report creation before the model is saved |
| No device fingerprinting | **MEDIUM** | config.json specifies `device_fingerprinting: true` but no `DeviceFingerprint` model, no middleware, no `device_fingerprints` table | Create fingerprint middleware + migration + model |
| Session timeout too long for admin | **LOW** | `SESSION_LIFETIME=120` minutes (2 hours) for all sessions; config.json specifies 15-minute admin session timeout | Add admin-specific session timeout middleware for Filament panel |
| Viewer can view all expenses | **LOW** | `ExpensePolicy::viewAny()` and `view()` return `true` for all roles — viewers see all financial data | Restrict expense viewing to admin/accountant/manager |

### Configuration Gaps

| Package | Missing Config | Impact | Priority |
|---------|---------------|--------|----------|
| `sentry/sentry-laravel` ^4.25 | No `config/sentry.php`, no `SENTRY_DSN` in `.env.example` | Zero error tracking in production — errors silently fail | **HIGH** |
| `spatie/laravel-health` ^1.39 | No `config/health.php`, no health checks registered | No uptime/health monitoring — Railway healthcheck only hits `/up` (Laravel default) | **HIGH** |
| `spatie/laravel-backup` ^9.0 | No `config/backup.php`, no scheduled backup command | No automated backups — data loss risk on DB failure | **HIGH** |
| `spatie/laravel-schedule-monitor` ^4.3 | No config, no scheduled tasks registered in `routes/console.php` | Package is dead weight; monitoring won't work without scheduled tasks | **MEDIUM** |
| `bepsvpt/secure-headers` ^9.1 | No `config/secure-headers.php` published | Security headers not applied to responses | **MEDIUM** |
| `maatwebsite/excel` ^3.1 | No export classes, no Filament export actions | Export feature declared in config.json but completely unimplemented | **LOW** |

## Technical Debt

### Unfinished Features

| Feature | Phase | Status | Blocker |
|---------|-------|--------|---------|
| Rate limiting (IP + device fingerprint) | Phase 3 | Not started | Requires `device_fingerprints` migration + middleware |
| Photo deduplication (perceptual hash) | Phase 3 | Not started | No service class or hash storage |
| reCAPTCHA enforcement | Phase 4a | Package installed, not enforced | Add validation rule to report form |
| Brute force protection (5/15min lockout) | Phase 4a | Login limited to 5/min but no lockout | Enhance `FortifyServiceProvider` rate limiter |
| Admin session management | Phase 4a | Not started | Requires `admin_sessions` migration |
| Viewer page-view logging | Phase 4a | Not started | No logging mechanism |
| MapLibre interactive map in Filament | Phase 4 | ReportsMap widget is just an iframe | Needs dedicated Filament map widget |
| Priority assignment UI | Phase 4 | Priority field exists on Report model but no Filament UI action | Add Filament action to ReportResource |
| "After" photo upload | Phase 4 | No `after_photos` media collection | Add collection to Report model + Filament UI |
| MaterialResource (Filament) | Phase 4c | Material model exists, no Filament resource | Create Filament CRUD resource |
| ReportCategoryResource (Filament) | Phase 4 | ReportCategory model exists, no Filament resource | Create Filament CRUD resource |
| Stock level tracking UI | Phase 4c | Model has `current_stock`/`reserved_stock`, no UI | Create Filament resource + widgets |
| Low stock alerts | Phase 4c | `min_stock_alert` field exists, no alert system | Add scheduled check + notification |
| Multi-worker assignment UI | Phase 4b | `job_workers` pivot exists, no Filament repeater | Add relationship repeater to RepairJobForm |
| Cost allocation | Phase 4b | `cost_allocation_percentage` pivot field exists, no UI | Add split UI to RepairJobForm |
| Tax calculation automation | Phase 4b | `tax_rate`/`tax_amount` fields exist, not auto-calculated | Add JS/PHP calculation on ExpenseForm |
| Bounced email handling | Phase 5 | No retry logic, no `email_deliveries` table | Create migration + job |
| Notification dashboard badges | Phase 5 | No notification system | Create `notifications` table + Filament badges |
| Export to Excel/PDF | Phase 4d | Package installed, no export classes | Create ExportAction on Filament resources |
| Suspicious activity dashboard | Phase 6 | No detection logic, no Filament page | Create migration + detection service |
| Data retention / IP purging | Phase 2 | No scheduled jobs, no purging command | Create artisan command + scheduler entry |

### Missing Infrastructure

| Item | Purpose | Priority |
|------|---------|----------|
| `permissions` + `role_permissions` tables | Granular permission system beyond role-slug checks | **MEDIUM** — current `isAdmin()`/`isManager()` approach works for 5 roles but won't scale |
| `clusters` table + pre-computed clustering | Map performance at 100k+ reports | **HIGH** — GeoJSON endpoint will degrade with scale |
| `admin_audit_log` table (separate from ActivityLog) | Dedicated admin action tracking | **LOW** — Spatie ActivityLog partially covers this |
| `hourly_stats`/`daily_stats`/`weekly_stats` tables | Pre-aggregated analytics for dashboard performance | **MEDIUM** — current widget queries hit live data |
| `notifications` table | In-app notification badges for admin | **MEDIUM** — feature gap |
| `rate_limit_buckets` table | Persistent rate limit storage | **LOW** — Redis or cache-based could work |
| `blocked_ips` + `suspicious_activity` tables | Abuse prevention | **MEDIUM** — IP columns were removed |
| Queue worker process on Railway | Processing `ShouldQueue` mailables | **HIGH** — emails will not send without worker |
| Scheduled task runner on Railway | Backup, stats aggregation, data retention | **HIGH** — `routes/console.php` has only `inspire` command |
| Redis for cache/session/queue in production | Performance at scale | **MEDIUM** — `database` driver works for MVP but not for 100k reports |

### Code Quality Concerns

**Anonymous Livewire component (494 lines):**
- File: `resources/views/components/⚡report-form.blade.php`
- Contains PHP class definition (lines 1-181) + full Blade template (lines 182-494) in a single file
- Mixes business logic (geofence validation, DB transaction, media upload, event dispatch) with presentation
- Hardcoded neighborhood/borough lists instead of database-driven
- No separation of concerns — difficult to test in isolation (no named PHP class)
- Refactor: Extract to `app/Livewire/ReportForm.php` class + separate Blade view

**Nominatim geocoding without rate limiting or error handling:**
- File: `resources/views/components/⚡report-form.blade.php` lines 229-272
- Direct `fetch()` to OpenStreetMap Nominatim API from browser — violates Nominatim's usage policy (1 req/sec, requires meaningful User-Agent)
- No backoff, no error feedback to user on geocoding failure
- Should be proxied through a server-side endpoint with caching

**Email sent synchronously in model method:**
- File: `app/Models/Report.php` line 188-189
- `Mail::to(...)->send(...)` called directly in `sendStatusNotification()` — the `ReportStatusUpdated` mailable implements `ShouldQueue`, so `send()` will queue it, but the pattern is fragile
- If someone calls `Mail::to(...)->send(...)` it queues, but `->sendNow()` would not — should use `Mail::to(...)->queue(...)` explicitly

**No Job classes:**
- ROADMAP reports 0 Jobs. All async work relies on `ShouldQueue` on the Mailable
- Backup, stats aggregation, data retention, email retry — all need dedicated Job classes
- Location: None exist; should be `app/Jobs/`

**Stats computed on every request:**
- File: `routes/web.php` lines 8-25
- Homepage computes `Report::count()`, `Report::where('status', 'repaired')->count()`, AVG repair time on every page load
- No caching, no pre-aggregation
- Will degrade significantly as report count grows

**GeoJSON endpoint returns all non-spam, non-rejected reports:**
- File: `app/Http/Controllers/MapController.php` lines 27-41
- No pagination, no bounding box filter, no clustering
- At 100k reports, this will return megabytes of JSON
- Only rate-limited route: `/api/reports/{uuid}/lookup` at 60/min — the GeoJSON endpoint has no throttle

## Production Readiness Blockers

Items that **must** be resolved before deploying to production:

1. **Queue worker not running** — `QUEUE_CONNECTION=database` in `.env.example`, but `railway.toml` only runs `php artisan serve`. Mailables implementing `ShouldQueue` will pile up in `jobs` table without `php artisan queue:work`. Add worker process to Railway config.

2. **Scheduler not running** — `routes/console.php` has only `inspire`. No `php artisan schedule:run` in Railway. Backup, stats, and data retention tasks cannot execute.

3. **No Sentry DSN** — `sentry/sentry-laravel` is installed but `config/sentry.php` is not published and `SENTRY_DSN` is not in `.env.example`. Production errors will be invisible.

4. **No backup configuration** — `spatie/laravel-backup` is installed but no config published, no backup schedule, no R2 target. Single DB failure = total data loss.

5. **File storage = local** — `FILESYSTEM_DISK=local` in `.env.example`. Production needs `r2` or S3-compatible storage for media uploads. No R2 config in `.env.example`.

6. **No `RESEND_API_KEY` in `.env.example`** — Resend is configured for production email but the API key field is blank. Deploy without it = no email delivery.

7. **reCAPTCHA keys missing** — `NOCAPTCHA_SECRET` and `NOCAPTCHA_SITEKEY` not in `.env.example`. Even if enforced, it won't work without keys.

8. **Session security** — `SESSION_ENCRYPT=false`, `SESSION_SECURE_COOKIE` not set. In production over HTTPS, cookies must be secure-only and encrypted.

9. **`php artisan serve` for production** — `railway.toml` uses `php artisan serve` which is the dev server. Production should use Octane, RoadRunner, or proper FPM + Nginx.

10. **No HTTPS enforcement** — No middleware forces HTTPS. `APP_URL` in `.env.example` is `http://localhost:8091`. Production must enforce HTTPS.

## Performance Concerns

### Unbounded GeoJSON Query
- **Problem**: `MapController::geojson()` loads ALL non-spam, non-rejected reports with locations into a single JSON response
- **Files**: `app/Http/Controllers/MapController.php` lines 27-41
- **Cause**: No bounding box filtering, no server-side clustering, no pagination
- **Current limit**: Works at ~1k reports; will time out at 10k+ reports
- **Improvement path**: Add bounding box parameter + server-side ST_ClusterDBSCAN clustering + tile-based loading

### Homepage Stats Without Caching
- **Problem**: 3 COUNT queries + 1 AVG aggregate on every homepage visit
- **Files**: `routes/web.php` lines 8-25
- **Cause**: No cache layer between DB and response
- **Improvement path**: Cache stats for 5 minutes with `Cache::remember()`, invalidate on report status change

### Database Driver for Cache + Queue
- **Problem**: `CACHE_STORE=database` and `QUEUE_CONNECTION=database` — polling-based queue, no atomic cache operations
- **Files**: `config/cache.php`, `config/queue.php`
- **Cause**: Redis available in docker-compose but not configured as cache/queue driver
- **Improvement path**: Set `CACHE_STORE=redis` and `QUEUE_CONNECTION=redis` for production; keep `database` for local dev simplicity

### No Database Partitioning
- **Problem**: config.json specifies `database_partitioning: "monthly-range"` on `reports` table; not implemented
- **Files**: `database/migrations/2024_01_01_000004_create_reports_table.php` — standard table, no `PARTITION BY RANGE`
- **Impact**: At 100k+ reports, queries filtering by date range will scan the entire table
- **Improvement path**: Implement monthly partitioning via migration, or defer until scale requires it

### Missing Indexes
- **Current indexes on reports**: `uuid`, `reporter_email`, `neighborhood`, `borough`, `status`, `priority`, `is_spam`, `created_at`, `status+created_at`, `email+created_at`, `neighborhood+status`, `deleted_at`, GIST on location
- **Missing**: Composite index on `(borough, status, created_at)` for neighborhood analytics widget; `(is_spam, status, location)` for map GeoJSON query

### N+1 Potential in Filament Resources
- **Problem**: ReportResource likely loads `category` relationship on each row display without eager loading
- **Files**: `app/Filament/Resources/Reports/Tables/ReportsTable.php` (likely)
- **Cause**: Default Filament behavior without explicit `$table->columns()` using `relationship()` with eager load
- **Improvement path**: Verify Filament is using `relationship()` directives correctly; add `->eagerlyLoadRelationships()` where needed

## Data Integrity Risks

### Soft Deletes on Reports
- **Risk**: `Report` uses `SoftDeletes` trait. Soft-deleted reports remain in DB with `deleted_at` set but are invisible to Eloquent queries.
- **Files**: `app/Models/Report.php` line 26
- **Implication**: Trashed reports still appear in GeoJSON endpoint unless explicitly excluded (current query doesn't use `withTrashed()` so this is OK)
- **Recovery gap**: Only admins can restore reports, but there's no Filament trash view or restore action configured

### No Foreign Key on `expenses.vendor_id`
- **Risk**: The `expenses` table has `vendor_id` but there may be no FK constraint at the DB level (migration `2026_05_06_070000_create_vendors_table.php` needs verification)
- **Files**: `database/migrations/2024_01_01_000010_create_expenses_table.php`
- **Impact**: Orphan expense records if vendor is deleted

### Reporter Email Nullable
- **Risk**: Migration `2026_05_05_200000_make_reporter_email_nullable.php` made email optional
- **Files**: `app/Models/Report.php` `$fillable` includes `reporter_email`
- **Impact**: Reports without email cannot receive status update notifications — `sendStatusNotification()` already handles `null` by returning early, but this means citizens get no feedback loop
- **Business implication**: The "frictionless" model allows anonymous reports, but the email loop is a core feature gap

### No Unique Constraint on Report Submissions
- **Risk**: No deduplication — same user can submit infinite reports for the same pothole at the same location
- **Files**: `resources/views/components/⚡report-form.blade.php` — no duplicate detection
- **Impact**: Spam/abuse vector; planned photo deduplication (perceptual hash) would help but is not implemented

### Expense Tax Fields Not Auto-Computed
- **Risk**: `tax_rate`, `tax_amount`, `subtotal`, `total` fields are on the Expense model but not calculated automatically
- **Files**: `app/Models/Expense.php` — all are in `$fillable` as manual input
- **Impact**: Data inconsistency if admin enters wrong calculations; Quebec GST+QST at 14.975% should be auto-applied

### Pivot Table `job_reports` Has No Unique Constraint
- **Risk**: Same report can be attached to the same repair job multiple times
- **Files**: `database/migrations/2024_01_01_000006_create_job_reports_table.php`
- **Impact**: Double-counting in cost allocation

## Dependency Risks

### Missing from composer.json (documented in config.json)

| Package | Config.json Expectation | Risk |
|---------|------------------------|------|
| `spatie/laravel-response-cache` ^7.0 | Listed in `performance` packages | Not installed — no response caching available |
| `appstract/laravel-opcache` ^4.0 | Listed in `performance` packages | Not installed — no OPcache clear on deploy |
| `matanyada/laravel-postgis` | Listed in `core` packages | Not installed — PostGIS queries use raw `DB::statement()` instead of Eloquent spatial accessors |
| `filament/spatie-laravel-media-library-plugin` | Listed in `core` packages | Not installed — Filament media upload may need manual integration |
| `pestphp/pest-plugin-livewire` ^3.0 | Listed in `testing` packages | ^2.0 installed instead — Livewire component testing may lack features |

### Version Mismatch

| Item | Expected | Actual | Impact |
|------|----------|--------|--------|
| `pestphp/pest` | ^3.0 (config.json) | ^2.0 (composer.json) | Pest 3 has different API surface |
| `filament/filament` | ^3.0 (config.json `admin`) | ^5.6 (composer.json) | Config.json is stale — Filament v5 is actually installed |
| PHP version | 8.3 (config.json) | ^8.2 (composer.json) | CI uses PHP 8.2 — should match 8.3 |

### Installed but Unconfigured (Dead Weight)

| Package | Weight | Action |
|---------|--------|--------|
| `sentry/sentry-laravel` ^4.25 | Adds ~2MB + HTTP overhead on every request if DSN not set | Publish config + add DSN, or remove if not using |
| `spatie/laravel-health` ^1.39 | Registers service provider but no checks | Publish config + register checks |
| `spatie/laravel-schedule-monitor` ^4.3 | No scheduled tasks to monitor | Add schedule tasks first, then configure |
| `spatie/laravel-backup` ^9.0 | No backup command scheduled | Publish config + schedule backup command |
| `maatwebsite/excel` ^3.1 | No export classes | Create export classes or remove if post-MVP |

### Dev Tools in Production Risk

| Package | Concern |
|---------|---------|
| `laravel/telescope` ^5.0 | Must be disabled in production — exposes sensitive DB queries, cache, request data |
| `barryvdh/laravel-debugbar` ^3.0 | Must be disabled in production — `DEBUGBAR_ENABLED=false` in `.env.example` but needs explicit guard |

## Test Coverage Gaps

| Untested Area | What's Missing | Files Involved | Risk | Priority |
|---------------|---------------|----------------|------|----------|
| Vendor CRUD | No Filament Vendor resource tests | `app/Filament/Resources/Vendors/` | Admin can create vendors without validation verification | **MEDIUM** |
| Material inventory | No Filament Material resource tests | `app/Models/Material.php` | Stock tracking untested | **MEDIUM** |
| ReportCategory management | No Filament CRUD test | `app/Models/ReportCategory.php` | Category admin untested | **LOW** |
| Filament admin panel access | No test verifying non-admins can't access `/admin` | `app/Providers/Filament/AdminPanelProvider.php` | Auth bypass risk | **HIGH** |
| PWA / service worker | No test for manifest, offline behavior | `public/manifest.json`, service worker | PWA may not install correctly | **LOW** |
| 2FA / passkey auth | No test for TOTP enrollment, passkey flow | `app/Actions/Fortify/` | Auth security regression risk | **HIGH** |
| Map functionality | No test for GeoJSON endpoint, bounding box | `app/Http/Controllers/MapController.php` | Map may return wrong data | **MEDIUM** |
| Email notification content | No test verifying correct FR/EN email body | `app/Mail/ReportStatusUpdated.php` | Wrong language emails sent | **MEDIUM** |
| Rate limiting | No test for submission throttling | `routes/web.php` | Spam flood possible | **HIGH** |
| File upload security | No test for malicious file upload (non-image mime type bypass) | `⚡report-form.blade.php` | MIME type spoofing risk | **HIGH** |
| Queue job processing | No test for queued email delivery | `app/Mail/ReportStatusUpdated.php` | Emails silently fail in queue | **MEDIUM** |
| Concurrent report submission | No test for race conditions in report creation | `⚡report-form.blade.php` | Duplicate UUID risk (mitigated by `Str::uuid()` but DB unique constraint not battle-tested) | **LOW** |

## Concern Priority Matrix

| Concern | Impact | Effort | Priority |
|---------|--------|--------|----------|
| Queue worker not configured on Railway | Production emails won't send | 1h | **P0 — Ship Blocker** |
| Scheduler not configured on Railway | Backups/stats/retention can't run | 1h | **P0 — Ship Blocker** |
| Sentry not configured | No production error visibility | 2h | **P0 — Ship Blocker** |
| Backup not configured | Data loss risk | 2h | **P0 — Ship Blocker** |
| reCAPTCHA not enforced | Spam flood vulnerability | 1h | **P0 — Ship Blocker** |
| No rate limiting on report form | Abuse/spam vector | 3h | **P0 — Ship Blocker** |
| File storage = local (not R2) | Media lost on redeploy | 2h | **P0 — Ship Blocker** |
| `php artisan serve` in railway.toml | Dev server in production | 1h | **P0 — Ship Blocker** |
| Session not secure/encrypted for HTTPS | Cookie hijacking risk | 1h | **P1 — Pre-Launch** |
| Secure headers not published | Missing HSTS, CSP, X-Frame-Options | 1h | **P1 — Pre-Launch** |
| Brute force lockout incomplete | Login abuse possible | 2h | **P1 — Pre-Launch** |
| IP tracking removed (no abuse trail) | Can't investigate spam origins | 4h | **P1 — Pre-Launch** |
| GeoJSON endpoint unbounded | Performance cliff at scale | 8h | **P1 — Pre-Launch** |
| Homepage stats uncached | DB load on every visit | 1h | **P1 — Pre-Launch** |
| Missing policies (Vendor, Material, Category) | Unauthorized CRUD access | 3h | **P1 — Pre-Launch** |
| Filament admin access untested | Auth bypass possible | 2h | **P1 — Pre-Launch** |
| 2FA/passkey auth untested | Security regression risk | 4h | **P1 — Pre-Launch** |
| Anonymous Livewire component (494 lines) | Hard to maintain/test | 4h | **P2 — Post-Launch** |
| Nominatim geocoding from client | Violates API policy | 3h | **P2 — Post-Launch** |
| No database partitioning | Scale limit at ~100k reports | 8h | **P2 — Post-Launch** |
| Expense tax not auto-computed | Data inconsistency risk | 3h | **P2 — Post-Launch** |
| Reporter email nullable breaks feedback loop | Citizens don't get updates | 2h | **P2 — Post-Launch** |
| Config.json stale (wrong Filament version, Pest version) | Planning docs don't match reality | 1h | **P2 — Post-Launch** |
| No Redis for cache/queue in production | Performance ceiling | 3h | **P2 — Post-Launch** |
| No `after_photos` media collection | Before/after repair tracking incomplete | 2h | **P3 — Nice-to-Have** |
| No MaterialResource Filament CRUD | Inventory management gap | 4h | **P3 — Nice-to-Have** |
| No ReportCategoryResource | Category management gap | 2h | **P3 — Nice-to-Have** |
| Photo deduplication | Duplicate report mitigation | 8h | **P3 — Nice-to-Have** |

---

*Concerns audit: 2026-05-06*
