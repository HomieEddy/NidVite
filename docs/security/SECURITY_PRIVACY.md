# Security & Privacy Guidelines

NidVite handles sensitive citizen data: GPS coordinates, email addresses, and photos. This document defines mandatory security controls and privacy practices.

---

## 1. Geolocation Privacy

### Principle
GPS coordinates are **personally identifiable information (PII)**. We must minimize exposure and prevent re-identification.

### Controls

- **Database Encryption**: PostgreSQL data-at-rest is handled by Railway (managed service). No additional application-level encryption is required for MVP.
- **API Exposure**: The public PWA must never expose raw coordinates in JSON responses. If a citizen views their report status, show an approximate location or a static map image, not the `lat/lng` values.
- **Filament Dashboard**: Only authenticated entrepreneurs can view exact coordinates.

---

## 2. Photo Metadata (EXIF Stripping)

### Principle
Photos uploaded by citizens may contain embedded GPS metadata revealing their exact location (e.g., if taken indoors near the pothole). This must be stripped before storage.

### Implementation

Use `intervention/image` in a Spatie media conversion:

```php
// In Report model
public function registerMediaConversions(Media $media = null): void
{
    $this->addMediaConversion('thumb')
        ->width(300)
        ->height(300)
        ->sharpen(10)
        ->strip() // Removes all EXIF metadata
        ->nonQueued();
}
```

**Verification:** All uploaded images must be checked in CI to confirm no GPS tags remain. A Pest test should inspect the binary for `GPSLatitude` or `GPSLongitude` presence.

---

## 3. Rate Limiting

### Principle
Without authentication, the public report form is vulnerable to spam and abuse. We implement multi-layer rate limiting.

### Layers

**Layer 1: IP-Based Throttling**
```php
// In RouteServiceProvider or middleware
RateLimiter::for('report', function (Request $request) {
    return Limit::perHour(5)->by($request->ip());
});
```

**Layer 2: Device Fingerprinting**
- Generate a hash from `User-Agent` + `Canvas fingerprint` + `WebGL fingerprint` + `fonts`.
- Store the hash in the `reports.device_fingerprint` column.
- Rate limit: max 3 reports per fingerprint per hour.

**Layer 3: Email-Based Throttling**
- Max 3 reports per email address per hour.
- Prevents a single actor from rotating devices/IPs.

**Rate Limit Response:**
```json
{
    "message": "Too many reports submitted. Please try again later.",
    "retry_after": 3600
}
```

---

## 4. Admin Brute Force Protection

### Principle
Admin accounts are high-value targets. Login endpoints require stricter protection than the public PWA.

### Controls

**Separate Throttle for Admin Login:**
```php
RateLimiter::for('admin-login', function (Request $request) {
    return Limit::perMinutes(15, 5)->by($request->ip());
});
```

**Account Lockout:**
- After 5 failed login attempts, lock account for 1 hour.
- Admin can manually unlock from user management panel.
- Failed attempts logged in `admin_audit_log`.

**Session Security:**
```env
SESSION_SECURE_COOKIE=true      # HTTPS only
SESSION_HTTP_ONLY=true          # No JavaScript access
SESSION_SAME_SITE=strict        # CSRF protection
SESSION_LIFETIME=15             # 15 minutes idle timeout
```

**Single Session Per User:**
- New login invalidates all previous sessions.
- `admin_sessions` table tracks active sessions.
- Admin can force-logout any user from dashboard.

---

## 5. XSS Prevention

### Principle
Citizen inputs (report descriptions) must not execute as scripts in other users' browsers.

### Controls

- **Input Sanitization**: Strip all HTML tags from `description` field on input.
- **Output Escaping**: Always use `{{ }}` in Blade (not `{!! !!}`) unless explicitly sanitized.
- **Content Security Policy (CSP)**: `bepsvpt/secure-headers` enforces CSP headers blocking inline scripts.
- **Stored XSS Test**: CI test verifies no `<script>` tags persist in database.

---

## 6. Timing Attack Protection

### Principle
System response time must not leak information about data existence.

### Controls

**Email Existence:**
- Always respond: "If this email has reports, you will receive updates."
- Never reveal whether an email exists in the database.
- Use constant-time comparison where possible.

**Login Responses:**
- Same response time for "user not found" vs "wrong password".
- Laravel Fortify handles this by default.

---

## 7. Session Fixation & Hijacking

### Principle
Prevent attackers from stealing or reusing admin sessions.

### Controls

- **Regenerate Session ID**: On login, `Session::regenerate()` (handled by Fortify).
- **Device Fingerprinting**: Session bound to User-Agent + IP hash.
- **Concurrent Session Detection**: Alert user if new login from different device.
- **Idle Timeout**: 15-minute inactivity logout.

