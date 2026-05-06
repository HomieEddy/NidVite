# Additional Laravel Packages & Monitoring

This document catalogs additional packages and monitoring tools that enhance NidVite's observability, security, and developer experience.

---

## Monitoring & Observability

### 1. Laravel Telescope (Local Debug)

**Package:** `laravel/telescope` (dev-only)

**Purpose:** Local debugging dashboard for requests, exceptions, logs, database queries, mail, and queue jobs.

**Why:** Essential for optimizing PostGIS queries and debugging the email loop during development. Replaces Debugbar with a more Laravel-native experience.

```bash
composer require laravel/telescope --dev
php artisan telescope:install
```

**Config:** Enable only in local via `TELESCOPE_ENABLED=true` in `.env`. Never enable in production (use Sentry instead).

---

### 2. Sentry (Production Error Tracking)

**Package:** `sentry/sentry-laravel`

**Purpose:** Real-time error tracking, performance monitoring, and session replay for production.

**Why:** Catch exceptions in the PWA, map widget failures, and PostGIS query errors before users report them. Tracks release health.

```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=your-dsn
```

**Free Tier:** 5,000 errors/month, 1 billion performance units/month — sufficient for MVP.

---

### 3. Flare (Laravel Error Tracking Alternative)

**Package:** `spatie/laravel-ray` (dev) or Flare SaaS

**Purpose:** Beautiful error reporting tailored for Laravel. Shows stack traces with Laravel context (route, middleware, view).

**Why:** Better Laravel-specific context than generic log aggregators. Built by Spatie.

**Note:** Flare is a paid service (€9/month). Use Sentry for MVP, migrate to Flare if Laravel-specific context becomes critical.

---

### 4. Laravel Health (System Monitoring)

**Package:** `spatie/laravel-health`

**Purpose:** Endpoint (`/health`) that reports system status: database connectivity, queue size, disk space, SSL certificate validity.

**Why:** Railway can ping this endpoint for uptime monitoring. The entrepreneur dashboard can display a "system status" widget.

```bash
composer require spatie/laravel-health
php artisan health:install
```

---

### 5. Laravel Schedule Monitor

**Package:** `spatie/laravel-schedule-monitor`

**Purpose:** Tracks scheduled tasks (clustering job, cleanup jobs) and alerts if they fail or don't run on time.

**Why:** The pre-computed clustering queue job must run every 5 minutes. If it fails silently, the dashboard shows stale data.

```bash
composer require spatie/laravel-schedule-monitor
```

---

## Performance & Caching

### 6. Laravel OPcache Monitor

**Package:** `appstract/laravel-opcache`

**Purpose:** CLI commands to clear and check OPcache status.

**Why:** After each Railway deploy, OPcache may hold stale bytecode. Include `php artisan opcache:clear` in the predeploy script.

```bash
composer require appstract/laravel-opcache
```

---

### 7. Laravel Response Cache

**Package:** `spatie/laravel-response-cache`

**Purpose:** Cache entire HTTP responses (e.g., the PWA status page, map tile metadata).

**Why:** The citizen "Track Report" page is read-heavy and doesn't change often. Cache it for 60 seconds to reduce PostGIS queries.

```bash
composer require spatie/laravel-response-cache
```

**Caution:** Do not cache the report submission form (POST requests are ignored by default).

---

## Security & Hardening

### 8. Laravel Security (Headers & CSP)

**Package:** `bepsvpt/secure-headers`

**Purpose:** Enforces security headers (HSTS, CSP, X-Frame-Options) via middleware.

**Why:** The PWA handles geolocation and photos. CSP prevents XSS attacks from injected scripts.

```bash
composer require bepsvpt/secure-headers
```

---

### 9. Laravel Purgecss (Frontend Asset Optimization)

**Package:** Built into Laravel Mix / Vite

**Purpose:** Remove unused Tailwind CSS classes from production builds.

**Why:** PWA needs fast first load. PurgeCSS reduces CSS bundle from ~3MB to ~10KB.

**Config:** Already handled by Tailwind's `content` array in `tailwind.config.js`. Ensure all Blade/Livewire views are listed.

---

## Geolocation & Validation

### 10. Laravel GeoIP

**Package:** `torann/geoip`

**Purpose:** Determine approximate location from IP address (fallback if GPS is disabled).

**Why:** If a user's browser denies geolocation permission, we can suggest their approximate location based on IP for the geofencing check.

```bash
composer require torann/geoip
```

---

### 11. Laravel Phone Number Validation

**Package:** `propaganistas/laravel-phone`

**Purpose:** Validate and format international phone numbers.

**Why:** If we add SMS notifications later (Twilio), this ensures valid numbers. Not needed for MVP but useful for Phase 2.

```bash
composer require propaganistas/laravel-phone
```

---

## Queue & Background Jobs

### 12. Laravel Horizon (Queue Dashboard)

**Package:** `laravel/horizon`

**Purpose:** Real-time queue monitoring dashboard. Shows job throughput, failed jobs, and worker status.

**Why:** Essential once we switch from `database` queue driver to `redis`. Not needed for MVP but critical for scaling.

```bash
composer require laravel/horizon
```

**Note:** Requires Redis. Add to TECH_STACK.md Phase 2 dependencies.

---

## SEO & PWA Enhancements

### 13. Laravel Sitemap

**Package:** `spatie/laravel-sitemap`

**Purpose:** Generate XML sitemaps for static pages (privacy policy, terms, landing page).

**Why:** Improves SEO for organic discovery. The PWA landing page should be indexable.

```bash
composer require spatie/laravel-sitemap
```

---

## Recommended Installation Order

### Phase 1 (MVP — Now)
```bash
# Monitoring
composer require sentry/sentry-laravel
composer require spatie/laravel-health
composer require spatie/laravel-schedule-monitor

# Performance
composer require appstract/laravel-opcache
composer require spatie/laravel-response-cache

# Security
composer require bepsvpt/secure-headers

# Dev only
composer require laravel/telescope --dev
```

### Phase 2 (Scaling — Later)
```bash
composer require laravel/horizon
composer require propaganistas/laravel-phone
composer require spatie/laravel-sitemap
```

---

## Monitoring Stack Summary

| Tool | Purpose | Environment | Cost |
|------|---------|-------------|------|
| **Laravel Telescope** | Local debugging | Dev only | Free |
| **Sentry** | Production error tracking | Production | Free tier (5k errors) |
| **Spatie Health** | System status endpoint | All | Free |
| **Spatie Schedule Monitor** | Cron job monitoring | Production | Free |
| **Railway Metrics** | CPU, memory, disk | Production | Included |

---

*This document is a living record. Propose additions via PR with justification.*
