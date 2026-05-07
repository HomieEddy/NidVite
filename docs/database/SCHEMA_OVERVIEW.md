# Database Schema Overview

> Last updated: 2026-05-06 — Audited against actual migrations

---

## Architecture Principles

1. **Privacy by Design**: Anti-spam fields removed from reports; IP/device tracking deferred
2. **PostGIS Spatial**: Geography columns on `reports` and `montreal_boundary` with GIST indexes
3. **Audit Trail**: Spatie ActivityLog tracks Report status/priority/notes changes
4. **Soft Deletes**: Report model uses SoftDeletes
5. **Pre-computation**: Stats and clustering tables planned but not yet implemented

---

## Entity Relationship Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                         CORE DOMAIN                                  │
│  ┌──────────┐  ┌──────────┐  ┌───────────────┐  ┌──────────────┐  │
│  │  users   │  │ reports  │  │report_categories│  │    media     │  │
│  │(admin)   │  │          │  │               │  │(spatie)      │  │
│  └──────────┘  └──────────┘  └───────────────┘  └──────────────┘  │
│  ┌──────────┐  ┌──────────┐  ┌──────────────┐  ┌──────────────┐   │
│  │  roles   │  │repair_jobs│ │montreal_boundary│ │   passkeys   │  │
│  └──────────┘  └──────────┘  └──────────────┘  └──────────────┘  │
└─────────────────────────────────────────────────────────────────────┘
                               │
┌─────────────────────────────┼───────────────────────────────────────┐
│                      JOB & EXPENSE DOMAIN                           │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────────┐   │
│  │ expenses │  │ materials│  │ vendors  │  │material_purchases│   │
│  └──────────┘  └──────────┘  └──────────┘  └──────────────────┘   │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐             │
│  │ job_reports  │  │ job_workers  │  │ job_materials │             │
│  │  (pivot)     │  │  (pivot)     │  │  (pivot)      │             │
│  └──────────────┘  └──────────────┘  └──────────────┘             │
└─────────────────────────────────────────────────────────────────────┘
                               │
┌─────────────────────────────┼───────────────────────────────────────┐
│                      INFRASTRUCTURE (EXISTING)                      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐      │
│  │ activity_log │  │telescope_   │  │  Laravel default      │      │
│  │  (spatie)    │  │entries      │  │  (sessions, cache,    │      │
│  └──────────────┘  └──────────────┘  │   jobs, failed_jobs)  │      │
│                                      └──────────────────────┘      │
└─────────────────────────────────────────────────────────────────────┘
                               │
┌─────────────────────────────┼───────────────────────────────────────┐
│                      PLANNED (NOT YET MIGRATED)                    │
│  ┌────────────┐  ┌──────────────────┐  ┌──────────────────────┐    │
│  │ permissions│  │role_permissions  │  │  admin_sessions      │    │
│  └────────────┘  └──────────────────┘  └──────────────────────┘    │
│  ┌────────────────┐  ┌──────────────┐  ┌──────────────────┐       │
│  │admin_audit_log │  │  clusters    │  │device_fingerprints│       │
│  └────────────────┘  └──────────────┘  └──────────────────┘       │
│  ┌────────────────┐  ┌──────────────┐  ┌──────────────────┐       │
│  │rate_limit_     │  │ blocked_ips │  │suspicious_       │       │
│  │buckets         │  │             │  │activity          │       │
│  └────────────────┘  └──────────────┘  └──────────────────┘       │
│  ┌──────────────────┐  ┌──────────────────────────────────┐      │
│  │ email_deliveries │  │ hourly/daily/weekly/neighborhood │      │
│  │                  │  │ _stats                           │      │
│  └──────────────────┘  └──────────────────────────────────────────┘      │
│  ┌──────────────┐                                                   │
│  │notifications │                                                   │
│  └──────────────┘                                                   │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Core Domain Tables (IMPLEMENTED)

