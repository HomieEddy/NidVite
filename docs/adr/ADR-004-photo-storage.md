# ADR-004: Photo Storage with Spatie Media Library

## Status
Accepted

## Context

The PRD requires two photo workflows:
1. **Citizen Facing**: Upload "Before" photos via camera integration.
2. **Entrepreneur Dashboard**: Upload "After" photos when a job is marked complete.

We need a storage strategy that works for local development and production (Railway), while supporting image conversions (thumbnails) and privacy controls (EXIF stripping).

### Options Considered

1. **Local Disk Only**: Store files on the server's filesystem.
   - *Pros*: Simple. No external dependencies.
   - *Cons*: Railway's filesystem is ephemeral. Files are lost on every deploy. Not viable for production.

2. **Cloud Storage Only (S3/R2)**: Store all files on a remote object store from day one.
   - *Pros*: Durable. Scales automatically.
   - *Cons*: Requires internet connection and valid credentials for local development. Slightly slower iteration.

3. **Hybrid (Local for Dev, R2 for Prod)**: Use the `local` disk in development and Cloudflare R2 in production.
   - *Pros*: Fast local development. Durable production storage. Cost-effective (R2 has zero egress fees).
   - *Cons*: Slightly more complex configuration. Must ensure both disks are tested.

## Decision

We will adopt **Option 3: Hybrid Storage** using `spatie/laravel-medialibrary`.

The package will be configured with a `media` disk that resolves to:
- **`local`** in development (`.env`: `FILESYSTEM_DISK=local`)
- **`r2`** in production (`.env`: `FILESYSTEM_DISK=r2`)

Spatie's conversions will generate thumbnails for the Filament dashboard and the PWA status page.

## Consequences

- **Positive**: Developers can work offline without configuring R2 credentials.
- **Positive**: R2 has no egress fees, making it cost-effective for a bootstrapped project.
- **Positive**: Spatie handles image conversions, responsive images, and collection organization (e.g., `before_photos`, `after_photos`).
- **Negative**: Must ensure the `r2` disk configuration in `config/filesystems.php` is tested before production deployment.

## Implementation Notes

- **EXIF Stripping**: `intervention/image` will be used in a Spatie media conversion to strip GPS and metadata before saving. This is mandatory for privacy (see SECURITY_PRIVACY.md).
- **Collections**: Define two media collections on the `Report` model:
  - `before_photos`: Citizen uploads. Max 3 files, 5MB each.
  - `after_photos`: Entrepreneur uploads. Max 3 files, 5MB each.
- **Filament Integration**: Use `filament/spatie-laravel-media-library-plugin` to render upload fields in the `ReportResource` form.

## Related Decisions

- SECURITY_PRIVACY.md: Mandates EXIF stripping on all uploads.
- TECH_STACK.md: Lists `spatie/laravel-medialibrary` and `intervention/image`.
