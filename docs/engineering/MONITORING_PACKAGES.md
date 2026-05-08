# Monitoring & Additional Packages

> Last updated: 2026-05-06 — Audited against actual composer.json and config state

---

## Status Summary

| Package | Installed | Configured | Active |
|---------|-----------|------------|--------|
| `sentry/sentry-laravel` | Yes (^4.25) | No | No |
| `spatie/laravel-health` | Yes (^1.39) | No | No |
| `spatie/laravel-schedule-monitor` | Yes (^4.3) | No | No |
| `spatie/laravel-backup` | Yes (^9.0) | No | No |
| `bepsvpt/secure-headers` | Yes (^9.1) | No | No |
| `maatwebsite/excel` | Yes (^3.1) | No | No |
| `laravel/telescope` | Yes (^5.0, dev) | Yes | Dev-only |
| `barryvdh/laravel-debugbar` | Yes (^3.0, dev) | Yes | Dev-only |

**Not installed despite being in docs:**
- `appstract/laravel-opcache` — NOT in composer.json
- `spatie/laravel-response-cache` — NOT in composer.json

---

## Installed & Configured (Dev Only)

### 1. Laravel Telescope

**Status:** Active, dev-only

**Config:** `config/telescope.php` published, `TELESCOPE_ENABLED=true` in `.env`

**Purpose:** Local debug dashboard for requests, queries, mail, logs.

**Warning:** Never enable in production. Use Sentry instead.

---

### 2. Laravel Debugbar

**Status:** Active, dev-only

**Config:** `config/debugbar.php` published

**Purpose:** Query profiling, memory usage, route debugging.

---

## Installed but NOT Configured

### 3. Sentry (Production Error Tracking)

**Status:** Package installed, NO config published

**To activate:**
```bash
php artisan sentry:publish --dsn=your-dsn
```

**Free tier:** 5,000 errors/month — sufficient for MVP.

**Missing:** `config/sentry.php`, DSN in `.env`, test exception in `app/Exceptions/Handler.php`

---

### 4. Spatie Laravel Health (System Monitoring)

**Status:** Package installed, NO config published, NO health checks registered

**To activate:**
```bash
php artisan health:install
```

Then register checks in a health check class:
```php
// e.g., DatabaseCheck, RedisCheck, DiskSpaceCheck, QueueCheck
```

**Missing:** `config/health.php`, check registrations, `/health` route

---

### 5. Spatie Schedule Monitor (Cron Monitoring)

**Status:** Package installed, NO config published, NO scheduled tasks

**To activate:** Requires scheduled tasks to exist first (currently `routes/console.php` only has `inspire`).

**Missing:** `config/schedule-monitor.php`, any actual scheduled commands to monitor

---

### 6. Spatie Laravel Backup (Database Backups)

**Status:** Package installed, NO config published, NO backup schedule

**To activate:**
```bash
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
```

Then configure `config/backup.php` with R2 destination and add to scheduler:
```php
$schedule->command('backup:clean')->daily()->at('01:00');
$schedule->command('backup:run')->daily()->at('01:30');
```

**Missing:** `config/backup.php`, R2 backup disk config, scheduled backup commands

---

### 7. Secure Headers (Security Headers)

**Status:** Package installed, NOT actively enforced

**To activate:** Add middleware to HTTP kernel or route middleware:
```php
\Bepsvpt\SecureHeaders\Http\Middleware\ApplySecureHeaders::class,
```

**Missing:** `config/secure-headers.php` (needs publishing), middleware registration

---

### 8. Laravel Excel (Export)

**Status:** Package installed, NO export classes created

**To use:** Create export classes extending `Maatwebsite\Excel\Concerns\Exportable`:
```php
// e.g., ReportsExport, ExpensesExport, JobsExport
```

**Missing:** Any export class, any Filament export action, R2 disk config for backup destination

---

## NOT Installed (Referenced in Docs)

### 9. Laravel OPcache

**Package:** `appstract/laravel-opcache`

**Status:** NOT in composer.json

**Purpose:** Clear stale OPcache bytecode after Railway deploys.

**Action needed:**
```bash
composer require appstract/laravel-opcache
```

Add to Railway predeploy: `php artisan opcache:clear`

---

### 10. Laravel Response Cache

**Package:** `spatie/laravel-response-cache`

**Status:** NOT in composer.json

**Purpose:** Cache HTTP responses (e.g., tracking page) to reduce PostGIS queries.

**Action needed:**
```bash
composer require spatie/laravel-response-cache
php artisan vendor:publish --provider="Spatie\ResponseCache\ResponseCacheServiceProvider"
```

---

## Monitoring Stack — Target State

| Tool | Purpose | Environment | Cost | Status |
|------|---------|-------------|------|--------|
| **Laravel Telescope** | Local debugging | Dev only | Free | Active |
| **Sentry** | Production errors | Production | Free tier | Installed, not configured |
| **Spatie Health** | System status | All | Free | Installed, not configured |
| **Spatie Schedule Monitor** | Cron monitoring | Production | Free | Installed, not configured |
| **Spatie Backup** | DB backups | Production | Free | Installed, not configured |
| **Railway Metrics** | CPU, memory, disk | Production | Included | Available on Railway |

---

## Recommended Activation Order

### Before Launch (Phase 6)
```bash
# 1. Sentry — critical for catching production errors
php artisan sentry:publish --dsn=your-dsn

# 2. Health checks — Railway needs a /health endpoint
php artisan health:install

# 3. Backup — data safety
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
# Then configure config/backup.php with R2 destination

# 4. Secure headers — security baseline
php artisan vendor:publish --provider="Bepsvpt\SecureHeaders\SecureHeadersServiceProvider"
# Then add middleware to kernel

# 5. Install missing packages
composer require appstract/laravel-opcache
composer require spatie/laravel-response-cache
```

### Post-Launch (Phase 7+)
```bash
# Schedule monitor — needs scheduled tasks first
# Response cache — after identifying slow pages
# Excel exports — when admin requests them
```

---

*Updated 2026-05-06 — Reflects actual installation and configuration state.*
