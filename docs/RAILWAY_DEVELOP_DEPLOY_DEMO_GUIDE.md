# Railway Deploy Guide (Develop Branch) for Staging Demo

Date: 2026-05-08  
Scope: Deploy develop branch to Railway staging and ensure demo-ready environment variables

## 1) Goal

Deploy the develop branch to Railway staging with all required services and environment variables so the staging demo passes preflight and UAT.

References:
- Runtime entrypoint and health check: railway.toml
- Web startup flow (migrate/cache/serve): deploy/railway/web.sh
- Worker command: deploy/railway/worker.sh
- Scheduler command: deploy/railway/scheduler.sh
- Staging readiness command: routes/console.php
- Demo seed command: routes/console.php
- Health route binding: bootstrap/app.php
- Branch note (default main auto deploy): docs/process/BRANCHING.md

## 2) Railway Service Topology

Create one Railway project with these services in the staging environment:

1. Web service
- Start command: sh deploy/railway/web.sh
- Health check path: /up

1. Worker service
- Start command: sh deploy/railway/worker.sh

1. Scheduler service
- Start command: sh deploy/railway/scheduler.sh
- If using cron mode, run every minute

1. Data services
- PostgreSQL
- Redis

## 3) Branch Configuration

Set each app service source to develop branch for staging deployment.  
Do not rely on default main auto-deploy behavior.

## 4) Required Environment Variables (Demo-Critical)

Set these in Railway staging.

### Core App

- APP_NAME=NidVite
- APP_ENV=staging
- APP_KEY=base64:GENERATED_KEY
- APP_DEBUG=false
- APP_URL=https://YOUR-STAGING-DOMAIN
- APP_LOCALE=fr
- APP_FALLBACK_LOCALE=fr

### Database (PostgreSQL)

- DB_CONNECTION=pgsql
- DB_HOST=from Railway Postgres
- DB_PORT=from Railway Postgres
- DB_DATABASE=from Railway Postgres
- DB_USERNAME=from Railway Postgres
- DB_PASSWORD=from Railway Postgres

Important:
- PostGIS must be available on the PostgreSQL service before first app boot.
- If Railway Postgres does not provide PostGIS in your environment, use a PostGIS-capable PostgreSQL service and update DB_* variables accordingly.

### Queue + Cache + Redis

These are required by staging readiness checks.

- QUEUE_CONNECTION=redis
- CACHE_STORE=redis
- REDIS_HOST=from Railway Redis
- REDIS_PORT=from Railway Redis
- REDIS_PASSWORD=from Railway Redis or null

### Session/Cookie

- SESSION_DRIVER=database
- SESSION_SECURE_COOKIE=true
- SESSION_HTTP_ONLY=true
- SESSION_SAME_SITE=lax

### File Storage (Cloudflare R2)

These are required by staging readiness checks.

- FILESYSTEM_DISK=r2
- MEDIA_DISK=r2
- R2_ACCESS_KEY_ID=...
- R2_SECRET_ACCESS_KEY=...
- R2_BUCKET=...
- R2_ENDPOINT=https://ACCOUNT_ID.r2.cloudflarestorage.com
- R2_URL=https://MEDIA_DOMAIN
- R2_DEFAULT_REGION=auto
- R2_USE_PATH_STYLE_ENDPOINT=false

### Mail (Staging-Safe)

Staging readiness enforces safe mailers.

- MAIL_MAILER=log
- MAIL_FROM_ADDRESS=noreply@YOUR_DOMAIN
- MAIL_FROM_NAME=NidVite

### CAPTCHA (Public Report Form Requires This)

Form requires recaptcha response.

- NOCAPTCHA_SITEKEY=...
- NOCAPTCHA_SECRET=...

### Broadcasting (Recommended for Demo Stability)

- BROADCAST_CONNECTION=log

Reason: report submit emits event. Using log avoids websocket dependency during staging demo.

### Build Runtime (Required)

- NIXPACKS_NODE_VERSION=20

Reason: frontend dependencies require Node >= 20 during npm ci.

## 5) First Deploy Sequence

After env vars are set:

1. Deploy web, worker, and scheduler services from develop.
2. Confirm web health check returns healthy at /up.
3. Open Railway web shell and run:
- php artisan ops:check-staging-readiness
- php artisan ops:seed-staging-demo --fresh

If deploy fails on migration with "extension postgis is not available":
1. Provision a PostgreSQL service/image with PostGIS support.
2. Point DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD to that service.
3. Redeploy web service.
4. Re-run readiness and seed commands.

## 6) Demo Data Hardening (Important)

Run these immediately after demo seed to ensure form defaults and geofence are reliable:

- php artisan db:seed --class=ReportCategorySeeder --force
- php artisan db:seed --class=MontrealBoundarySeeder --force
- php artisan db:seed --class=AdminUserSeeder --force
- php artisan ops:check-staging-readiness

Why:
- Form expects pothole category slug as default
- Pothole slug is seeded by ReportCategorySeeder
- Geofence checks Montreal boundary in model query logic
- Boundary row is seeded by MontrealBoundarySeeder
- Known admin credentials are seeded by AdminUserSeeder

## 7) Post-Deploy Verification

Run your existing UAT checklist:
- docs/engineering/STAGING_DEMO_CHECKLIST.md

Minimum checks:
- Public map loads with markers
- /api/reports/geojson returns features
- Admin dashboard widgets load without SQL errors
- Export actions create Excel and PDF
- Report submission passes with captcha and valid Montreal geolocation

## 8) Common No-Go Causes

- APP_ENV not staging
- QUEUE_CONNECTION not redis
- CACHE_STORE not redis
- FILESYSTEM_DISK not r2
- MAIL_MAILER not log or array
- Missing NOCAPTCHA keys (report form blocked)
- PostGIS extension unavailable during migration
- NIXPACKS_NODE_VERSION missing or below 20 (npm ci/build failures)

## 9) Sign-off Template

- Commit SHA tested:
- Operator:
- Date/time:
- Go or No-go:
- Follow-up actions:

