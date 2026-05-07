# Environment Setup Guide

> Last updated: 2026-05-06 — Audited against actual codebase

Get NidVite running locally within 15 minutes.

**Prerequisites:** Docker Desktop installed and running.

---

## Step 1: Clone and Install

```bash
git clone git@github.com:your-org/nid-vite.git
cd nid-vite

composer install
npm install
cp .env.example .env
php artisan key:generate
```

---

## Step 2: Start Sail

The `docker-compose.yml` is already configured with PostGIS 15-3.4, Redis, and Mailpit.

```bash
./vendor/bin/sail up -d
```

Wait for containers to be healthy:

```bash
./vendor/bin/sail ps
```

---

## Step 3: Enable PostGIS Extension

```bash
./vendor/bin/sail psql -c "CREATE EXTENSION IF NOT EXISTS postgis;"
```

Verify:

```bash
./vendor/bin/sail artisan tinker
>>> DB::select("SELECT PostGIS_Version()")
```

---

## Step 4: Configure `.env`

Key values (see `.env.example` for full list):

```env
APP_NAME=NidVite
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=nid_vite
DB_USERNAME=sail
DB_PASSWORD=password

QUEUE_CONNECTION=database
CACHE_DRIVER=database

SESSION_SECURE_COOKIE=false
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict

MAIL_MAILER=resend
RESEND_API_KEY=re_xxxxxxxx
MAIL_FROM_ADDRESS="updates@nidvite.ca"
MAIL_FROM_NAME="NidVite"

FILESYSTEM_DISK=local

NOCAPTCHA_SECRET=your-secret-key
NOCAPTCHA_SITEKEY=your-site-key

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

# Seed roles (admin, manager, service_worker, accountant, viewer)
./vendor/bin/sail artisan db:seed --class=RoleSeeder

# Seed report categories (pothole, graffiti, broken_light, sidewalk, other)
./vendor/bin/sail artisan db:seed --class=ReportCategorySeeder

# Seed Montreal boundary polygon for geofencing
./vendor/bin/sail artisan db:seed --class=MontrealBoundarySeeder

# Create admin user (email: admin@nidvite.test, password: password)
./vendor/bin/sail artisan db:seed --class=AdminUserSeeder

# Optional: seed test data for development
./vendor/bin/sail artisan db:seed --class=TestDataSeeder
```

**Note:** `MontrealBoundarySeeder` downloads GeoJSON from Montreal Open Data. If unavailable, it falls back to a simplified bounding box.

**Note:** The `ExpenseCategorySeeder` and `RolePermissionSeeder` referenced in older docs do NOT exist. Expense categories were replaced by the Vendor system. Permissions use simple role-based checks, not a separate permissions table.

---

## Step 6: Build Assets

```bash
./vendor/bin/sail npm run dev
```

---

## Step 7: Start Reverb (Real-time)

In a separate terminal:

```bash
./vendor/bin/sail artisan reverb:start
```

---

## Step 8: Start Queue Worker

In a separate terminal:

```bash
./vendor/bin/sail artisan queue:work
```

Processes background jobs: status change emails, media conversions.

---

## Step 9: Verify Everything Works

1. **PWA**: Visit `http://localhost/signaler` — report form should load
2. **Dashboard**: Visit `http://localhost/admin` — log in with admin@nidvite.test / password
3. **PostGIS**: Submit a test report with a Montreal location — it should pass geofencing
4. **Real-time**: Open dashboard in two browsers — new reports should trigger a notification
5. **Map**: Visit `http://localhost/carte` — public map with report markers

---

## Key URLs

| URL | Purpose |
|-----|---------|
| `/` | Citizen homepage with stats |
| `/signaler` | Report submission form |
| `/suivi/{uuid}` | Track a report by UUID |
| `/carte` | Public map of all reports |
| `/locale/en` or `/locale/fr` | Switch language |
| `/admin` | Filament admin dashboard |
| `/admin/login` | Admin login page |

---

## Troubleshooting

### PostGIS extension fails to create

```bash
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
./vendor/bin/sail artisan queue:work
```

### Reverb connection refused

```bash
./vendor/bin/sail artisan reverb:start
# Check REVERB_PORT in .env matches docker-compose.yml
```

---

## Useful Sail Commands

| Command | Purpose |
|---------|---------|
| `./vendor/bin/sail up -d` | Start containers |
| `./vendor/bin/sail down` | Stop containers |
| `./vendor/bin/sail artisan ...` | Run Artisan commands |
| `./vendor/bin/sail test` | Run Pest tests |
| `./vendor/bin/sail psql` | Open PostgreSQL CLI |
| `./vendor/bin/sail npm run dev` | Start Vite dev server |
| `./vendor/bin/sail npm run build` | Build for production |
| `./vendor/bin/sail artisan reverb:start` | Start WebSocket server |
| `./vendor/bin/sail artisan queue:work` | Process background jobs |

---

*Updated 2026-05-06 — Reflects actual seeder names and project routes.*
