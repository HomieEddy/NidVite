# NidVite MVP Roadmap

> Last audited: 2026-05-06

---

## COMPLETED

### Phase 0: Foundation & Architecture
- [x] Coding manifesto and conventions
- [x] Git workflow with `develop` branch
- [x] CI/CD pipeline (GitHub Actions: Pint + PHPStan + Pest)
- [x] Security & privacy guidelines
- [x] Deployment strategy (Railway)
- [x] Tech stack documentation
- [x] 10 ADRs complete
- [x] Production database schema design
- [x] Zero-auth abuse prevention architecture
- [x] Caching & CDN strategy
- [x] Monitoring package selection
- [x] RBAC & admin security model
- [x] Job-based expense tracking with inventory
- [x] Bilingual support architecture (FR/EN)

### Phase 1: Laravel Scaffolding
- [x] Laravel 11 project with Sail
- [x] PostGIS 15-3.4 in docker-compose
- [x] Redis container for Reverb scaling
- [x] Mailpit for local email testing
- [x] Core packages installed (see Package Tracker below)
- [x] `.env.example` with all required keys
- [x] GitHub Actions CI (Pint, PHPStan L5, Pest on PostGIS)
- [x] `develop` branch

### Phase 2: Database & Models
- [x] Migrations: `users`, `roles`, `reports`, `report_categories`, `repair_jobs`, `job_reports`, `job_workers`, `expenses`, `materials`, `material_purchases`, `job_materials`, `media`, `activity_log`, `telescope_entries`, `montreal_boundary`, `passkeys`, `vendors`
- [x] Eloquent models with relationships (13 models: User, Role, Report, ReportCategory, RepairJob, Expense, Vendor, Material, MaterialPurchase, JobReport, JobWorker, JobMaterial, MontrealBoundary)
- [x] PostGIS geography columns on `reports` + `montreal_boundary`
- [x] GIST spatial indexes
- [x] Spatie media collections (`report-photos` with EXIF stripping)
- [x] Spatie ActivityLog on Report model
- [x] Soft deletes on Report
- [x] Seeders: RoleSeeder, ReportCategorySeeder, MontrealBoundarySeeder, AdminUserSeeder, TestDataSeeder

### Phase 3: Citizen PWA
- [x] Anonymous Livewire `report-form` component (photo upload inline)
- [x] One-tap geolocation capture
- [x] Photo upload with EXIF stripping (ExifStripper service)
- [x] Geofencing validation (MontrealBoundary::contains)
- [x] Anti-spam honeypot (spatie/laravel-honeypot)
- [x] PWA manifest, service worker, icons, splash screens (silviolleite/laravelpwa)
- [x] Unique tracking URL (`/suivi/{uuid}`)
- [x] Report state machine (ReportStatus enum: Received→Verified→Scheduled→InProgress→Repaired/Rejected)
- [x] Public map page (`/carte`) with Leaflet + GeoJSON
- [x] Welcome page with stats and tracking modal
- [x] FR/EN language switch (session-based, `/locale/{locale}`)
- [x] Bilingual lang files: report, map, tracking, email, dashboard (FR + EN)
- [x] French-first default, localized dates
- [x] Citizen layout with mobile bottom nav

### Phase 4: Entrepreneur Dashboard
- [x] Filament v5 AdminPanelProvider with 2FA
- [x] ReportResource (status/priority badges, filters, groups, location modal)
- [x] UserResource (basic CRUD)
- [x] RepairJobResource (basic CRUD)
- [x] ExpenseResource (with vendor selector)
- [x] VendorResource (basic CRUD)
- [x] 4 KPI widgets: Open Reports, Repairs This Week, Money Spent, Avg Repair Time
- [x] ReportsChart (30-day line chart)
- [x] ReportsByNeighborhood (top 10 bar chart)
- [x] ReportsMap widget (iframe of public map)
- [x] RBAC policies: ReportPolicy, UserPolicy, RepairJobPolicy, ExpensePolicy
- [x] Activity log integration on Report
- [x] Real-time notifications via Reverb (ReportCreated broadcasts on private-admin.reports)

### Phase 4a: Auth & Security
- [x] Fortify integration (login, 2FA, password reset)
- [x] 2FA: TOTP with QR codes + recovery codes (pragmarx/google2fa-laravel)
- [x] Passkeys/WebAuthn support (migration + Fortify config)
- [x] Role-based access (5 roles: Admin, Manager, ServiceWorker, Accountant, Viewer)
- [x] Audit logging via Spatie ActivityLog (status, priority, admin_notes, rejection_reason changes)
- [x] Secure headers package installed (bepsvpt/secure-headers ^9.1)
- [x] reCAPTCHA v2 package installed (no-captcha)
- [x] User agent parsing (jenssegers/agent)

### Phase 4b: Job & Expense Management
- [x] Repair jobs CRUD (Filament resource)
- [x] Expense entry (Filament resource with vendor FK)
- [x] Vendor management (Filament resource)
- [x] Expense category → replaced by Vendor system (categories table dropped, vendors table added)