### `users`

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | `BIGSERIAL` | PK | |
| `uuid` | `UUID` | UNIQUE | Auto-generated |
| `name` | `VARCHAR(255)` | NOT NULL | |
| `email` | `VARCHAR(255)` | UNIQUE, NOT NULL | |
| `password` | `VARCHAR(255)` | NOT NULL | Bcrypt hash |
| `role_id` | `BIGINT` | FK → roles.id | RBAC role |
| `two_factor_secret` | `TEXT` | NULL | TOTP secret |
| `two_factor_recovery_codes` | `TEXT` | NULL | Backup codes |
| `two_factor_confirmed_at` | `TIMESTAMP` | NULL | |
| `last_login_at` | `TIMESTAMP` | NULL | |
| `locale` | `VARCHAR(5)` | DEFAULT 'fr' | 'en' or 'fr' |
| `is_active` | `BOOLEAN` | DEFAULT true | |
| `remember_token` | `VARCHAR(100)` | NULL | |
| `created_at` / `updated_at` | `TIMESTAMP` | | |

---

### `roles`

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | `BIGSERIAL` | PK | |
| `slug` | `VARCHAR(255)` | UNIQUE | admin, manager, service_worker, accountant, viewer |
| `label_en` | `VARCHAR(255)` | NOT NULL | |
| `label_fr` | `VARCHAR(255)` | | |
| `sort_order` | `INT` | DEFAULT 0 | |

---

### `reports`

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | `BIGSERIAL` | PK | |
| `uuid` | `UUID` | UNIQUE, NOT NULL | Auto-generated, public tracking ID |
| `reporter_email` | `VARCHAR(255)` | NULLABLE | Citizen email (made nullable) |
| `preferred_locale` | `VARCHAR(5)` | DEFAULT 'fr' | FR/EN preference |
| `location` | `GEOGRAPHY(POINT,4326)` | NULLABLE | PostGIS (made nullable for edge cases) |
| `address` | `VARCHAR(500)` | | Reverse geocoded |
| `neighborhood` | `VARCHAR(100)` | INDEXED | For analytics |
| `borough` | `VARCHAR(100)` | INDEXED | Montreal borough |
| `status` | `VARCHAR(20)` | DEFAULT 'received', CHECK constraint | States: received, verified, scheduled, in_progress, repaired, rejected |
| `priority` | `VARCHAR(20)` | DEFAULT 'normal' | low, normal, high, critical |
| `category_id` | `BIGINT` | FK → report_categories.id | |
| `description` | `TEXT` | | Citizen description |
| `rejection_reason` | `VARCHAR(255)` | | Required when status=rejected |
| `admin_notes` | `TEXT` | | Entrepreneur notes |
| `first_scheduled_at` | `TIMESTAMP` | | |
| `first_started_at` | `TIMESTAMP` | | |
| `target_completion_at` | `TIMESTAMP` | | SLA target |
| `completed_at` | `TIMESTAMP` | | When status→repaired |
| `deleted_at` | `TIMESTAMP` | | Soft delete |
| `created_at` / `updated_at` | `TIMESTAMP` | | |

**Note:** Several fields from the original design were removed in cleanup migrations (2026-05-06): `ip_address_hash`, `ip_address_raw`, `user_agent_hash`, `submission_duration_ms`, `spam_score`, `geofence_checked_at`, `email_verified_at`, `location_accuracy`. These are deferred to the security/abuse-prevention phase.

**Indexes:**
- GIST on `location`
- B-tree on `status`, `neighborhood`, `borough`, `deleted_at`

**Status State Machine:**
```
received → verified | rejected
verified → scheduled | rejected
scheduled → in_progress | rejected
in_progress → repaired | rejected
repaired → (terminal)
rejected → (terminal)
```

---

