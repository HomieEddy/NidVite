# Railway Runtime (Phase 5)

This document defines the production process commands introduced for Phase 5 infrastructure hardening.

## Required Services

Create three Railway services from the same repository:

1. Web service
- Start command: `sh deploy/railway/web.sh`
- Health check path: `/up`

2. Worker service
- Start command: `sh deploy/railway/worker.sh`

3. Scheduler service (cron)
- Command: `sh deploy/railway/scheduler.sh`
- Cron expression: `* * * * *`

## Environment Variables

Set these in Railway for production:

- `QUEUE_CONNECTION=redis`
- `FILESYSTEM_DISK=r2`
- `MEDIA_DISK=r2`
- `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`
- `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`
- `R2_BUCKET`, `R2_ENDPOINT`, `R2_URL`
- `R2_DEFAULT_REGION=auto`

## Signed Media Access

Report photos are private in object storage and exposed through a signed app route:

- Route: `/media/{media}` (`media.signed`)
- Route protection: Laravel signed middleware
- Controller behavior: validates report-photo media + issues short-lived R2 temporary URL redirect

## Verification Checklist

- `php artisan queue:work redis --once` succeeds
- `php artisan schedule:run` executes without errors
- `php artisan tinker` confirms `config('filesystems.disks.r2.driver') === 's3'`
- Open a signed media link from report tracking and verify redirect to a temporary R2 URL