### Phase 4c: Inventory System
- [x] Materials model + migration (with current_stock, reserved_stock, min_stock_alert)
- [x] Purchase logging (MaterialPurchase model + migration)
- [x] Job-Material pivot (quantity_planned, quantity_actual, unit_cost_at_time)
- [x] Job-Worker pivot (role_in_job, hours_worked)
- [x] Tax fields on Expense (GST + QST at 14.975%)

### Phase 5: Email & Notifications
- [x] Resend integration (mailer configured)
- [x] Automated email on all status changes (ReportStatusUpdated mailable, queued)
- [x] Email on report rejection (with reason)
- [x] Markdown email template with tracking link CTA button
- [x] Bilingual emails (preferred_locale on reports, FR/EN templates)
- [x] Queue worker configuration (database queue, ShouldQueue interface)

### Phase 7: Testing & CI
- [x] Pest test suite (~65 tests across 11 feature test files)
- [x] State machine transition tests
- [x] RBAC permission tests
- [x] Geofencing tests
- [x] Email notification tests
- [x] Reverb broadcast tests
- [x] Report form + tracking tests
- [x] PHPStan Level 5 compliance (Larastan)
- [x] Laravel Pint formatting (enforced in CI)
- [x] CodeRabbit configuration
- [x] Railway deployment config (`railway.toml`)

---

## REMAINING

### Phase 2 Gaps: Database & Models
- [ ] Missing migrations: `permissions`, `role_permissions`, `admin_sessions`, `admin_audit_log`, `clusters`, `device_fingerprints`, `rate_limit_buckets`, `blocked_ips`, `suspicious_activity`, `email_deliveries`, `hourly_stats`, `daily_stats`, `weekly_stats`, `neighborhood_stats`, `notifications`
- [ ] Missing models: Permission, Cluster, AdminSession, DeviceFingerprint, etc.
- [ ] Table partitioning (monthly ranges on `reports`)
- [ ] Data retention jobs (IP purging, archiving)
- [ ] Automated backups to R2 (spatie/laravel-backup installed but NOT configured)

### Phase 3 Gaps: Citizen PWA
- [ ] Rate limiting (IP + device fingerprint)
- [ ] Language switcher as dedicated Livewire component (currently just route-based)
- [ ] Localized numbers (FR: "1 234,56", EN: "1,234.56")
- [ ] Photo deduplication (perceptual hash)
- [ ] Device fingerprinting middleware

### Phase 4 Gaps: Entrepreneur Dashboard
- [ ] MapLibre interactive map widget inside Filament (current ReportsMap is just an iframe)
- [ ] Priority assignment UI
- [ ] "After" photo upload (report-photos collection only; no after_photos collection)
- [ ] Pre-computed clustering display
- [ ] Admin audit log viewer (Filament page)
- [ ] MaterialResource (Filament resource for inventory management)
- [ ] ReportCategoryResource (Filament resource for managing categories)
- [ ] All Filament resources translated with __() keys
- [ ] English toggle in user profile
- [ ] Missing policies: VendorPolicy, MaterialPolicy, MaterialPurchasePolicy, ReportCategoryPolicy

### Phase 4a Gaps: Auth & Security
- [ ] Admin session management (timeout, concurrent sessions)
- [ ] Brute force protection (5 attempts / 15 min lockout)
- [ ] Viewer page-view logging
- [ ] reCAPTCHA enforcement (installed but not enforced in validation)

### Phase 4b Gaps: Job & Expense Management
- [ ] Multi-worker assignment UI
- [ ] Self-assignment for Service Workers
- [ ] Receipt photo upload via Spatie
- [ ] Cost allocation (equal split + manual override)
- [ ] Tax calculation automation (GST + QST)

### Phase 4c Gaps: Inventory System
- [ ] Stock level tracking UI
- [ ] Low stock alerts (dashboard + email + filter)
- [ ] Purchase receipt upload
- [ ] Automatic stock decrement on job completion
- [ ] Reserved stock for in-progress jobs

### Phase 4d Gaps: Dashboard & Analytics
- [ ] Repair velocity trend chart
- [ ] Cost per neighborhood analysis
- [ ] Excel/PDF export (maatwebsite/excel installed but no export classes)

### Phase 5 Gaps: Email & Notifications
- [ ] Bounced email handling (3 retries → permanent fail)
- [ ] Notification system (dashboard badges + emails)
- [ ] Critical report alerts to Manager/Admin
- [ ] Email delivery tracking (no `email_deliveries` table)

