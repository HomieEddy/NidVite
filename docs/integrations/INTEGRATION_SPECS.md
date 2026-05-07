# API & Integration Specifications

> Last updated: 2026-05-06 — Audited against actual implementation

---

## 1. Leaflet + Map (Public Map)

### Current Implementation
- **Public map** (`/carte`): Leaflet loaded via CDN, GeoJSON from `/api/reports/geojson`
- **Tracking page** (`/suivi/{uuid}`): Leaflet for single report location
- **Admin**: ReportsMap widget is an iframe of the public map page

### API Endpoints

| Method | Path | Handler | Response |
|--------|------|---------|----------|
| GET | `/api/reports/geojson` | `MapController@geojson` | GeoJSON FeatureCollection of all non-spam reports |
| GET | `/api/reports/{uuid}/lookup` | `ReportTrackingController@lookup` | JSON: status, progress, timeline data |

**Note:** These are defined in `routes/web.php`, not `routes/api.php` (no separate API routes file exists).

### Planned
- MapLibre GL JS for Filament admin dashboard (not yet implemented)
- MapTiler vector tiles (not yet integrated)

---

## 2. Resend (Transactional Email)

### Purpose
Send automated emails to citizens when their report status changes.

### Current Implementation

**Mailable:** `app/Mail/ReportStatusUpdated.php`
- Implements `ShouldQueue` (queued via database driver)
- Uses `report.preferred_locale` to select FR or EN content
- Markdown template: `resources/views/emails/report-status-updated.blade.php`

**Email triggers:** `Report::transitionTo()` calls `sendStatusNotification()` on every status change.

**Template content:**
- Localized subject line (FR/EN)
- Status change body with old→new status
- Rejection reason (if applicable)
- UUID reference
- Date (localized)
- Tracking button linking to `/suivi/{uuid}`
- Footer signature

### Configuration

```env
MAIL_MAILER=resend
RESEND_API_KEY=re_xxxxxxxx
MAIL_FROM_ADDRESS="updates@nidvite.ca"
MAIL_FROM_NAME="NidVite"
```

### Gaps
- No bounced email handling (3 retries → permanent fail)
- No email delivery tracking table
- No idempotency check (could send duplicate emails if called twice)

---

## 3. Cloudflare R2 (Object Storage)

### Purpose
Production photo storage. R2 is S3-compatible with zero egress fees.

### Current Implementation
- **Development:** Photos stored locally via `FILESYSTEM_DISK=local`
- **Production:** Not yet configured. Spatie Media Library is set up to use the default disk, so switching to R2 requires only env/config changes.

### Configuration (Production)

```env
FILESYSTEM_DISK=r2
R2_ACCESS_KEY_ID=your-r2-key
R2_SECRET_ACCESS_KEY=your-r2-secret
R2_BUCKET=nidvite-media
R2_ENDPOINT=https://your-account.r2.cloudflarestorage.com
R2_URL=https://media.nidvite.ca
```

`config/filesystems.php` must add an `r2` disk:
```php
'r2' => [
    'driver' => 's3',
    'key' => env('R2_ACCESS_KEY_ID'),
    'secret' => env('R2_SECRET_ACCESS_KEY'),
    'region' => 'auto',
    'bucket' => env('R2_BUCKET'),
    'endpoint' => env('R2_ENDPOINT'),
    'url' => env('R2_URL'),
    'visibility' => 'public',
],
```

### Media Collections

```php
// Report model — only 'report-photos' collection exists currently
public function registerMediaCollections(): void
{
    $this->addMediaCollection('report-photos')
        ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
        ->maxNumberOfFiles(5);
}
```

**Note:** `after_photos` collection is NOT yet registered. Adding it is a remaining task.

---

## 4. Google reCAPTCHA v2 (Anti-Spam)

### Current Implementation
- Package installed: `anhskohbo/no-captcha` ^3.8
- Config published: `config/captcha.php`
- Captcha field present in report form

### Gaps
- **NOT enforced in validation** — The `g-recaptcha-response` field exists in the form but is not required in the Livewire validation rules
- Honeypot (`spatie/laravel-honeypot`) IS active and working

### Configuration

```env
NOCAPTCHA_SECRET=your-secret-key
NOCAPTCHA_SITEKEY=your-site-key
```

---

## 5. Montreal Open Data (Geofencing Boundary)

### Purpose
Provide the Montreal boundary polygon for geofence validation.

### Current Implementation

**Seeder:** `MontrealBoundarySeeder` downloads GeoJSON from Montreal Open Data and stores it as a PostGIS geography polygon.

**Validation:** `MontrealBoundary::contains(float $lat, float $lng): bool` uses `ST_Contains` to check if a point is within the boundary.

**Called by:** The report-form Livewire component before saving a new report.

### Data Source
- URL: `https://donnees.montreal.ca/dataset/limites-terrestres`
- Format: GeoJSON
- License: CC-BY

---

## 6. Laravel Reverb (Real-time Notifications)

### Current Implementation

**Event:** `App\Events\ReportCreated` broadcasts on `private-admin.reports` channel when a new report is submitted.

**Channel authorization:** Only admin and manager users can subscribe to `private-admin.reports`.

**Frontend:** `resources/js/reverb-listener.js` listens for new reports and shows browser notifications in the admin panel.

**Admin panel integration:** Reverb scripts injected via Filament renderHook in `AdminPanelProvider.php` for admin/manager users.

### Configuration

```env
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

---

## 7. Laravel Fortify (Authentication)

### Current Implementation

**Features enabled:**
- Login / Logout
- Password reset
- Email verification (commented out in config)
- Profile update (name, email, password)
- Two-factor authentication (TOTP with QR codes + recovery codes)
- Passkeys/WebAuthn (migration created, enabled in config)

**Fortify Actions:** Custom implementations in `app/Actions/Fortify/`:
- `CreateNewUser`, `UpdateUserProfileInformation`, `UpdateUserPassword`, `ResetUserPassword`

**2FA:** Uses `pragmarx/google2fa-laravel` + `bacon/bacon-qr-code` for TOTP with QR code setup.

---

## Credential Checklist

Before deploying to production, ensure:

- [ ] `RESEND_API_KEY` (from https://resend.com, domain verified)
- [ ] `NOCAPTCHA_SECRET` + `NOCAPTCHA_SITEKEY` (from https://www.google.com/recaptcha/admin)
- [ ] `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`, `R2_BUCKET`, `R2_ENDPOINT` (from Cloudflare)
- [ ] `MAIL_FROM_ADDRESS` domain verified in Resend
- [ ] `SENTRY_LARAVEL_DSN` (from https://sentry.io, after publishing config)
- [ ] `MAPTILER_API_KEY` (when MapLibre integration is built)

---

*Updated 2026-05-06 — Reflects actual integration state.*
