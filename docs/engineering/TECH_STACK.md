# Tech Stack & Dependencies

This document defines the complete technology stack for NidVite. All versions are locked for the MVP phase to prevent drift.

---

## Runtime & Framework

| Technology | Version | Justification |
|------------|---------|---------------|
| PHP | `>= 8.3` | Required for strict typing, readonly properties, and modern Laravel features. |
| Laravel | `^11.0` | Latest stable LTS-adjacent release. Filament 3 requires Laravel 10+. |
| Node.js | `^20.0` | Required for Vite asset building. LTS version. |

---

## Database

| Technology | Version | Justification |
|------------|---------|---------------|
| PostgreSQL | `>= 15` | Required for PostGIS extension. Widely supported by Laravel Sail. |
| PostGIS | `>= 3.4` | Industry-standard spatial database. Enables `ST_DWithin`, `ST_ClusterDBSCAN`, and true-meter distance calculations via `geography` type. |

---

## Admin Panel & Media (Required)

| Package | Version | Purpose |
|---------|---------|---------|
| `filament/filament` | `^3.0` | Entrepreneur dashboard with map widgets, tables, and forms. |
| `filament/spatie-laravel-media-library-plugin` | `^3.0` | Native Filament integration for "Before/After" photo uploads. |
| `spatie/laravel-medialibrary` | `^11.0` | Photo storage, conversions, and responsive images. Configured for `local` (dev) and `r2` (prod). |
| `intervention/image` | `^3.0` | EXIF stripping and thumbnail generation. |

---

## Real-time (Required)

| Package | Version | Purpose |
|---------|---------|---------|
| `laravel/reverb` | `^1.0` | WebSocket server for real-time dashboard updates (new reports, notifications). |

---

## Anti-Spam & Security (Required)

| Package | Version | Purpose |
|---------|---------|---------|
| `spatie/laravel-honeypot` | `^3.0` | Invisible honeypot field for basic bot protection (ADR-003). |
| `anhskohbo/no-captcha` | `^3.0` | reCAPTCHA v2 Invisible integration (ADR-003). |
| `matanyada/laravel-postgis` | `^5.0` | PostGIS-aware Eloquent schema builder and spatial query scopes. |
| `bepsvpt/secure-headers` | `^7.0` | Enforces security headers (CSP, HSTS, X-Frame-Options) via middleware. |
| `jenssegers/agent` | `^2.6` | Parse User-Agent strings for device fingerprinting analytics. |

---

## Auth & RBAC (Required)

| Package | Version | Purpose |
|---------|---------|---------|
| `laravel/fortify` | `^1.0` | Auth scaffolding with 2FA, password reset, session management (ADR-008). |

---

## Mail & Notifications (Required)

| Package | Version | Purpose |
|---------|---------|---------|
| `resend/resend-laravel` | `^0.1` | Official Resend mail driver for Laravel. Handles the Automated Email Loop. |

---

## PWA (Required)

| Package | Version | Purpose |
|---------|---------|---------|
| `silviolleite/laravelpwa` | `^2.0` | Service worker, `manifest.json`, and offline support for the citizen-facing PWA. |

---

## Maps (Required)

| Technology | Version | Purpose |
|------------|---------|---------|
| MapLibre GL JS | `^4.0` | Open-source map renderer. Used in both the PWA and the Filament dashboard. |
| MapTiler | Free Tier | Vector tile provider. API key required. Sufficient for MVP traffic. |

---

## Testing (Required)

| Package | Version | Purpose |
|---------|---------|---------|
| `pestphp/pest` | `^3.0` | Primary TDD framework. Expressive syntax over PHPUnit. |
| `pestphp/pest-plugin-laravel` | `^3.0` | Pest bindings for Laravel (factories, HTTP testing, etc.). |
| `pestphp/pest-plugin-livewire` | `^3.0` | Testing Livewire components in isolation. |
| `pestphp/pest-plugin-faker` | `^3.0` | Fake data generation for tests (coordinates, emails, etc.). |
| `nunomaduro/larastan` | `^2.0` | Laravel-aware PHPStan (Level 5+). |

---

## Audit & Logging (Required)

| Package | Version | Purpose |
|---------|---------|---------|
| `spatie/laravel-activitylog` | `^4.0` | Audit trail for report status changes (who changed what and when). |

---

## Monitoring & Observability (Required)

| Package | Version | Purpose |
|---------|---------|---------|
| `sentry/sentry-laravel` | `^4.0` | Production error tracking, performance monitoring, and session replay. Free tier: 5k errors/month. |
| `spatie/laravel-health` | `^1.0` | System status endpoint (`/health`) for Railway uptime checks. |
| `spatie/laravel-schedule-monitor` | `^1.0` | Alerts if scheduled tasks (clustering job) fail or don't run on time. |

