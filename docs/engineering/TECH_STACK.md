# Tech Stack & Dependencies

> Last updated: 2026-05-06 — Audited against composer.json and actual config state

---

## Runtime & Framework

| Technology | Version | Justification |
|------------|---------|---------------|
| PHP | `^8.2` | Laravel 11 minimum. Docker uses 8.3. |
| Laravel | `^11.0` | Latest stable. Required for Filament v5. |
| Node.js | `^20.0` | Vite asset building. LTS version. |

---

## Database

| Technology | Version | Justification |
|------------|---------|---------------|
| PostgreSQL | `>= 15` | Required for PostGIS extension. |
| PostGIS | `>= 3.4` | Spatial queries, `ST_Contains`, `geography` type. |
| Redis | `alpine` | Reverb scaling + future queue driver. |

---

## Admin Panel & Media

| Package | Installed Version | Config Status | Purpose |
|---------|-------------------|---------------|---------|
| `filament/filament` | `^5.6` | Active | Admin panel v5 (not v3 as originally planned) |
| `spatie/laravel-medialibrary` | `^11.22` | Active (config published) | Photo storage, conversions, EXIF stripping |
| `intervention/image` | `^3.11` | Active | Image processing, EXIF removal |
| `pragmarx/google2fa-laravel` | `^3.0` | Active | TOTP 2FA with QR codes |
| `bacon/bacon-qr-code` | `^3.1` | Active | QR code generation for 2FA setup |

**Not installed:** `filament/spatie-laravel-media-library-plugin` (was in docs but not in composer.json)

---

## Real-time

| Package | Installed Version | Config Status | Purpose |
|---------|-------------------|---------------|---------|
| `laravel/reverb` | `^1.10` | Active (config published) | WebSocket server for dashboard notifications |

---

## Anti-Spam & Security

| Package | Installed Version | Config Status | Purpose |
|---------|-------------------|---------------|---------|
| `spatie/laravel-honeypot` | `^4.7` | Active (config published) | Invisible honeypot field |
| `anhskohbo/no-captcha` | `^3.8` | Active (config published) | reCAPTCHA v2 (installed but not enforced in form validation) |
| `bepsvpt/secure-headers` | `^9.1` | Installed (not actively configured) | Security headers middleware |
| `jenssegers/agent` | `^2.6` | Installed | User-agent parsing for device fingerprinting |

**Not installed:** `matanyada/laravel-postgis` (was in docs but not in composer.json — PostGIS queries done natively)

---

## Auth & RBAC

| Package | Installed Version | Config Status | Purpose |
|---------|-------------------|---------------|---------|
| `laravel/fortify` | `^1.37` | Active (config published) | Auth scaffolding with 2FA + passkeys |

---

## Mail

| Package | Installed Version | Config Status | Purpose |
|---------|-------------------|---------------|---------|
| `resend/resend-laravel` | `^1.3` | Active | Resend mail driver for transactional emails |

---

## PWA

| Package | Installed Version | Config Status | Purpose |
|---------|-------------------|---------------|---------|
| `silviolleite/laravelpwa` | `^2.0` | Active (config published) | Service worker, manifest, offline support |

---

## Maps

| Technology | Version | Purpose |
|------------|---------|---------|
| Leaflet | CDN-loaded | Used in public map page and tracking page |
| MapLibre GL JS | NOT YET | Planned for Filament dashboard (not currently used) |
| MapTiler | NOT YET | Planned but not yet integrated |

**Note:** The public map and tracking pages currently use Leaflet via CDN, not MapLibre. The admin map widget is an iframe of the Leaflet page.

---

## Testing

| Package | Installed Version | Config Status | Purpose |
|---------|-------------------|---------------|---------|
| `pestphp/pest` | `^2.0` | Active | Test framework (v2, not v3 as originally planned) |
| `pestphp/pest-plugin-laravel` | `^2.0` | Active | Laravel bindings |
| `pestphp/pest-plugin-faker` | `^2.0` | Active | Fake data generation |
| `nunomaduro/larastan` | `^2.0` | Active | PHPStan Level 5 |

**Not installed:** `pestphp/pest-plugin-livewire` (was in docs but not in composer.json)

---

## Audit & Logging

| Package | Installed Version | Config Status | Purpose |
|---------|-------------------|---------------|---------|
| `spatie/laravel-activitylog` | `^4.12` | Active (config published) | Audit trail on Report changes |

---

## Monitoring & Observability

| Package | Installed Version | Config Status | Purpose |
|---------|-------------------|---------------|---------|
| `sentry/sentry-laravel` | `^4.25` | **Installed but NOT configured** | Error tracking (no config/sentry.php) |
| `spatie/laravel-health` | `^1.39` | **Installed but NOT configured** | Health checks (no config/health.php, no checks) |
| `spatie/laravel-schedule-monitor` | `^4.3` | **Installed but NOT configured** | Schedule monitoring (no scheduled tasks) |

---

## Performance & Caching

| Package | Installed Version | Config Status | Purpose |
|---------|-------------------|---------------|---------|
| `spatie/laravel-backup` | `^9.0` | **Installed but NOT configured** | DB backups to R2 (no config/backup.php) |

**Not installed (despite being in docs):**
- `appstract/laravel-opcache` — NOT in composer.json
- `spatie/laravel-response-cache` — NOT in composer.json

---

## Export

| Package | Installed Version | Config Status | Purpose |
|---------|-------------------|---------------|---------|
| `maatwebsite/laravel-excel` | `^3.1` | **Installed but NOT used** | No export classes created yet |

---

## Development Tools

| Package | Installed Version | Purpose |
|---------|-------------------|---------|
| `laravel/sail` | `^1.26` | Docker dev environment |
| `laravel/telescope` | `^5.0` | Local debug dashboard (config published) |
| `laravel/pint` | `^1.13` | Code formatting (enforced in CI) |
| `barryvdh/laravel-debugbar` | `^3.0` | Query profiling (config published) |

---

## Configuration Status Summary

| Status | Count | Packages |
|--------|-------|----------|
| **Active & Configured** | 14 | filament, medialibrary, image, reverb, fortify, honeypot, no-captcha, resend, laravelpwa, activitylog, google2fa, bacon-qr, agent, pest/larastan |
| **Installed but NOT Configured** | 4 | sentry, health, schedule-monitor, backup |
| **Installed but NOT Used** | 2 | excel (no export classes), secure-headers (not active) |
| **In Docs but NOT Installed** | 4 | opcache, response-cache, postgis-eloquent, filament-media-plugin |

---

## npm Dependencies

```bash
npm install maplibre-gl  # Planned but not yet used (Leaflet used instead)
```

---

## Alternatives Considered (and Rejected)

| Category | Rejected Option | Reason |
|----------|-----------------|--------|
| Admin Panel | Custom Livewire CRUD | Filament provides widgets, tables, forms out-of-box |
| Maps (public) | MapLibre GL JS | Leaflet simpler for MVP; MapLibre planned for admin |
| Storage | AWS S3 | Cloudflare R2 has zero egress fees |
| Queue | Redis | Database queue sufficient for MVP |
| Anti-Spam | reCAPTCHA v3 | v2 Invisible more predictable |
| PWA | Hand-rolled SW | laravelpwa handles manifest + SW scaffolding |
| Real-time | Pusher | Reverb is Laravel-native and free |
| Auth | Laravel Breeze | Fortify provides headless auth with 2FA |
| Export | Custom CSV | laravel-excel handles formatting + multi-sheet |

---

*Updated 2026-05-06 — Reflects actual composer.json and config state.*