### Phase 6: Monitoring & Hardening (NOT STARTED)
- [ ] Sentry error tracking (installed but NOT configured: no config/sentry.php)
- [ ] Health check endpoint (spatie/laravel-health installed but NOT configured)
- [ ] Schedule monitor (spatie/laravel-schedule-monitor installed but NOT configured)
- [ ] Response caching (NOT installed: spatie/laravel-response-cache missing from composer.json)
- [ ] Security headers enforcement (bepsvpt/secure-headers installed but not actively configured)
- [ ] OPcache clear on deploy (NOT installed: appstract/laravel-opcache missing from composer.json)
- [ ] Automated database backups to R2 (spatie/laravel-backup installed but NOT configured)
- [ ] Suspicious activity dashboard (Filament)

### Phase 7 Gaps: Testing & Polish
- [ ] Tests for Vendor, Material, Category resources
- [ ] Tests for Filament admin panel access
- [ ] Tests for PWA / map functionality
- [ ] Tests for 2FA / passkey auth
- [ ] Railway auto-deploy verification
- [ ] Load testing (optional)

### Phase 8: Launch (NOT STARTED)
- [ ] Production environment variables
- [ ] Custom domain + SSL
- [ ] Privacy policy page
- [ ] Terms of service page
- [ ] Quebec Law 25 compliance check
- [ ] Soft launch (friends & family)
- [ ] Public launch

---

## Post-MVP Nice-to-Haves

### Operational Efficiency
- [ ] Bulk operations (assign 10 reports at once)
- [ ] Export reports to CSV/PDF
- [ ] Print-friendly job sheet
- [ ] Mobile-optimized admin view
- [ ] Customer satisfaction survey

### Notifications
- [ ] SMS notifications (Twilio)
- [ ] SLA breach alerts
- [ ] Push notifications for PWA

### Advanced Analytics
- [ ] Event sourcing
- [ ] Predictive maintenance (AI/ML)
- [ ] Multi-year trend analysis

---

## Package Installation Tracker

### Installed & Configured
| Package | Version | Status |
|---------|---------|--------|
| `filament/filament` | ^5.6 | Active |
| `spatie/laravel-medialibrary` | ^11.22 | Active |
| `intervention/image` | ^3.11 | Active |
| `laravel/reverb` | ^1.10 | Active |
| `laravel/fortify` | ^1.37 | Active |
| `spatie/laravel-honeypot` | ^4.7 | Active |
| `anhskohbo/no-captcha` | ^3.8 | Active |
| `bepsvpt/secure-headers` | ^9.1 | Active |
| `jenssegers/agent` | ^2.6 | Active |
| `resend/resend-laravel` | ^1.3 | Active |
| `silviolleite/laravelpwa` | ^2.0 | Active |
| `spatie/laravel-activitylog` | ^4.12 | Active |
| `pragmarx/google2fa-laravel` | ^3.0 | Active |
| `bacon/bacon-qr-code` | ^3.1 | Active |
| `maatwebsite/excel` | ^3.1 | Active |

### Installed but NOT Configured
| Package | Version | Missing |
|---------|---------|---------|
| `sentry/sentry-laravel` | ^4.25 | No config/sentry.php published |
| `spatie/laravel-health` | ^1.39 | No config/health.php, no checks registered |
| `spatie/laravel-schedule-monitor` | ^4.3 | No config, no scheduled tasks to monitor |
| `spatie/laravel-backup` | ^9.0 | No config/backup.php, no backup schedule |

### NOT Installed (listed in docs but missing from composer.json)
| Package | Notes |
|---------|-------|
| `spatie/laravel-response-cache` | Docs say ^7.0 but not in composer.json |
| `appstract/laravel-opcache` | Docs say ^4.0 but not in composer.json |
| `laravel/horizon` | Deferred to scaling phase |
| `matanyada/laravel-postgis` | Docs reference but not in composer.json |
| `filament/spatie-laravel-media-library-plugin` | Docs reference but not in composer.json |
| `pestphp/pest-plugin-livewire` | Docs say ^3.0 but not in composer.json (^2.0 installed) |

### Dev Tools (Active)
| Package | Version |
|---------|---------|
| `pestphp/pest` | ^2.0 |
| `pestphp/pest-plugin-laravel` | ^2.0 |
| `pestphp/pest-plugin-faker` | ^2.0 |
| `nunomaduro/larastan` | ^2.0 |
| `laravel/pint` | ^1.13 |
| `laravel/telescope` | ^5.0 |
| `barryvdh/laravel-debugbar` | ^3.0 |
| `laravel/sail` | ^1.26 |

---

## Quick Stats

| Metric | Value |
|--------|-------|
| Migrations | 27 files, 26 actual tables |
| Models | 13 |
| Filament Resources | 5 |
| Policies | 4 |
| Livewire Components | 1 (anonymous inline) |
| Controllers | 3 |
| Mail classes | 1 (ReportStatusUpdated) |
| Event classes | 1 (ReportCreated) |
| Jobs | 0 |
| Services | 1 (ExifStripper) |
| Test files | 11 feature + 1 unit |
| Approx. test count | ~65 |
| Lang files | 5 per locale (FR + EN) |
| Routes | 7 web + 1 channel |
| ADRs | 10 |

---

*Updated: 2026-05-06 — Full codebase audit against docs*
