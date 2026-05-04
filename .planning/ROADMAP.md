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
- [ ] Create Laravel 11 project with Sail
- [ ] Configure Sail with PostGIS 15-3.4
- [ ] Install core packages:
  - [ ] `filament/filament`
  - [ ] `spatie/laravel-medialibrary`
  - [ ] `matanyada/laravel-postgis`
  - [ ] `laravel/reverb`
  - [ ] `laravel/fortify`
- [ ] Configure `.env.example` with all required keys
- [ ] Set up GitHub Actions CI workflow
- [ ] Create `develop` branch

## Phase 2: Database & Models
- [ ] Create migrations:
  - [ ] `roles`, `permissions`, `role_permissions`
  - [ ] `users` (with role_id FK, 2FA fields)
  - [ ] `admin_sessions`
  - [ ] `admin_audit_log`
  - [ ] `reports` (partitioned by month, SLA fields)
  - [ ] `clusters` (pre-computed with geohash)
  - [ ] `montreal_boundary`
  - [ ] `device_fingerprints`
  - [ ] `rate_limit_buckets`
  - [ ] `blocked_ips`
  - [ ] `suspicious_activity`
  - [ ] `email_deliveries`
  - [ ] `hourly_stats`, `daily_stats`, `weekly_stats`
  - [ ] `neighborhood_stats`
  - [ ] `report_categories`
  - [ ] `notifications`
- [ ] Configure table partitioning (monthly ranges)
- [ ] Seed roles, permissions, categories, Montreal boundary
- [ ] Create Eloquent models with relationships
- [ ] Set up Spatie media collections (with perceptual hash)
- [ ] Configure PostGIS indexes (GIST, geohash)
- [ ] Set up data retention jobs (IP purging, archiving)
- [ ] Configure automated backups to R2

## Phase 3: Citizen PWA
- [ ] Livewire `ReportForm` component
- [ ] Livewire `PhotoUploader` component
- [ ] Livewire `TrackReport` component
- [ ] One-tap geolocation capture
- [ ] Photo upload with EXIF stripping
- [ ] Geofencing validation (Montreal only)
- [ ] Anti-spam (honeypot + reCAPTCHA)
- [ ] Rate limiting (IP + device fingerprint)
- [ ] PWA manifest and service worker
- [ ] Unique tracking URL generation
- [ ] Report state machine (strict transitions)
- [ ] **Bilingual support (FR/EN)**:
  - [ ] Language switcher (globe icon, cookie persistence)
  - [ ] All PWA strings in `lang/fr.json` and `lang/en.json`
  - [ ] French-first default, English toggle
  - [ ] Localized dates (FR: "4 mai 2026", EN: "May 4, 2026")
  - [ ] Localized numbers (FR: "1 234,56", EN: "1,234.56")

## Phase 4: Entrepreneur Dashboard (Core)
- [ ] Filament `ReportResource` with RBAC policies
- [ ] MapLibre map widget (all reports + clusters)
- [ ] Status management with state machine
- [ ] Priority assignment
- [ ] "After" photo upload
- [ ] Pre-computed clustering display
- [ ] Activity log integration
- [ ] Real-time notifications (Reverb)
- [ ] Admin audit log viewer (Admin only)
- [ ] **Bilingual dashboard**:
  - [ ] User locale preference stored in `users.locale`
  - [ ] All Filament resources translated (`__()` keys)
  - [ ] French-first default, English toggle in profile
  - [ ] Dynamic content (`expense_categories`, `notifications`) bilingual

## Phase 4a: RBAC & Admin Security
- [ ] Fortify integration with 2FA
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
- [ ] `filament/filament:^3.0`
- [ ] `filament/spatie-laravel-media-library-plugin:^3.0`
- [ ] `spatie/laravel-medialibrary:^11.0`
- [ ] `intervention/image:^3.0`
- [ ] `matanyada/laravel-postgis:^5.0`
- [ ] `laravel/reverb:^1.0`
- [ ] `laravel/fortify:^1.0`

### Security (Phase 3)
- [ ] `spatie/laravel-honeypot:^3.0`
- [ ] `anhskohbo/no-captcha:^3.0`
- [ ] `bepsvpt/secure-headers:^7.0`
- [ ] `jenssegers/agent:^2.6`

### Mail (Phase 5)
- [ ] `resend/resend-laravel:^0.1`

### PWA (Phase 3)
- [ ] `silviolleite/laravelpwa:^2.0`

### Audit (Phase 4)
- [ ] `spatie/laravel-activitylog:^4.0`

### Monitoring (Phase 6)
- [ ] `sentry/sentry-laravel:^4.0`
- [ ] `spatie/laravel-health:^1.0`
- [ ] `spatie/laravel-schedule-monitor:^1.0`

### Performance (Phase 6)
- [ ] `appstract/laravel-opcache:^4.0`
- [ ] `spatie/laravel-response-cache:^7.0`
- [ ] `spatie/laravel-backup:^8.0`

### Export (Phase 4)
- [ ] `maatwebsite/laravel-excel:^3.1`

### Testing (Phase 1)
- [ ] `pestphp/pest:^3.0`
- [ ] `pestphp/pest-plugin-laravel:^3.0`
- [ ] `pestphp/pest-plugin-livewire:^3.0`
- [ ] `pestphp/pest-plugin-faker:^3.0`
- [ ] `nunomaduro/larastan:^2.0`

### Dev Tools (Phase 1)
- [ ] `barryvdh/laravel-debugbar:^3.0`
- [ ] `laravel/pint:^1.0`
- [ ] `laravel/telescope:^5.0`

---

*Last updated: 2024-05-04*
*Update this file as phases are completed.*
