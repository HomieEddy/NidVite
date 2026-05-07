# Technology Stack

> Mapped: 2026-05-06 | Focus: tech

## Runtime & Language

**Primary:**
- PHP 8.3 — Backend application, Docker container (`docker/8.3/Dockerfile`)
- Required extensions: `pdo_pgsql`, `pgsql`, `mbstring`, `intl`, `exif`, `bcmath`, `gd`, `zip`, `imagick`, `redis`, `xdebug` (dev-only)

**Secondary:**
- JavaScript (ES modules) — Frontend build via Vite, WebSocket client
- Node.js 24 — Build tooling (Docker image); Node 20 in CI (`ci.yml` line 155)
- SQL (PostgreSQL dialect) — PostGIS spatial queries

## Backend Framework

**Core:**
- Laravel 11 (`^11.0`) — Full-stack MVC framework
- Key features: Eloquent ORM, Blade templating, queue system, broadcasting, migrations
- Application timezone: `America/Toronto` (Montreal)
- Locale: `fr` (French), fallback `fr_CA`
- Health endpoint: `/up` (registered in `bootstrap/app.php`)

**Authentication:**
- Laravel Fortify `^1.37` — Session-based auth with:
  - Registration, password reset, profile update
  - Two-factor authentication (2FA) with QR codes (`bacon/bacon-qr-code ^3.1`, `pragmarx/google2fa-laravel ^3.0`)
  - Passkeys/WebAuthn support (config in `config/fortify.php` lines 147-178)

**Admin Panel:**
- Filament v5 (`^5.6`) — Admin dashboard at `/admin`
- Config: `app/Providers/Filament/AdminPanelProvider.php`
- Multi-factor auth via `AppAuthentication::make()->recoverable()`
- Amber color scheme matching citizen PWA
- Reverb scripts injected via renderHook at `panels::body.start`

**Real-time:**
- Laravel Reverb `^1.10` — First-party WebSocket server
- Laravel Echo + Pusher-js client — Browser-side WebSocket client
- Channel: `private-admin.reports` for report notifications

## Frontend Stack

**Build Tool:**
- Vite 5 (`^5.0`) — Frontend build with HMR
- `laravel-vite-plugin ^1.0` — Laravel integration
- Config: `vite.config.js`
- Entry points: `resources/css/app.css`, `resources/js/app.js`, `resources/js/echo.js`, `resources/js/reverb-listener.js`

**CSS:**
- Tailwind CSS v4 (`^4.2.4`) — Utility-first CSS
- `@tailwindcss/postcss ^4.2.4` — PostCSS plugin for Tailwind v4
- Custom amber theme (`resources/css/app.css` lines 3-16)
- Custom components: `.citizen-container`, `.btn-touch`, `.citizen-card`, `.citizen-header`
- Custom utilities: `.no-overscroll`, `.scrollbar-hide`, `.animate-fade-in`, `.animate-slide-up`
- Font: Inter (system-ui fallback)

**JavaScript:**
- `laravel-echo ^2.3.4` — WebSocket broadcasting client
- `pusher-js ^8.5.0` — Reverb-compatible transport layer
- `axios ^1.6.4` — HTTP client (dev dependency)
- No SPA framework — server-rendered Blade + Livewire 3

**Maps:**
- Leaflet 1.9.4 — Loaded via CDN (unpkg), NOT npm
- OpenStreetMap tiles — Free tile layer
- Nominatim API — Geocoding/reverse geocoding (no key required)
- Used in: `resources/views/map.blade.php`, `resources/views/tracking.blade.php`, `resources/views/report.blade.php`, `resources/views/components/⚡report-form.blade.php`

## Database & Storage

**Primary Database:**
- PostgreSQL 15 with PostGIS 3.4 extension
- Docker image: `postgis/postgis:15-3.4`
- Connection: `pgsql` (env `DB_CONNECTION=pgsql`)
- PostGIS usage: `geography` type for report locations, `ST_MakePoint`, `ST_SetSRID`, `ST_DWithin`, `ST_Contains`
- Montreal geofence: `App\Models\MontrealBoundary` with `ST_Contains` check
- Testing DB auto-created: `docker/pgsql/create-testing-database.sql`

