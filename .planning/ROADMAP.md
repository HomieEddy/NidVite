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
- [ ] Set up GitHub Actions CI workflow
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
  - [ ] `permissions`, `role_permissions`
  - [ ] `admin_sessions`
  - [ ] `admin_audit_log`
  - [ ] `clusters` (pre-computed with geohash)
  - [ ] `montreal_boundary`
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
- [ ] Set up Spatie media collections (with perceptual hash)
- [x] Configure PostGIS indexes (GIST)
- [ ] Set up data retention jobs (IP purging, archiving)
- [ ] Configure automated backups to R2

## Phase 3: Citizen PWA
- [x] Livewire `ReportForm` component (basic)
- [ ] Livewire `PhotoUploader` component
- [ ] Livewire `TrackReport` component
- [x] One-tap geolocation capture
- [ ] Photo upload with EXIF stripping
- [ ] Geofencing validation (Montreal only)
- [x] Anti-spam (honeypot)
- [ ] Rate limiting (IP + device fingerprint)
- [x] PWA manifest and service worker
- [ ] Unique tracking URL generation
- [ ] Report state machine (strict transitions)
- [ ] **Bilingual support (FR/EN)**:
  - [ ] Language switcher (globe icon, cookie persistence)
  - [x] Basic PWA strings in `lang/fr/report.php` and `lang/en/report.php`
  - [x] French-first default
  - [ ] Localized dates (FR: "4 mai 2026", EN: "May 4, 2026")
  - [ ] Localized numbers (FR: "1 234,56", EN: "1,234.56")

## Phase 4: Entrepreneur Dashboard (Core)
- [x] Filament `ReportResource` (basic CRUD)
- [x] Filament `UserResource`
- [x] Filament `RepairJobResource`
- [x] Filament `ExpenseResource`
- [ ] RBAC policies on all resources
- [ ] MapLibre map widget (all reports + clusters)
- [ ] Status management with state machine
- [ ] Priority assignment
- [ ] "After" photo upload
- [ ] Pre-computed clustering display
- [ ] Activity log integration
- [ ] Real-time notifications (Reverb)
- [ ] Admin audit log viewer (Admin only)
- [ ] **Bilingual dashboard**:
  - [x] User locale preference stored in `users.locale`
  - [ ] All Filament resources translated (`__()` keys)
  - [x] French-first default
  - [ ] English toggle in profile

## Phase 4a: RBAC & Admin Security
- [x] Fortify integration
- [ ] 2FA setup
- [ ] Role-based access control on all resources
- [ ] Admin session management
- [ ] Brute force protection
- [ ] Audit logging for all data changes
- [ ] Viewer page-view logging

## Phase 4b: Job & Expense Management
- [ ] Repair jobs CRUD
- [ ] Multi-worker assignment
- [ ] Self-assignment for Service Workers
- [ ] Expense entry (all optional fields)
- [ ] Receipt photo upload via Spatie
- [ ] Expense category management
- [ ] Cost allocation (equal split + manual override)
- [ ] Tax calculation (GST + QST)

## Phase 4c: Inventory System
- [ ] Materials CRUD
- [ ] Stock level tracking
- [ ] Low stock alerts (dashboard + email + filter)
- [ ] Purchase logging with receipt upload
- [ ] Automatic stock decrement on job completion
- [ ] Reserved stock for in-progress jobs

## Phase 4d: Dashboard & Analytics
- [ ] 4 KPI cards (Open Reports, Repair Velocity, Money Spent, Repairs This Week)
- [ ] Expense charts (ApexCharts)
- [ ] Repair velocity trend
- [ ] Cost per neighborhood analysis
- [ ] Excel/PDF export

## Phase 5: Email & Notifications
- [ ] Resend integration
- [ ] Automated email on status change to "repaired"
- [ ] Email on report rejection (with reason)
- [ ] Email template with tracking link
- [ ] Bounced email handling (3 retries → permanent)
- [ ] Queue worker configuration
- [ ] Notification system (dashboard badges + emails)
- [ ] Critical report alerts to Manager/Admin
- [ ] **Bilingual emails**:
  - [ ] Store `preferred_locale` on `reports` (default 'fr')
  - [ ] Single-language emails based on reporter preference
  - [ ] French subject/body stored in `email_deliveries.subject_fr`, `.body_fr`
  - [ ] English subject/body stored in `.subject_en`, `.body_en`
  - [ ] `locale_sent` tracked for audit

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
- [ ] Critical-path Pest tests (all features)
- [ ] State machine transition tests
- [ ] RBAC permission tests
- [ ] PHPStan Level 5 compliance
- [ ] Laravel Pint formatting
- [ ] CodeRabbit configuration verification
- [ ] Branch protection rules
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

*Last updated: 2026-05-05*
*Update this file as phases are completed.*
