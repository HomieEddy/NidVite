# External Integrations

> Mapped: 2026-05-06 | Focus: tech

## Email & Communication

**Resend (Production):**
- Package: `resend/resend-laravel ^1.3`
- Transport: `resend` mailer in `config/mail.php` line 78
- Env var: `RESEND_API_KEY` (empty in `.env.example`)
- From address: `noreply@nidvite.ca`
- Status: **Configured but not active** — `MAIL_MAILER=log` by default; Resend only when `MAIL_MAILER=resend` and API key provided

**Mailpit (Development):**
- Docker service: `axllent/mailpit:latest`
- SMTP: port 1025→1026
- Dashboard: port 8025→8026
- Status: **Active in local development only**

## Real-time

**Laravel Reverb (WebSocket Server):**
- Package: `laravel/reverb ^1.10`
- Config: `config/reverb.php`
- Server port: 8080 (internal), 8089 (host)
- Protocol: WebSocket (`ws`/`wss`)
- Scaling: Redis-based scaling available but disabled (`REVERB_SCALING_ENABLED=false`)
- Rate limiting: Disabled by default
- Max connections: Unset (`REVERB_APP_MAX_CONNECTIONS` empty)
- Allowed origins: `*` (all — needs restriction for production)

**Laravel Echo (Browser Client):**
- Package: `laravel-echo ^2.3.4` + `pusher-js ^8.5.0`
- Config: `resources/js/echo.js`
- Connection: Uses `VITE_REVERB_*` env vars
- Vite env injection: `VITE_REVERB_APP_KEY`, `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, `VITE_REVERB_SCHEME`

**Active Channel:**
- `private-admin.reports` — Listens for `.report.created` events
- Handler: `resources/js/reverb-listener.js`
- Actions: Browser notification + Filament notification on new pothole report
- Injected into Filament admin via renderHook (`AdminPanelProvider.php` line 68-70)

## Maps & Geospatial

**PostGIS (Database Extension):**
- Version: PostGIS 3.4 on PostgreSQL 15
- Docker image: `postgis/postgis:15-3.4`
- Extension created via CI step and migrations
- Spatial operations used:
  - `ST_SetSRID(ST_MakePoint(lng, lat), 4326)::geography` — Store report coordinates
  - `ST_DWithin(location::geography, point, radius)` — Proximity searches (`App\Models\Report`)
  - `ST_Contains(boundary, point)` — Montreal geofence validation (`App\Models\MontrealBoundary`)
  - `ST_Y(location::geometry)`, `ST_X(location::geometry)` — Extract lat/lng from geography
- SRID: 4326 (WGS84)

**Leaflet (Map Rendering):**
- Version: 1.9.4 via CDN (unpkg.com, SRI-hashed)
- Not in `package.json` — loaded directly in Blade templates
- Used in: `resources/views/map.blade.php`, `resources/views/tracking.blade.php`, `resources/views/report.blade.php`, `resources/views/components/⚡report-form.blade.php`

**OpenStreetMap (Tiles):**
- Tile URL: `https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png`
- Free, no API key required
- Attribution included

**Nominatim (Geocoding):**
- Reverse geocoding: `https://nominatim.openstreetmap.org/reverse`
- Forward geocoding: `https://nominatim.openstreetmap.org/search`
- Usage: Address lookup from coordinates, address search
- Constrained: `city=Montreal&country=Canada`
- Language-aware: `accept-language` parameter
- No API key required (rate-limited: 1 req/s policy)

**OpenStreetMap Embed (Filament):**
- Used in `resources/views/filament/modals/report-location.blade.php`
- iframe embed for report location preview in admin

## Authentication & Identity

**Laravel Fortify:**
- Package: `laravel/fortify ^1.37`
- Config: `config/fortify.php`
- Guard: `web` (session-based)
- Features enabled:
  - Registration
  - Password reset
  - Profile update
  - Password update
  - Two-factor authentication (with QR code confirmation)
  - Passkeys/WebAuthn (with password confirmation)
- Rate limiting: Custom limiters for `login`, `two-factor`, `passkeys`