### `report_categories`

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | `BIGSERIAL` | PK | |
| `slug` | `VARCHAR(50)` | UNIQUE | pothole, graffiti, broken_light, sidewalk, other |
| `label_en` | `VARCHAR(100)` | NOT NULL | |
| `label_fr` | `VARCHAR(100)` | | |
| `icon` | `VARCHAR(50)` | | Lucide icon name |
| `color` | `VARCHAR(7)` | | Hex color for map pins |
| `is_active` | `BOOLEAN` | DEFAULT true | |
| `sort_order` | `INT` | DEFAULT 0 | |
| `created_at` | `TIMESTAMP` | | |

---

### `montreal_boundary`

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | `SMALLINT` | PK | Single row |
| `name` | `VARCHAR(255)` | | |
| `boundary` | `GEOGRAPHY(MULTIPOLYGON,4326)` | NOT NULL | PostGIS polygon |
| `created_at` | `TIMESTAMP` | | |

---

### `passkeys`

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | `BIGSERIAL` | PK | |
| `user_id` | `BIGINT` | FK → users.id | |
| `credential_id` | `TEXT` | | WebAuthn credential |
| `public_key` | `TEXT` | | |
| `aaguid` | `TEXT` | | Authenticator GUID |
| `transports` | `JSON` | | |
| `attestation_format` | `VARCHAR(255)` | | |
| `counter` | `BIGINT` | | |
| `name` | `VARCHAR(255)` | | User-given name |
| `last_used_at` | `TIMESTAMP` | | |
| `created_at` / `updated_at` | `TIMESTAMP` | | |

---

## Job & Expense Domain Tables (IMPLEMENTED)

### `repair_jobs`

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | `BIGSERIAL` | PK | |
| `uuid` | `UUID` | UNIQUE | Auto-generated |
| `title` | `VARCHAR(255)` | | |
| `description` | `TEXT` | | |
| `status` | `VARCHAR(20)` | | planned, in_progress, completed, cancelled |
| `priority` | `VARCHAR(20)` | | |
| `scheduled_at` | `TIMESTAMP` | | |
| `started_at` | `TIMESTAMP` | | |
| `completed_at` | `TIMESTAMP` | | |
| `creator_id` | `BIGINT` | FK → users.id | |
| `created_at` / `updated_at` | `TIMESTAMP` | | |

---

### `expenses`

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | `BIGSERIAL` | PK | |
| `repair_job_id` | `BIGINT` | FK → repair_jobs.id | |
| `material_id` | `BIGINT` | FK → materials.id, NULLABLE | |
| `vendor_id` | `BIGINT` | FK → vendors.id, NULLABLE | |
| `creator_id` | `BIGINT` | FK → users.id | |
| `description` | `VARCHAR(255)` | | |
| `amount` | `DECIMAL(10,2)` | | Pre-tax amount |
| `gst` | `DECIMAL(10,2)` | | GST (5%) |
| `qst` | `DECIMAL(10,2)` | | QST (9.975%) |
| `total` | `DECIMAL(10,2)` | | Amount + GST + QST |
| `expense_date` | `DATE` | | |
| `created_at` / `updated_at` | `TIMESTAMP` | | |

**Note:** The `expense_categories` table was dropped in a migration. Categories were replaced by the vendor system.

---

### `vendors`

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | `BIGSERIAL` | PK | |
| `name` | `VARCHAR(255)` | NOT NULL | |
| `contact_name` | `VARCHAR(255)` | | |
| `email` | `VARCHAR(255)` | | |
| `phone` | `VARCHAR(255)` | | |
| `address` | `TEXT` | | |
| `website` | `VARCHAR(255)` | | |
| `notes` | `TEXT` | | |
| `is_active` | `BOOLEAN` | DEFAULT true | |
| `created_at` / `updated_at` | `TIMESTAMP` | | |

---

### `materials`

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | `BIGSERIAL` | PK | |
| `name` | `VARCHAR(255)` | | |
| `unit` | `VARCHAR(50)` | | e.g., "kg", "bag", "m" |
| `unit_cost` | `DECIMAL(10,2)` | | |
| `current_stock` | `DECIMAL(10,2)` | DEFAULT 0 | |
| `reserved_stock` | `DECIMAL(10,2)` | DEFAULT 0 | For in-progress jobs |
| `min_stock_alert` | `DECIMAL(10,2)` | DEFAULT 0 | Low-stock threshold |
| `is_active` | `BOOLEAN` | DEFAULT true | |
| `created_at` / `updated_at` | `TIMESTAMP` | | |