---

## Performance & Caching (Required)

| Package | Version | Purpose |
|---------|---------|---------|
| `appstract/laravel-opcache` | `^4.0` | OPcache management. Clear stale bytecode after each Railway deploy. |
| `spatie/laravel-response-cache` | `^7.0` | Cache HTTP responses (e.g., "Track Report" page) to reduce PostGIS queries. |
| `spatie/laravel-backup` | `^8.0` | Automated database backups to R2. Critical for partition recovery. |

---

## Export (Required)

| Package | Version | Purpose |
|---------|---------|---------|
| `maatwebsite/laravel-excel` | `^3.1` | Export expense reports, job sheets, and analytics to Excel/PDF. |

---

## Development Tools (Dev-only)

| Package | Version | Purpose |
|---------|---------|---------|
| `barryvdh/laravel-debugbar` | `^3.0` | Local query profiling, memory usage, and route debugging. Essential for optimizing PostGIS queries. |
| `laravel/pint` | `^1.0` | Automated code formatting with the `laravel` preset. |
| `laravel/telescope` | `^5.0` | Local debugging dashboard for requests, exceptions, logs, and mail. **Never enable in production.** |

---

## Composer Installation Command

Run this after `composer create-project` to install all MVP dependencies:

```bash
# Core
composer require filament/filament:"^3.0" \
    filament/spatie-laravel-media-library-plugin:"^3.0" \
    spatie/laravel-medialibrary:"^11.0" \
    intervention/image:"^3.0" \
    matanyada/laravel-postgis:"^5.0"

# Real-time
composer require laravel/reverb:"^1.0"

# Anti-spam & Security
composer require spatie/laravel-honeypot:"^3.0" \
    anhskohbo/no-captcha:"^3.0" \
    bepsvpt/secure-headers:"^7.0" \
    jenssegers/agent:"^2.6"

# Auth & RBAC
composer require laravel/fortify:"^1.0"

# Mail
composer require resend/resend-laravel:"^0.1"

# PWA
composer require silviolleite/laravelpwa:"^2.0"

# Audit
composer require spatie/laravel-activitylog:"^4.0"

# Monitoring
composer require sentry/sentry-laravel:"^4.0" \
    spatie/laravel-health:"^1.0" \
    spatie/laravel-schedule-monitor:"^1.0"

# Performance
composer require appstract/laravel-opcache:"^4.0" \
    spatie/laravel-response-cache:"^7.0" \
    spatie/laravel-backup:"^8.0"

# Export
composer require maatwebsite/laravel-excel:"^3.1"

# Dev
composer require --dev pestphp/pest:"^3.0" \
    pestphp/pest-plugin-laravel:"^3.0" \
    pestphp/pest-plugin-livewire:"^3.0" \
    pestphp/pest-plugin-faker:"^3.0" \
    nunomaduro/larastan:"^2.0" \
    barryvdh/laravel-debugbar:"^3.0" \
    laravel/pint:"^1.0" \
    laravel/telescope:"^5.0"
```

---

## npm Dependencies

```bash
npm install maplibre-gl
```

MapLibre GL JS is loaded via Vite. The MapTiler API key is injected via a Blade `@env` directive or global `window` variable.

---

## Alternatives Considered (and Rejected)

| Category | Rejected Option | Reason |
|----------|-----------------|--------|
| Admin Panel | Custom Livewire CRUD | Filament provides map widgets, tables, and forms out-of-the-box. Building raw CRUD would take 2-3x longer. |
| Maps | Mapbox GL JS | MapLibre is open-source and avoids vendor lock-in. Mapbox requires a paid plan at higher volumes. |
| Maps | Leaflet | Raster-based. MapLibre provides smoother vector rendering and better clustering performance. |
| Storage | AWS S3 | Cloudflare R2 has zero egress fees. For a bootstrapped MVP, this is a significant cost advantage. |
| Queue | Redis | Database queue driver is sufficient for MVP traffic. Redis can be adopted later without code changes. |
| Anti-Spam | reCAPTCHA v3 | Score-based system is harder to test and reason about during MVP. v2 Invisible is more predictable. |
| PWA | Hand-rolled SW | `silviolleite/laravelpwa` handles manifest generation and service worker scaffolding, saving ~1 hour of setup. |
| Real-time | Pusher | Reverb is Laravel-native, free, and integrates seamlessly with Laravel Echo. |
| Auth | Laravel Breeze | Fortify provides headless auth (API + web) with 2FA built-in. Better for Filament integration. |
| Export | Custom CSV | `laravel-excel` handles formatting, styling, and multiple sheet exports with minimal code. |

---

*This document is a living record. Propose changes via PR with a tech-debt justification.*
