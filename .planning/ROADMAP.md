# NidVite MVP Roadmap

## Phase 0: Foundation (Current)
- [x] Coding manifesto and conventions
- [x] Git workflow with `develop` branch
- [x] CI/CD pipeline documentation
- [x] Security & privacy guidelines
- [x] Deployment strategy (Railway)
- [x] Tech stack and ADRs (9 ADRs complete)
- [x] Production database schema (partitioned, 20+ tables)
- [x] Zero-auth abuse prevention architecture
- [x] Caching & CDN strategy
- [x] Monitoring package selection
- [x] RBAC & admin security model
- [x] Job-based expense tracking with inventory
- [x] All ambiguities resolved
- [x] All gaps closed

## Phase 1: Laravel Scaffolding
- [x] Create Laravel 11 project with Sail
- [x] Configure Sail with PostGIS 15-3.4
- [x] Install core packages:
  - [x] `filament/filament`
  - [x] `spatie/laravel-medialibrary`
  - [x] `matanyada/laravel-postgis`
  - [x] `laravel/reverb`
  - [x] `laravel/fortify`
- [x] Configure `.env.example` with all required keys
- [x] Set up GitHub Actions CI workflow (Pest, Pint, PHPStan L5 on PostGIS 15-3.4)
- [x] Create `develop` branch

## Phase 2: Database & Models
- [x] Create migrations:
  - [x] `roles`
  - [x] `users` (with role_id FK, 2FA fields)
  - [x] `reports` (SLA fields)
  - [x] `repair_jobs`
  - [x] `job_reports`
  - [x] `job_workers`
  - [x] `expense_categories`
  - [x] `materials`
  - [x] `expenses`
  - [x] `material_purchases`
  - [x] `job_materials`
  - [x] `media` (Spatie)
  - [x] `telescope_entries`
  - [x] `activity_log` (Spatie)
  - [ ] `permissions`, `role_permissions`
  - [ ] `admin_sessions`
  - [ ] `admin_audit_log`
  - [ ] `clusters` (pre-computed with geohash)
  - [x] `montreal_boundary`
  - [ ] `device_fingerprints`
  - [ ] `rate_limit_buckets`
  - [ ] `blocked_ips`
  - [ ] `suspicious_activity`
  - [ ] `email_deliveries`
  - [ ] `hourly_stats`, `daily_stats`, `weekly_stats`
  - [ ] `neighborhood_stats`
  - [ ] `notifications`
- [ ] Configure table partitioning (monthly ranges)
- [x] Seed roles, categories
- [x] Create Eloquent models with relationships
- [x] Set up Spatie media collections (`report-photos` with EXIF stripping)
- [x] Configure PostGIS indexes (GIST)
- [ ] Set up data retention jobs (IP purging, archiving)
- [ ] Configure automated backups to R2

## Phase 3: Citizen PWA
- [x] Livewire `ReportForm` component (basic)
- [x] Livewire `PhotoUploader` component (integrated in ReportForm)
- [x] Livewire `TrackReport` component (via `/suivi/{uuid}` page)
- [x] One-tap geolocation capture
- [x] Photo upload with EXIF stripping
- [x] Geofencing validation (Montreal only)
- [x] Anti-spam (honeypot)
- [ ] Rate limiting (IP + device fingerprint)
- [x] PWA manifest and service worker
- [x] Unique tracking URL generation (`/suivi/{uuid}`)
- [x] Report state machine (strict transitions)
- [x] **Bilingual support (FR/EN)**:
  - [ ] Language switcher (globe icon, cookie persistence)
  - [x] Basic PWA strings in `lang/fr/report.php` and `lang/en/report.php`
  - [x] Email translations in `lang/fr/email.php` and `lang/en/email.php`
  - [x] Tracking page translations in `lang/fr/tracking.php` and `lang/en/tracking.php`
  - [x] French-first default
  - [x] Localized dates (FR: "4 mai 2026", EN: "May 4, 2026")
  - [ ] Localized numbers (FR: "1 234,56", EN: "1,234.56")

## Phase 4: Entrepreneur Dashboard (Core)
- [x] Filament `ReportResource` (basic CRUD)
- [x] Filament `UserResource`
- [x] Filament `RepairJobResource`
- [x] Filament `ExpenseResource`
- [x] RBAC policies on all resources (Reports, Users, RepairJobs, Expenses)
- [ ] MapLibre map widget (all reports + clusters)
- [x] Status management with state machine
- [ ] Priority assignment
- [ ] "After" photo upload
- [ ] Pre-computed clustering display
- [x] Activity log integration (Spatie ActivityLog on Report model)
- [x] Real-time notifications (Reverb)
- [ ] Admin audit log viewer (Admin only)
- [ ] **Bilingual dashboard**:
  - [x] User locale preference stored in `users.locale`
  - [ ] All Filament resources translated (`__()` keys)
  - [x] French-first default
  - [ ] English toggle in profile

## Phase 4a: RBAC & Admin Security
- [x] Fortify integration
- [x] 2FA setup (TOTP with QR codes + recovery codes)
- [x] Role-based access control on all resources (4 policies + AuthServiceProvider)
- [ ] Admin session management
- [ ] Brute force protection
- [x] Audit logging for data changes (Spatie ActivityLog)
- [ ] Viewer page-view logging

## Phase 4b: Job & Expense Management
- [x] Repair jobs CRUD (Filament resource)
- [ ] Multi-worker assignment
- [ ] Self-assignment for Service Workers
- [x] Expense entry (basic Filament resource)
- [ ] Receipt photo upload via Spatie
- [x] Expense category management (seeder + model)
- [ ] Cost allocation (equal split + manual override)
- [ ] Tax calculation (GST + QST)