---

## 8. SQL Injection Prevention

### Principle
All database queries must be parameterized. Never trust user input in raw SQL.

### Controls

- **Ban `DB::raw()` without bindings**: CodeRabbit enforces this.
- **Use Eloquent or Query Builder**: Always use parameterized queries.
- **PostGIS Queries**: Pass coordinates as bound parameters:
```php
// Good
DB::select("SELECT * FROM reports WHERE ST_DWithin(location, ?, 5)", [$point]);

// Bad — NEVER DO THIS
DB::select("SELECT * FROM reports WHERE ST_DWithin(location, $point, 5)");
```

---

## 9. Path Traversal Prevention

### Principle
File uploads must not allow directory traversal attacks.

### Controls

- **Spatie Media Library**: Handles safe storage paths automatically.
- **No Custom Upload Logic**: Use Spatie's `addMedia()` method exclusively.
- **Filename Sanitization**: `basename()` applied to all uploaded filenames.

---

## 10. Anti-Spam (Honeypot + reCAPTCHA)

### Honeypot (`spatie/laravel-honeypot`)
- Renders a hidden field (`my_name`) that is invisible to humans but filled by bots.
- If the field contains data, the request is rejected silently (HTTP 200 with no action taken) to avoid revealing the defense mechanism.

### reCAPTCHA v2 Invisible (`anhskohbo/no-captcha`)
- Binds to the form submit button.
- Auto-verifies in the background.
- Falls back to a visual challenge if the user is flagged as suspicious.

**Fail-Closed Policy:** If reCAPTCHA's API is unreachable, the form submission must fail with a 503 and instruct the user to retry. Do not bypass reCAPTCHA on API errors.

---

## 11. Geofencing

### Principle
Reports must be physically located within the Montreal metropolitan area. This prevents spam from outside the service area.

### Implementation

The `ValidateGeofence` Action checks against the `montreal_boundary` table:

```php
public function execute(Point $location): bool
{
    $result = DB::selectOne("
        SELECT ST_Within(
            ST_SetSRID(ST_MakePoint(?, ?), 4326)::geometry,
            (SELECT boundary::geometry FROM montreal_boundary LIMIT 1)
        ) as inside
    ", [$location->getLng(), $location->getLat()]);

    return (bool) $result->inside;
}
```

**UX:** If geofencing fails, the user sees: "NidVite currently only accepts reports within the Montreal area."

---

## 12. Compliance: Quebec Law 25

### Principle
Quebec's **Law 25** requires explicit consent for collecting personal information and mandates transparency about data use.

### Controls

- **Consent Notice**: The report form must display a clear statement before submission:
  > "By submitting this report, you consent to NidVite storing your email address and approximate location for the purpose of tracking repair progress."
- **Data Minimization**: We only collect email, location, and photos. No names, phone numbers, or addresses.
- **Right to Deletion**: Citizens can request deletion by emailing `privacy@nidvite.ca`. The entrepreneur must delete the report and associated media within 30 days.
- **Breach Notification**: If a data breach occurs, we must notify affected users and the Commission d'accès à l'information du Québec (CAI) within 72 hours.

### Required Pages
- [ ] `/privacy` — Privacy Policy (data collection, retention, deletion rights)
- [ ] `/terms` — Terms of Service (liability, acceptable use)

**MVP Note:** These pages can be static HTML/Markdown rendered via a simple Blade view. They do not need to be dynamic.

---

## 13. Dependency Security

- **Dependabot**: Enable on the GitHub repository for automatic security updates.
- `composer audit`: Run before every release.
- `npm audit`: Run before every release.

---

## Security Checklist (Per Feature)

Before any feature is merged, verify:

- [ ] No raw coordinates exposed in public APIs
- [ ] All uploaded images pass EXIF stripping
- [ ] Rate limiting applies to new endpoints (both public and admin)
- [ ] Admin endpoints have separate (stricter) rate limits
- [ ] reCAPTCHA validation covers new public forms
- [ ] Input is validated via Form Requests or Livewire rules
- [ ] Output is escaped in Blade (`{{ }}` not `{!! !!}` unless explicitly sanitized)
- [ ] Filament actions are protected by policies
- [ ] No `DB::raw()` without parameterization
- [ ] No secrets committed to Git (use `.env` only)
- [ ] Session secure flags configured (`secure`, `httpOnly`, `sameSite`)
- [ ] 2FA enforced for admin roles in production
- [ ] Audit logging covers data changes

---

*This document is a living record. Security is everyone's responsibility. Propose changes via PR.*