**2FA Implementation:**
- QR code generation: `bacon/bacon-qr-code ^3.1`
- TOTP: `pragmarx/google2fa-laravel ^3.0`
- Filament MFA: `AppAuthentication::make()->recoverable()->brandName('NidVite')`

**Passkeys:**
- Relying party: Derived from `APP_URL` host
- Allowed origins: `[APP_URL]`
- Timeout: 60 seconds

## Storage & CDN

**Local Filesystem:**
- Default disk: `local` (`storage/app`)
- Public disk: `public` (`storage/app/public`) with symlink `public/storage`
- Config: `config/filesystems.php`

**S3 (Not Active):**
- Config present in `config/filesystems.php`
- Env vars `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET` all empty
- Status: **Not configured** — no S3 credentials in `.env.example`

**Media Library (Spatie):**
- Package: `spatie/laravel-medialibrary ^11.22`
- Config: `config/media-library.php`
- Default disk: `public` (env `MEDIA_DISK=public`)
- Collection: `report-photos` (defined in `App\Models\Report`)
- Max file size: 10MB
- Image driver: `gd` (env `IMAGE_DRIVER=gd`), `imagick` available as fallback
- Conversions: Queued by default
- Responsive images: Enabled with `FileSizeOptimizedWidthCalculator`
- Image optimization: Full optimizer stack (Jpegoptim, Pngquant, Optipng, Svgo, Gifsicle, Cwebp, Avifenc)
- Pro features: Temporary uploads enabled, session affinity on

**No CDN configured** — All assets served directly from application server

## Monitoring & Error Tracking

**Sentry:**
- Package: `sentry/sentry-laravel ^4.25`
- Status: **Installed but NOT configured** — no `config/sentry.php` published
- No `SENTRY_LARAVEL_DSN` env var in `.env.example`
- Service provider auto-discovered but ineffective without DSN
- Impact: Production errors not tracked

**Laravel Health (Spatie):**
- Package: `spatie/laravel-health ^1.39`
- Status: **Installed but NOT configured** — no `config/health.php` published
- No health checks defined
- Railway health check uses `/up` (Laravel built-in), not Spatie Health

**Schedule Monitor (Spatie):**
- Package: `spatie/laravel-schedule-monitor ^4.3`
- Status: **Installed but NOT configured** — no `config/schedule-monitor.php` published

**Telescope (Dev-only):**
- Package: `laravel/telescope ^5.0` (require-dev)
- Config: `config/telescope.php`
- Storage: `database` driver
- Disabled in testing (`TELESCOPE_ENABLED=false`)
- Watches: All default watchers enabled
- Ignores: `livewire*`, `nova-api*`, `pulse*`, `_boost*`
- Auth: `Authorize` middleware (gates access)

**Debugbar (Dev-only):**
- Package: `barryvdh/laravel-debugbar ^3.0` (require-dev)
- Config: `config/debugbar.php`
- Disabled by default: `DEBUGBAR_ENABLED=false`
- Excludes: `telescope*`, `horizon*`, `_boost/browser-logs`

**Activity Log (Spatie):**
- Package: `spatie/laravel-activitylog ^4.12`
- Config: `config/activitylog.php`
- Retention: 365 days
- Used by: `App\Models\Report` (`LogsActivity` trait)
- Table: `activity_log`

## Security

**Google reCAPTCHA:**
- Package: `anhskohbo/no-captcha ^3.8`
- Config: `config/captcha.php`
- Env vars: `NOCAPTCHA_SECRET`, `NOCAPTCHA_SITEKEY`
- Status: **Config wired but keys not set** — needs `NOCAPTCHA_SECRET` and `NOCAPTCHA_SITEKEY` values

**Honeypot (Spatie):**
- Package: `spatie/laravel-honeypot ^4.7`
- Config: `config/honeypot.php`
- Enabled: true by default
- Field name: `my_name` (randomized)
- Timer: 1-second minimum submission time
- CSP integration: Disabled (`HONEYPOT_WITH_CSP=false`)
- Spam response: `BlankPageResponder` (returns empty page)

**Secure Headers (Bepsvpt):**
- Package: `bepsvpt/secure-headers ^9.1`
- Status: **Installed but NOT configured** — no `config/secure-headers.php` published
- Service provider auto-registered, uses default header values
- Missing custom config means headers may not be strict enough for production

**CSRF:**
- Laravel built-in `VerifyCsrfToken` middleware
- Applied to web routes and Filament admin routes

**Session Security:**
- `http_only: true` — JS cannot access session cookie
- `same_site: lax` — Mitigates CSRF
- `secure: auto` — Follows request scheme (needs explicit `SESSION_SECURE_COOKIE=true` in production)
- `encrypt: false` — Session data not encrypted at rest

## PWA

**Laravel PWA:**
- Package: `silviolleite/laravelpwa ^2.0`
- Config: `config/laravelpwa.php`
- App name: `NidVite`
- Short name: `NidVite`
- Start URL: `/signaler` (report page)
- Display: `standalone`
- Orientation: `portrait`
- Theme color: `#D97706` (amber-600)
- Background: `#FEF3C7` (amber-100)
- Status bar: `#D97706`
- Icons: 72→512px in `/images/icons/`
- Splash screens: 640→2048px for iOS devices
- Shortcut: "Signaler" → `/signaler` with 96x96 icon
- Service worker: Auto-generated by package

## Export

**Maatwebsite Excel:**
- Package: `maatwebsite/excel ^3.1`
- Status: **Installed but NOT used** — no export classes, no `config/excel.php` published
- No `App\Exports\*` classes found
- Likely intended for report data export in admin panel (not yet implemented)

## Integration Health Matrix

| Integration | Package | Configured | Tested | Production-Ready |
|-------------|---------|-----------|--------|-----------------|
| PostgreSQL + PostGIS | Built-in | ✅ Yes | ✅ CI tests | ✅ Yes |
| Laravel Reverb (WS) | `laravel/reverb` | ✅ Yes | ❌ Unknown | ⚠️ Needs `allowed_origins` restricted |
| Laravel Echo (client) | `laravel-echo` + `pusher-js` | ✅ Yes | ❌ Unknown | ⚠️ Scheme/port must match prod |
| Resend Email | `resend/resend-laravel` | ⚠️ Partial | ❌ No | ❌ No API key configured |
| Google reCAPTCHA | `anhskohbo/no-captcha` | ⚠️ Partial | ❌ No | ❌ No site key/secret set |
| Sentry Error Tracking | `sentry/sentry-laravel` | ❌ No config | ❌ No | ❌ No DSN configured |
| Spatie Health Checks | `spatie/laravel-health` | ❌ No config | ❌ No | ❌ No checks defined |
| Spatie Backup | `spatie/laravel-backup` | ❌ No config | ❌ No | ❌ Cannot run backups |
| Spatie Schedule Monitor | `spatie/laravel-schedule-monitor` | ❌ No config | ❌ No | ❌ Not monitored |
| Secure Headers | `bepsvpt/secure-headers` | ❌ No config | ❌ No | ⚠️ Default headers only |
| Maatwebsite Excel | `maatwebsite/excel` | ❌ No config | ❌ No | ❌ Not implemented |
| Honeypot | `spatie/laravel-honeypot` | ✅ Yes | ❌ Unknown | ✅ Active |
| Activity Log | `spatie/laravel-activitylog` | ✅ Yes | ❌ Unknown | ✅ Active |
| Media Library | `spatie/laravel-medialibrary` | ✅ Yes | ❌ Unknown | ✅ Active |
| Laravel PWA | `silviolleite/laravelpwa` | ✅ Yes | ❌ Unknown | ⚠️ Icons must exist on deploy |
| Leaflet / OSM | CDN-loaded | ✅ Yes | ❌ Unknown | ✅ Active (CDN dependency) |
| Nominatim API | No package | ✅ Inline | ❌ Unknown | ⚠️ Rate-limited (1 req/s) |
| Fortify Auth | `laravel/fortify` | ✅ Yes | ❌ Unknown | ✅ Active |
| 2FA + Passkeys | `fortify` + QR/TOTP | ✅ Yes | ❌ Unknown | ⚠️ Passkeys need HTTPS |
| Filament Admin | `filament/filament` | ✅ Yes | ❌ Unknown | ✅ Active |
| S3 File Storage | Built-in | ❌ No keys | ❌ No | ❌ Not configured |

---

*Integration audit: 2026-05-06*