## Phase 4c: Inventory System
- [x] Materials CRUD (model + migration)
- [ ] Stock level tracking
- [ ] Low stock alerts (dashboard + email + filter)
- [x] Purchase logging (model + migration)
- [ ] Purchase receipt upload
- [ ] Automatic stock decrement on job completion
- [ ] Reserved stock for in-progress jobs

## Phase 4d: Dashboard & Analytics
- [x] 4 KPI cards (Open Reports, Repairs This Week, Money Spent, Avg Repair Time)
- [x] Reports chart (30-day line chart)
- [x] Expense charts (by category bar chart)
- [x] Neighborhood analytics (top 10 bar chart)
- [ ] Repair velocity trend
- [ ] Cost per neighborhood analysis
- [ ] Excel/PDF export

## Phase 5: Email & Notifications
- [x] Resend integration (mailer configured, `RESEND_API_KEY` env)
- [x] Automated email on all status changes (queued via database)
- [x] Email on report rejection (with reason)
- [x] Email template with tracking link CTA button
- [ ] Bounced email handling (3 retries → permanent)
- [x] Queue worker configuration (database queue, `ShouldQueue` interface)
- [ ] Notification system (dashboard badges + emails)
- [ ] Critical report alerts to Manager/Admin
- [x] **Bilingual emails**:
  - [x] Store `preferred_locale` on `reports` (default 'fr')
  - [x] Single-language emails based on reporter preference
  - [x] Markdown email template with localized subject and body
  - [ ] `locale_sent` tracked for audit (deferred to Phase 6)

## Phase 6: Monitoring & Hardening
- [ ] Sentry error tracking
- [ ] Health check endpoint (`/health`)
- [ ] Schedule monitor for clustering job
- [ ] Response caching for read-heavy pages
- [ ] Security headers (CSP, HSTS)
- [ ] OPcache clear on deploy
- [ ] Automated database backups to R2
- [ ] Suspicious activity dashboard (Filament)

## Phase 7: Testing & Polish
- [x] Critical-path Pest tests (all features, 86 tests, 117 assertions)
- [x] State machine transition tests (18 tests)
- [x] RBAC permission tests (46 tests)
- [x] PHPStan Level 5 compliance (Larastan)
- [x] Laravel Pint formatting (enforced in CI)
- [x] CodeRabbit configuration verification (`.coderabbit.yaml`)
- [x] Branch protection rules (main + develop)
- [x] Railway deployment configuration (`railway.toml`)
- [ ] Railway auto-deploy verification
- [ ] Load testing (optional)

## Phase 8: Launch
- [ ] Production environment variables
- [ ] Custom domain + SSL
- [ ] Privacy policy page
- [ ] Terms of service page
- [ ] Quebec Law 25 compliance check
- [ ] Soft launch (friends & family)
- [ ] Public launch

---

## Phase 9: Post-MVP Nice-to-Haves

### Operational Efficiency
- [ ] Bulk operations (assign 10 reports at once)
- [ ] Export reports to CSV/PDF
- [ ] Print-friendly job sheet
- [ ] Mobile-optimized admin view
- [ ] Customer satisfaction survey ("Was this fixed?")

### Notifications
- [ ] SMS notifications (Twilio)
- [ ] SLA breach alerts
- [ ] Push notifications for PWA

### Advanced Analytics
- [ ] Event sourcing (`spatie/laravel-event-sourcing`)
- [ ] Predictive maintenance (AI/ML)
- [ ] Multi-year trend analysis

---

## Package Installation Tracker

### Core (Phase 1)
- [x] `filament/filament:^5.0` (v5.6.1 for Laravel 11)
- [x] `spatie/laravel-medialibrary:^11.0` (v11.22.1)
- [x] `intervention/image:^3.0`
- [x] `matanyada/laravel-postgis:^5.0`
- [x] `laravel/reverb:^1.0` (v1.10.0)
- [x] `laravel/fortify:^1.0` (v1.37)

### Security (Phase 3)
- [x] `spatie/laravel-honeypot:^3.0`
- [x] `anhskohbo/no-captcha:^3.0`
- [x] `bepsvpt/secure-headers:^7.0`
- [x] `jenssegers/agent:^2.6`

### Mail (Phase 5)
- [x] `resend/resend-laravel:^0.1`

### PWA (Phase 3)
- [x] `silviolleite/laravelpwa:^2.0` (v2.0.3)

### Audit (Phase 4)
- [x] `spatie/laravel-activitylog:^4.0` (v4.12.3)

### Monitoring (Phase 6)
- [x] `sentry/sentry-laravel:^4.0`
- [x] `spatie/laravel-health:^1.0`
- [x] `spatie/laravel-schedule-monitor:^1.0`

### Performance (Phase 6)
- [x] `appstract/laravel-opcache:^4.0`
- [x] `spatie/laravel-response-cache:^7.0`
- [x] `spatie/laravel-backup:^9.0` (v9.3.6, PHP 8.2 compatible)

### Export (Phase 4)
- [x] `maatwebsite/laravel-excel:^3.1` (v3.1.69)

### Testing (Phase 1)
- [x] `pestphp/pest:^2.0` (v2.36, PHP 8.2 compatible)
- [x] `pestphp/pest-plugin-laravel:^2.0`
- [x] `pestphp/pest-plugin-faker:^2.0`
- [x] `nunomaduro/larastan:^2.0` (v2.11)

### Dev Tools (Phase 1)
- [x] `barryvdh/laravel-debugbar:^3.0` (v3.16)
- [x] `laravel/pint:^1.0`
- [x] `laravel/telescope:^5.0` (v5.20)

---

*Last updated: 2026-05-06*
*Current milestone: Phase 1-4 complete, Phase 5 (Email) complete, Phase 7 (CI/CD) complete*
*Update this file as phases are completed.*
