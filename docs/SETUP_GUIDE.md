# Environment Setup Guide

This guide will get a new developer running NidVite locally within 15 minutes.

**Prerequisites:** Docker Desktop installed and running.

---

## Step 1: Clone and Install

```bash
git clone git@github.com:your-org/nid-vite.git
cd nid-vite

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

---

## Step 2: Configure Sail with PostGIS

Laravel Sail's default `docker-compose.yml` uses a standard PostgreSQL image. We must override it to use PostGIS.

### 2a. Publish Sail's Docker files

```bash
php artisan sail:publish
```

### 2b. Modify `docker-compose.yml`

Find the `pgsql` service and replace the image:

```yaml
services:
  pgsql:
    image: 'postgis/postgis:15-3.4'
    # ... keep existing ports, volumes, environment, healthcheck
```

### 2c. Start Sail

```bash
./vendor/bin/sail up -d
```

Wait for the containers to be healthy. You can check with:

```bash
./vendor/bin/sail ps
```

---

## Step 3: Enable PostGIS Extension

Connect to the database and enable the extension:

```bash
./vendor/bin/sail psql
```

Then run:

```sql
CREATE EXTENSION IF NOT EXISTS postgis;
\q
```

Verify:

```bash
./vendor/bin/sail artisan tinker
>>> DB::select("SELECT PostGIS_Version()")
```

You should see a version string like `3.4.2`.

---

## Step 4: Configure `.env`

Edit `.env` with the following values:

```env
# Application
APP_NAME=NidVite
APP_ENV=local
APP_KEY=base64:xxx
APP_DEBUG=true
APP_URL=http://localhost

# Database (Sail defaults)
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=nid_vite
DB_USERNAME=sail
DB_PASSWORD=password

# Queue (database driver for MVP)
QUEUE_CONNECTION=database

# Cache (database driver for MVP)
CACHE_DRIVER=database

# Session Security
SESSION_SECURE_COOKIE=false       # Set to true in production (HTTPS only)
SESSION_HTTP_ONLY=true            # Prevent JavaScript access
SESSION_SAME_SITE=strict          # CSRF protection
SESSION_LIFETIME=15               # 15 minutes idle timeout

# Mail (get your API key from https://resend.com)
MAIL_MAILER=resend
RESEND_API_KEY=re_xxxxxxxx
MAIL_FROM_ADDRESS="updates@nidvite.ca"
MAIL_FROM_NAME="NidVite"

# File Storage (local for dev)
FILESYSTEM_DISK=local

# reCAPTCHA (get keys from https://www.google.com/recaptcha/admin)
NOCAPTCHA_SECRET=your-secret-key
NOCAPTCHA_SITEKEY=your-site-key

# MapTiler (get a free key from https://cloud.maptiler.com)
MAPTILER_API_KEY=your-maptiler-key

# Cloudflare R2 (production only)
R2_ACCESS_KEY_ID=your-r2-key
R2_SECRET_ACCESS_KEY=your-r2-secret
R2_BUCKET=nidvite-media
R2_ENDPOINT=https://your-account.r2.cloudflarestorage.com
R2_URL=https://media.nidvite.ca

# Sentry (production only)
SENTRY_LARAVEL_DSN=your-sentry-dsn
SENTRY_TRACES_SAMPLE_RATE=0.1

# Reverb (real-time WebSockets)
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

---

## Step 5: Run Migrations and Seeders

```bash
./vendor/bin/sail artisan migrate

# Seed roles and permissions
./vendor/bin/sail artisan db:seed --class=RolePermissionSeeder

# Seed expense categories
./vendor/bin/sail artisan db:seed --class=ExpenseCategorySeeder

# Seed report categories
./vendor/bin/sail artisan db:seed --class=ReportCategorySeeder

# Seed the Montreal boundary (downloads GeoJSON from open data)
./vendor/bin/sail artisan db:seed --class=MontrealBoundarySeeder
```

**Note:** If the Montreal Open Data portal is unavailable, the seeder will fall back to a simplified bounding box stored in `database/seeders/data/montreal_fallback.geojson`.

---

## Step 6: Build Assets

```bash
./vendor/bin/sail npm run dev
```

---

## Step 7: Create the Admin Account

```bash
./vendor/bin/sail artisan tinker
>>> \App\Models\User::create(['name'=>'Admin','email'=>'admin@nidvite.test','password'=>bcrypt('password'),'role_id'=>1])
```

**Note:** Role ID 1 is `admin` (seeded in Step 5).

---

## Step 8: Configure Reverb (Real-time)

In a separate terminal:

```bash
./vendor/bin/sail artisan reverb:start
```

This starts the WebSocket server for real-time dashboard updates.

---

## Step 9: Start Queue Worker

In a separate terminal:

```bash
./vendor/bin/sail artisan queue:work
```

This processes background jobs: emails, clustering, geocoding, notifications.

---

## Step 10: Verify Everything Works

1. **PWA**: Visit `http://localhost`. You should see the report form.
2. **Dashboard**: Visit `http://localhost/admin`. Log in with credentials from Step 7.
3. **PostGIS**: Submit a test report. The map pin should appear only if the location is within Montreal.
4. **Real-time**: Open dashboard in two browsers. Submit a report from one — the other should show a notification badge.

---

## Troubleshooting

### PostGIS extension fails to create

```bash
# Restart the database container to ensure a clean state
./vendor/bin/sail down
./vendor/bin/sail up -d
./vendor/bin/sail psql -c "CREATE EXTENSION IF NOT EXISTS postgis;"
```

### Permission denied on storage

```bash
./vendor/bin/sail artisan storage:link
./vendor/bin/sail bash -c "chmod -R 775 storage bootstrap/cache"
```

### Queue worker not running (emails not sending)

```bash
# In a separate terminal
./vendor/bin/sail artisan queue:work
```

### Reverb connection refused

```bash
# Ensure Reverb is running
./vendor/bin/sail artisan reverb:start

# Check REVERB_PORT in .env matches
```

---

## Useful Sail Commands

| Command | Purpose |
|---------|---------|
| `./vendor/bin/sail up -d` | Start containers in background |
| `./vendor/bin/sail down` | Stop containers |
| `./vendor/bin/sail artisan ...` | Run Artisan commands |
| `./vendor/bin/sail test` | Run Pest tests |
| `./vendor/bin/sail psql` | Open PostgreSQL CLI |
| `./vendor/bin/sail npm run dev` | Start Vite dev server |
| `./vendor/bin/sail npm run build` | Build for production |
| `./vendor/bin/sail artisan reverb:start` | Start WebSocket server |
| `./vendor/bin/sail artisan queue:work` | Process background jobs |

---

*This guide is a living document. Propose changes via PR if you find a smoother setup path.*