**Cache:**
- Driver: `database` (env `CACHE_STORE=database`)
- Table: `cache`
- Redis available but not default (`redis:alpine` in docker-compose)

**Queue:**
- Driver: `database` (env `QUEUE_CONNECTION=database`)
- Tables: `jobs`, `job_batches`, `failed_jobs`
- Media conversions queued by default (`config/media-library.php` line 58)

**Session:**
- Driver: `database` (env `SESSION_DRIVER=database`)
- Table: `sessions`
- Lifetime: 120 minutes

**File Storage:**
- Default disk: `local` (env `FILESYSTEM_DISK=local`)
- `public` disk: `storage/app/public` with symlink
- `s3` disk: Configured but no env values set (AWS_* vars empty in `.env.example`)
- Media library disk: `public` (env `MEDIA_DISK=public`)
- Max file size: 10MB (`config/media-library.php` line 41)

**Redis:**
- Image: `redis:alpine` in docker-compose
- Client: `phpredis` extension
- Used for: Reverb scaling (disabled by default), cache/store available

## Dev Tools

**Testing:**
- Pest 2 (`^2.0`) — Test framework (PHPUnit-compatible)
- `pest-plugin-faker ^2.0`, `pest-plugin-laravel ^2.0`
- PHPUnit 10.5 (`^10.5`) — Underlying runner
- Config: `phpunit.xml` — Uses `pgsql` for testing DB, PostGIS test DB
- Suite structure: `tests/Unit/`, `tests/Feature/`

**Linting / Static Analysis:**
- Laravel Pint `^1.13` — PHP code style fixer
- PHPStan via Larastan `^2.0` — Static analysis, level 5
- Config: `phpstan.neon` — Paths: `app/`, `tests/`
- CI runs: `pint --test` and `phpstan analyse`

**Debugging:**
- Laravel Debugbar `^3.0` — Dev-only, disabled by default (env `DEBUGBAR_ENABLED=false`)
- Config: `config/debugbar.php` — Excludes `telescope*`, `horizon*`, `_boost/*`
- Laravel Telescope `^5.0` — Dev debug dashboard, disabled in testing
- Config: `config/telescope.php` — Ignores `livewire*`, `pulse*`, `_boost*`
- Xdebug — Available in Docker, disabled by default (`SAIL_XDEBUG_MODE=off`)

**CI/CD:**
- GitHub Actions — `/.github/workflows/ci.yml`
- Two parallel jobs: `quality` (Pint + PHPStan) and `tests` (Pest)
- Both use `postgis/postgis:15-3.4` service container
- PHP 8.2 in CI (note: Docker uses 8.3)
- Node 20 for `npm ci` + `npm run build` in test job
- Triggers: push/PR to `main` and `develop`

**Other Dev:**
- Laravel Tinker `^2.9` — REPL
- Laravel Sail `^1.26` — Docker dev environment
- Faker `^1.23` — Test data generation (locale `fr_CA`)
- Mockery `^1.6` — Test mocking
- Spatie Ignition `^2.4` — Error pages

## Infrastructure

**Docker (Development):**
- Base: Ubuntu 24.04 (`docker/8.3/Dockerfile`)
- PHP 8.3 CLI with all required extensions
- Node.js 24 + npm (global)
- Supervisor for process management (`docker/8.3/supervisord.conf`)
- OPcache aggressively tuned for Windows Docker I/O performance
- Upload limits: 100MB (`post_max_size`, `upload_max_filesize`)
- Services: `laravel.test`, `pgsql`, `redis`, `mailpit`
- Ports: App 80→8091, Vite 5173→5174, Reverb 8080→8089

**Deployment (Production):**
- Railway — `railway.toml` config
- Builder: Nixpacks
- Start command: `php artisan serve --host=0.0.0.0 --port=$PORT`
- Health check: `/up` (30s timeout)
- Restart: on-failure, max 3 retries

**Mail (Development):**
- Mailpit — SMTP sink + web UI
- Ports: 1025→1026 (SMTP), 8025→8026 (dashboard)

## Package Inventory

### Active & Configured
| Package | Version | Purpose | Config Status |
|---------|---------|---------|--------------|
| `laravel/framework` | `^11.0` | Core framework | Full config in `config/` |
| `filament/filament` | `^5.6` | Admin panel | `AdminPanelProvider.php` |
| `laravel/fortify` | `^1.37` | Authentication | `config/fortify.php` (2FA + passkeys enabled) |
| `laravel/reverb` | `^1.10` | WebSocket server | `config/reverb.php`, `config/broadcasting.php` |
| `resend/resend-laravel` | `^1.3` | Production email | `config/mail.php` (resend transport) |
| `spatie/laravel-medialibrary` | `^11.22` | File/image uploads | `config/media-library.php` (full custom config) |
| `spatie/laravel-activitylog` | `^4.12` | Audit logging | `config/activitylog.php` (365-day retention) |
| `spatie/laravel-honeypot` | `^4.7` | Bot/spam protection | `config/honeypot.php` (randomized fields) |
| `spatie/laravel-backup` | `^9.0` | DB/file backups | No config file published |
| `spatie/laravel-health` | `^1.39` | Health monitoring | No config file published |
| `spatie/laravel-schedule-monitor` | `^4.3` | Schedule monitoring | No config file published |
| `anhskohbo/no-captcha` | `^3.8` | Google reCAPTCHA | `config/captcha.php` |
| `bepsvpt/secure-headers` | `^9.1` | Security headers | Auto-registered, no config published |
| `intervention/image` | `^3.11` | Image processing / EXIF stripping | Used in `App\Services\ExifStripper` |
| `jenssegers/agent` | `^2.6` | Device/browser detection | No config needed |
| `bacon/bacon-qr-code` | `^3.1` | QR code for 2FA | Used by Fortify 2FA |
| `pragmarx/google2fa-laravel` | `^3.0` | 2FA TOTP | Used by Fortify 2FA |
| `maatwebsite/excel` | `^3.1` | Excel export | No config file published, no exports implemented |
| `sentry/sentry-laravel` | `^4.25` | Error tracking | Auto-registered, no `config/sentry.php` published |
| `silviolleite/laravelpwa` | `^2.0` | PWA manifest + SW | `config/laravelpwa.php` (full config) |

### Installed but NOT Configured (missing published config)
| Package | Version | Missing Config |
|---------|---------|---------------|
| `spatie/laravel-backup` | `^9.0` | No `config/backup.php` — cannot run backups |
| `spatie/laravel-health` | `^1.39` | No `config/health.php` — health checks undefined |
| `spatie/laravel-schedule-monitor` | `^4.3` | No `config/schedule-monitor.php` — schedule monitoring unset |
| `sentry/sentry-laravel` | `^4.25` | No `config/sentry.php` — DSN not configured, errors not reported |
| `bepsvpt/secure-headers` | `^9.1` | No `config/secure-headers.php` — using default headers only |
| `maatwebsite/excel` | `^3.1` | No `config/excel.php` — no export classes created |

### Referenced but NOT Installed (CDN-only or implicit)
| Package | Expected Version | Notes |
|---------|-----------------|-------|
| Leaflet | 1.9.4 | CDN-loaded via unpkg.com, not in `package.json` |
| OpenStreetMap tiles | N/A | Free tile layer, no package |
| Nominatim (OSM) | N/A | Geocoding API, no SDK |

## Version Constraints Summary

| Constraint | Why |
|-----------|-----|
| PHP `^8.2` | Minimum for Laravel 11; Docker uses 8.3, CI uses 8.2 |
| Laravel `^11.0` | Latest LTS with Fortify passkeys support |
| Filament `^5.6` | v5 for Laravel 11 compatibility |
| PostgreSQL 15 + PostGIS 3.4 | Spatial queries for Montreal geofence |
| Tailwind `^4.2.4` | v4 with new PostCSS-based engine |
| Vite `^5.0` | Latest Vite for Laravel integration |
| Pest `^2.0` | Modern test framework over PHPUnit |
| Node 20/24 | 20 in CI, 24 in Docker — minor mismatch |

---

*Stack analysis: 2026-05-06*