---

### `material_purchases`

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | `BIGSERIAL` | PK | |
| `material_id` | `BIGINT` | FK → materials.id | |
| `creator_id` | `BIGINT` | FK → users.id | |
| `quantity` | `DECIMAL(10,2)` | | |
| `unit_cost` | `DECIMAL(10,2)` | | Price at time of purchase |
| `total_cost` | `DECIMAL(10,2)` | | |
| `purchased_at` | `DATE` | | |
| `notes` | `TEXT` | | |
| `created_at` / `updated_at` | `TIMESTAMP` | | |

---

### Pivot Tables

**`job_reports`** (repair_job_id FK, report_id FK, cost_allocation_percentage)

**`job_workers`** (repair_job_id FK, user_id FK, role_in_job, hours_worked)

**`job_materials`** (repair_job_id FK, material_id FK, quantity_planned, quantity_actual, unit_cost_at_time)

---

## Infrastructure Tables (IMPLEMENTED)

| Table | Source | Notes |
|-------|--------|-------|
| `media` | Spatie Media Library | Report photos in `report-photos` collection |
| `activity_log` | Spatie ActivityLog | Tracks Report status/priority/notes/rejection changes |
| `telescope_entries` | Laravel Telescope | Dev-only, disabled in production |
| `sessions` | Laravel default | |
| `cache` / `cache_locks` | Laravel default | |
| `jobs` / `job_batches` / `failed_jobs` | Laravel default | Queue driver: database |

---

## Planned Tables (NOT YET MIGRATED)

These tables are designed but not yet implemented. See the original schema design for column details.

### Security & Abuse
- `permissions` — Fine-grained permission table
- `role_permissions` — Permission-role pivot
- `admin_sessions` — Admin session tracking and timeout
- `admin_audit_log` — Separate audit trail for admin actions
- `device_fingerprints` — Device reputation tracking
- `rate_limit_buckets` — Token bucket rate limiting
- `blocked_ips` — IP blacklist
- `suspicious_activity` — Abuse audit trail

### Analytics & Pre-computation
- `clusters` — Pre-computed report clusters (5m proximity)
- `hourly_stats` — Pre-aggregated hourly metrics
- `daily_stats` — Daily roll-ups
- `weekly_stats` — Weekly trend data
- `neighborhood_stats` — Per-neighborhood summaries

### Communications
- `email_deliveries` — Track every automated email
- `notifications` — In-app notification system

---

## Data Retention & Privacy (PLANNED)

| Table | Retention | Action |
|-------|-----------|--------|
| `reports` | 2 years | Auto-archive to R2, keep stats |
| `media` | Match reports | Delete files + DB row |
| `suspicious_activity` | 1 year | Aggregate and archive |
| `email_deliveries` | 1 year | Anonymize email, keep stats |
| `device_fingerprints` | 90 days inactive | Delete or anonymize |
| `hourly_stats` / `daily_stats` | Indefinite | Aggregated, no PII |

---

## Scaling Checklist (NOT YET IMPLEMENTED)

- [ ] Partition management command (`artisan reports:partition-create`)
- [ ] Archive job (move cold data to R2)
- [ ] Stats refresh job (hourly_stats, neighborhood_stats)
- [ ] IP purge job (hash raw IPs older than 30 days)
- [ ] Clustering job (every 5 min, update `clusters`)
- [ ] GIST index maintenance (REINDEX monthly)
- [ ] Connection pooling (PgBouncer at >100 concurrent)
- [ ] Read replica for citizen PWA queries

---

*Updated 2026-05-06 — Reflects actual migration state. Planned tables retained for reference.*
