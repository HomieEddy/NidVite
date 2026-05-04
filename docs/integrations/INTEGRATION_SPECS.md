# API & Integration Specifications

This document defines the contracts, configurations, and credentials required for all external services integrated into NidVite.

---

## 1. MapLibre + MapTiler (Maps)

### Purpose
- **PWA**: Display a static map with the user's report pin after submission.
- **Dashboard**: Full interactive map showing all reports, clusters, and status colors.

### Configuration

**`.env`:**
```env
MAPTILER_API_KEY=your-maptiler-key
```

**`config/services.php`:**
```php
'maptiler' => [
    'key' => env('MAPTILER_API_KEY'),
    'style' => 'https://api.maptiler.com/maps/streets/style.json?key=' . env('MAPTILER_API_KEY'),
],
```

**Frontend Initialization (MapLibre):**
```javascript
const map = new maplibregl.Map({
    container: 'map',
    style: '{{ config("services.maptiler.style") }}',
    center: [-73.5673, 45.5017], // Montreal
    zoom: 12,
});
```

### Usage Limits (MapTiler Free Tier)
- **Vector tiles**: 100,000 requests/month.
- **Static maps**: 100,000 requests/month.
- **Geocoding**: 5,000 requests/day.

**Monitoring:** If we approach 80% of the free tier, we must upgrade or implement tile caching.

---

## 2. Resend (Transactional Email)

### Purpose
Send automated emails to citizens when their report status changes to "Repaired."

### Configuration

**`.env`:**
```env
MAIL_MAILER=resend
RESEND_API_KEY=re_xxxxxxxx
MAIL_FROM_ADDRESS="updates@nidvite.ca"
MAIL_FROM_NAME="NidVite"
```

**Email Template Spec:**
- **Subject**: `Your NidVite report has been repaired — #{uuid}`
- **Body**:
  - Greeting: "Hi there," (we do not store names, only emails).
  - Status update: "Your report submitted on {date} has been marked as REPAIRED."
  - Photo evidence: Include a thumbnail of the "After" photo if available.
  - Tracking link: `https://nidvite.ca/track/{uuid}`
  - Footer: "You received this because you submitted a report on NidVite."

**Contract:** The `SendJobCompletionEmail` Action must be idempotent. If called twice for the same report, it should not send a duplicate email. This is enforced by checking `activity_log` for existing "email_sent" events.

---

## 3. Cloudflare R2 (Object Storage)

### Purpose
Store "Before" and "After" photos in production. R2 is S3-compatible with zero egress fees.

### Configuration

**`.env` (Production only):**
```env
FILESYSTEM_DISK=r2
R2_ACCESS_KEY_ID=your-r2-access-key
R2_SECRET_ACCESS_KEY=your-r2-secret-key
R2_BUCKET=nidvite-media
R2_ENDPOINT=https://your-account-id.r2.cloudflarestorage.com
R2_URL=https://media.nidvite.ca
```

**`config/filesystems.php`:**
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

**Spatie Media Library Config:**
```php
// In Report model
public function registerMediaCollections(): void
{
    $this->addMediaCollection('before_photos')
        ->useDisk(config('filesystems.default'));
}
```

**Contract:** In development, `config('filesystems.default')` resolves to `local`. In production, it resolves to `r2`. No code changes required between environments.

---

## 4. Google reCAPTCHA v2 Invisible (Anti-Spam)

### Purpose
Second-line bot protection on the public report submission form (ADR-003).

### Configuration

**`.env`:**
```env
NOCAPTCHA_SECRET=your-secret-key
NOCAPTCHA_SITEKEY=your-site-key
```

**Blade Component (in `ReportForm`):**
```blade
<form wire:submit="submit">
    {{-- Honeypot field (first line) --}}
    <x-honeypot />

    {{-- reCAPTCHA (second line) --}}
    {!! NoCaptcha::renderJs() !!}
    {!! NoCaptcha::display(['data-size' => 'invisible']) !!}

    {{-- Form fields --}}
    ...
</form>
```

**Validation Rule:**
```php
public function rules(): array
{
    return [
        'email' => ['required', 'email'],
        'location' => ['required'],
        'g-recaptcha-response' => ['required', 'captcha'],
    ];
}
```

**Contract:** If reCAPTCHA validation fails, the form must return a 422 with a clear error message: "Unable to verify you are human. Please try again."

---

## 5. Montreal Open Data (Geofencing Boundary)

### Purpose
Provide the authoritative Montreal metropolitan boundary polygon for geofencing validation.

### Data Source
- **URL**: `https://donnees.montreal.ca/dataset/limites-terrestres`
- **Format**: GeoJSON
- **License**: Creative Commons Attribution (CC-BY)

**Seeder Logic:**
```php
$geojson = file_get_contents('https://donnees.montreal.ca/.../limites.geojson');
$data = json_decode($geojson, true);

DB::table('montreal_boundary')->insert([
    'name' => 'Montreal Metropolitan Area',
    'boundary' => DB::raw("ST_GeomFromGeoJSON('" . json_encode($data['features'][0]['geometry']) . "')::geography"),
]);
```

**Fallback:** If the open data portal is unavailable, the seeder uses a simplified bounding box stored in `database/seeders/data/montreal_fallback.geojson`.

**Contract:** The boundary must be imported before any report can pass geofencing. The `ValidateGeofence` Action returns `false` if the `montreal_boundary` table is empty.

---

## Credential Checklist

Before deploying to production, ensure the following are configured:

- [ ] `RESEND_API_KEY` (from https://resend.com)
- [ ] `MAPTILER_API_KEY` (from https://cloud.maptiler.com)
- [ ] `NOCAPTCHA_SECRET` and `NOCAPTCHA_SITEKEY` (from https://www.google.com/recaptcha/admin)
- [ ] `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`, `R2_BUCKET`, `R2_ENDPOINT` (from Cloudflare dashboard)
- [ ] `MAIL_FROM_ADDRESS` and `MAIL_FROM_NAME` (domain must be verified in Resend)

---

*This document is a living record. Rotate keys immediately if compromised. Propose changes via PR.*
