# Database Schema Overview (Production)

This document defines the production database schema for NidVite, designed for scale, security, and compliance.

---

## Architecture Principles

1. **Partitioning**: `reports` table partitioned by month on `created_at`
2. **Privacy by Design**: IPs hashed after 30 days, automatic data expiration
3. **Read/Write Separation**: Hot data fully indexed, cold data compressed/archived
4. **Audit Everything**: All changes tracked, all abuse logged
5. **Pre-computation**: Aggregated stats updated async to keep dashboard fast

---

## Entity Relationship Diagram (High Level)

```
┌─────────────────────────────────────────────────────────────────────┐
│                         CORE DOMAIN                                  │
│  ┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────────┐  │
│  │  users   │    │ reports  │    │ clusters │    │   media      │  │
│  │(admin)   │    │(partitioned)│  │(pre-comp)│   │(spatie)     │  │
│  └──────────┘    └──────────┘    └──────────┘    └──────────────┘  │
└─────────────────────────────────────────────────────────────────────┘
                              │
┌─────────────────────────────┼───────────────────────────────────────┐
│                         SECURITY & ABUSE                            │
│  ┌────────────┐  ┌──────────────────┐  ┌──────────────────────┐    │
│  │blocked_ips │  │ rate_limit_buckets│  │ suspicious_activity  │    │
│  └────────────┘  └──────────────────┘  └──────────────────────┘    │
│  ┌──────────────────┐  ┌──────────────────────┐                    │
│  │ device_fingerprints│  │ report_archives      │                    │
│  └──────────────────┘  └──────────────────────┘                    │
└─────────────────────────────────────────────────────────────────────┘
                              │
┌─────────────────────────────┼───────────────────────────────────────┐
│                         ANALYTICS & CACHE                           │
│  ┌────────────────┐  ┌────────────────┐  ┌──────────────────┐      │
│  │ hourly_stats   │  │neighborhood_stats│  │ cache_warmers    │      │
│  └────────────────┘  └────────────────┘  └──────────────────┘      │
└─────────────────────────────────────────────────────────────────────┘
                              │
┌─────────────────────────────┼───────────────────────────────────────┐
│                         COMMUNICATIONS                              │
│  ┌──────────────────┐  ┌──────────────────────┐                    │
│  │ email_deliveries │  │ notification_prefs   │                    │
│  └──────────────────┘  └──────────────────────┘                    │
└─────────────────────────────────────────────────────────────────────┘
                              │
┌─────────────────────────────┼───────────────────────────────────────┐
│                         REFERENCE DATA                              │
│  ┌──────────────────┐  ┌──────────────────────┐                    │
│  │ montreal_boundary│  │ activity_log         │                    │
│  └──────────────────┘  └──────────────────────┘                    │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Core Domain Tables

### `users`

The entrepreneur (operator) login table.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | `BIGSERIAL` | PK | |
| `uuid` | `UUID` | UNIQUE, INDEX | Public identifier |
| `name` | `VARCHAR(255)` | NOT NULL | |
| `email` | `VARCHAR(255)` | UNIQUE, NOT NULL | |
| `password` | `VARCHAR(255)` | NOT NULL | Hashed (bcrypt) |
| `role_id` | `SMALLINT` | NOT NULL, FK → roles.id | RBAC role |
| `two_factor_secret` | `TEXT` | NULL | Encrypted TOTP secret |
| `two_factor_recovery_codes` | `TEXT` | NULL | Encrypted backup codes |
| `two_factor_confirmed_at` | `TIMESTAMP` | NULL | When 2FA was enabled |
| `last_login_at` | `TIMESTAMP` | NULL | |
| `locale` | `VARCHAR(5)` | NOT NULL DEFAULT 'fr' | Preferred language: 'en' or 'fr' |
| `is_active` | `BOOLEAN` | NOT NULL DEFAULT true | Soft-disable account |
| `created_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | |
| `updated_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | |

---

### `reports` (Partitioned by Month)

Central entity. Partitioned on `created_at` for fast time-range queries and easy data purging.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | `BIGSERIAL` | PK | |
| `uuid` | `UUID` | UNIQUE, NOT NULL, INDEX | Public tracking identifier |
| `reporter_email` | `VARCHAR(255)` | NOT NULL, INDEX | Normalized (lowercase) |
| `preferred_locale` | `VARCHAR(5)` | NOT NULL DEFAULT 'fr' | Language preference: 'en' or 'fr' |
| `email_verified_at` | `TIMESTAMP` | NULL | If we add email verification later |
| `location` | `GEOGRAPHY(POINT,4326)` | NOT NULL | PostGIS spatial type |
| `location_accuracy` | `FLOAT` | NULL | GPS accuracy in meters |
| `address` | `VARCHAR(500)` | NULL | Reverse geocoded address |
| `neighborhood` | `VARCHAR(100)` | NULL INDEX | For analytics aggregation |
| `borough` | `VARCHAR(100)` | NULL INDEX | Montreal borough |
| `status` | `ENUM('pending','scheduled','in_progress','repaired','rejected')` | NOT NULL DEFAULT 'pending', INDEX | |
| `priority` | `ENUM('low','normal','high','critical')` | NOT NULL DEFAULT 'normal', INDEX | |
| `category` | `ENUM('pothole','graffiti','broken_light','sidewalk','other')` | NOT NULL DEFAULT 'pothole', INDEX | Report type |
| `description` | `TEXT` | NULL | Citizen description |
| `device_fingerprint_id` | `BIGINT` | FK → device_fingerprints.id, INDEX | |
| `ip_address_hash` | `VARCHAR(64)` | NOT NULL, INDEX | SHA-256 hash of IP |
| `ip_address_raw` | `INET` | NULL | Raw IP, purged after 30 days |
| `user_agent_hash` | `VARCHAR(64)` | NOT NULL, INDEX | Hash of UA string |
| `geofence_passed` | `BOOLEAN` | NOT NULL DEFAULT false | |
| `geofence_checked_at` | `TIMESTAMP` | NULL | |
| `submission_duration_ms` | `INT` | NULL | Time to fill form (anti-bot) |
| `is_spam` | `BOOLEAN` | NOT NULL DEFAULT false, INDEX | Flagged by filters |
| `spam_score` | `FLOAT` | NULL | 0.0-1.0 confidence |
| `rejection_reason` | `ENUM('false_report','out_of_scope','duplicate','not_found','insufficient_info')` | NULL | Required when status='rejected' |
| `admin_notes` | `TEXT` | NULL | Entrepreneur notes |
| `first_scheduled_at` | `TIMESTAMP` | NULL | When job was first scheduled |
| `first_started_at` | `TIMESTAMP` | NULL | When work first began |
| `target_completion_at` | `TIMESTAMP` | NULL | SLA target: 72h high, 7d normal, 14d low |
| `completed_at` | `TIMESTAMP` | NULL | When status → 'repaired' |
| `expires_at` | `TIMESTAMP` | NOT NULL DEFAULT (NOW() + INTERVAL '2 years') | GDPR retention |
| `deleted_at` | `TIMESTAMP` | NULL | Soft delete |
| `created_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | Partition key |
| `updated_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | |

**Partition Strategy:**
```sql
-- Create monthly partitions
CREATE TABLE reports_y2024m01 PARTITION OF reports
    FOR VALUES FROM ('2024-01-01') TO ('2024-02-01');
CREATE TABLE reports_y2024m02 PARTITION OF reports
    FOR VALUES FROM ('2024-02-01') TO ('2024-03-01');
-- ... auto-generated by Laravel command each month
```

**Indexes:**
```sql
-- Spatial
CREATE INDEX idx_reports_location_gist ON reports USING GIST(location);

-- Time-series queries
CREATE INDEX idx_reports_created_at ON reports(created_at DESC);
CREATE INDEX idx_reports_status_created ON reports(status, created_at DESC);

-- Email tracking
CREATE INDEX idx_reports_email_created ON reports(reporter_email, created_at DESC);

-- Neighborhood analytics
CREATE INDEX idx_reports_neighborhood ON reports(neighborhood, status);

-- Soft delete filtering
CREATE INDEX idx_reports_deleted_at ON reports(deleted_at) WHERE deleted_at IS NULL;
```

---

### `clusters` (Pre-computed)

Aggregate duplicate reports within 5 meters.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | `BIGSERIAL` | PK | |
| `cluster_uuid` | `UUID` | UNIQUE, NOT NULL | Public identifier |
| `centroid_location` | `GEOGRAPHY(POINT,4326)` | NOT NULL | Center of cluster |
| `boundary` | `GEOGRAPHY(POLYGON,4326)` | NULL | Convex hull of points |
| `geohash` | `VARCHAR(12)` | NOT NULL, INDEX | For fast region queries |
| `report_count` | `INT` | NOT NULL DEFAULT 0 | |
| `report_uuids` | `JSONB` | NOT NULL | Array of report UUIDs |
| `status` | `ENUM('pending','scheduled','in_progress','repaired','mixed')` | NOT NULL DEFAULT 'pending' | |
| `primary_report_uuid` | `UUID` | FK → reports.uuid | Representative report |
| `first_reported_at` | `TIMESTAMP` | NOT NULL | |
| `last_reported_at` | `TIMESTAMP` | NOT NULL | |
| `neighborhood` | `VARCHAR(100)` | NULL, INDEX | |
| `expires_at` | `TIMESTAMP` | NOT NULL | Matches primary report |
| `created_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | |
| `updated_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | |

**Indexes:**
```sql
CREATE INDEX idx_clusters_location ON clusters USING GIST(centroid_location);
CREATE INDEX idx_clusters_geohash ON clusters(geohash);
CREATE INDEX idx_clusters_status ON clusters(status) WHERE status != 'repaired';
```

---

### `media` (Spatie Media Library Extended)

Stores photos with processing metadata.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | `BIGSERIAL` | PK | |
| `model_type` | `VARCHAR(255)` | NOT NULL | `App\Models\Report` |
| `model_id` | `BIGINT` | NOT NULL, INDEX | |
| `uuid` | `UUID` | UNIQUE, NOT NULL | Public access |
| `collection_name` | `VARCHAR(255)` | NOT NULL | `before_photos` / `after_photos` |
| `file_name` | `VARCHAR(255)` | NOT NULL | |
| `disk` | `VARCHAR(255)` | NOT NULL | `local` / `r2` |
| `conversions_disk` | `VARCHAR(255)` | NULL | |
| `mime_type` | `VARCHAR(255)` | NOT NULL | `image/jpeg`, etc. |
| `size` | `BIGINT` | NOT NULL | Bytes |
| `width` | `INT` | NULL | Pixels |
| `height` | `INT` | NULL | Pixels |
| `exif_stripped` | `BOOLEAN` | NOT NULL DEFAULT false | Privacy check |
| `perceptual_hash` | `VARCHAR(64)` | NULL, INDEX | For deduplication |
| `processing_status` | `ENUM('pending','processing','completed','failed')` | NOT NULL DEFAULT 'pending' | |
| `cdn_url` | `VARCHAR(500)` | NULL | Cloudflare cached URL |
| `cdn_cached_at` | `TIMESTAMP` | NULL | |
| `generated_conversions` | `JSONB` | NULL | Track which conversions exist |
| `responsive_images` | `JSONB` | NULL | Srcset definitions |
| `order_column` | `INT` | NULL | Display order |
| `created_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | |
| `updated_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | |

---

## Security & Abuse Prevention Tables

### `device_fingerprints`

Track device reputation for zero-auth abuse prevention.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | `BIGSERIAL` | PK | |
| `fingerprint_hash` | `VARCHAR(64)` | UNIQUE, NOT NULL, INDEX | SHA-256 |
| `trust_score` | `FLOAT` | NOT NULL DEFAULT 0.0 | -1.0 (blocked) to 1.0 (trusted) |
| `first_seen_at` | `TIMESTAMP` | NOT NULL | |
| `last_seen_at` | `TIMESTAMP` | NOT NULL | |
| `submission_count` | `INT` | NOT NULL DEFAULT 0 | |
| `blocked_count` | `INT` | NOT NULL DEFAULT 0 | |
| `is_blocked` | `BOOLEAN` | NOT NULL DEFAULT false | |
| `block_reason` | `VARCHAR(255)` | NULL | |
| `block_expires_at` | `TIMESTAMP` | NULL | Temporary blocks |
| `user_agent` | `VARCHAR(500)` | NULL | Truncated |
| `country` | `VARCHAR(2)` | NULL | GeoIP country code |
| `created_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | |
| `updated_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | |

### `rate_limit_buckets`

Token bucket rate limiting (in-memory Redis preferred, DB fallback).

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | `BIGSERIAL` | PK | |
| `key` | `VARCHAR(255)` | UNIQUE, NOT NULL, INDEX | `ip:{hash}`, `email:{email}`, `device:{hash}` |
| `type` | `ENUM('ip','email','device')` | NOT NULL | |
| `tokens` | `FLOAT` | NOT NULL | Current bucket level |
| `last_refill_at` | `TIMESTAMP` | NOT NULL | |
| `created_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | |
| `updated_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | |

**TTL:** Rows auto-expire after 24 hours.

### `blocked_ips`

IP blacklist with metadata.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | `BIGSERIAL` | PK | |
| `ip_address` | `INET` | NOT NULL | |
| `ip_hash` | `VARCHAR(64)` | NOT NULL, INDEX | For lookup without raw IP |
| `reason` | `VARCHAR(255)` | NOT NULL | `abuse`, `botnet`, `manual` |
| `source` | `VARCHAR(50)` | NOT NULL | `auto`, `manual`, `import` |
| `evidence` | `JSONB` | NULL | Links to suspicious_activity |
| `blocked_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | |
| `expires_at` | `TIMESTAMP` | NULL | NULL = permanent |
| `created_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | |

### `suspicious_activity`

Audit trail for potential abuse.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | `BIGSERIAL` | PK | |
| `activity_type` | `ENUM('rapid_submission','geofence_fail','photo_duplicate','impossible_travel','honeypot_triggered','captcha_fail','rate_limit_exceeded')` | NOT NULL, INDEX | |
| `severity` | `ENUM('low','medium','high','critical')` | NOT NULL | |
| `ip_address_hash` | `VARCHAR(64)` | NOT NULL, INDEX | |
| `device_fingerprint_id` | `BIGINT` | FK → device_fingerprints.id, NULL | |
| `report_id` | `BIGINT` | FK → reports.id, NULL | Related report |
| `email` | `VARCHAR(255)` | NULL | Normalized |
| `details` | `JSONB` | NOT NULL | Context data |
| `resolved_at` | `TIMESTAMP` | NULL | Manual review timestamp |
| `resolved_by` | `BIGINT` | FK → users.id, NULL | Admin who reviewed |
| `created_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | |

---

## Analytics Tables

### `hourly_stats`

Pre-aggregated metrics for fast dashboard queries.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | `BIGSERIAL` | PK | |
| `hour` | `TIMESTAMP` | NOT NULL, UNIQUE | Truncated to hour |
| `total_reports` | `INT` | NOT NULL DEFAULT 0 | |
| `pending_reports` | `INT` | NOT NULL DEFAULT 0 | |
| `repaired_reports` | `INT` | NOT NULL DEFAULT 0 | |
| `rejected_reports` | `INT` | NOT NULL DEFAULT 0 | |
| `unique_emails` | `INT` | NOT NULL DEFAULT 0 | |
| `unique_devices` | `INT` | NOT NULL DEFAULT 0 | |
| `photos_uploaded` | `INT` | NOT NULL DEFAULT 0 | |
| `avg_processing_time_ms` | `INT` | NULL | Report creation time |
| `blocked_submissions` | `INT` | NOT NULL DEFAULT 0 | |
| `created_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | |
| `updated_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | |

### `neighborhood_stats`

Pre-computed neighborhood summaries.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | `BIGSERIAL` | PK | |
| `neighborhood` | `VARCHAR(100)` | NOT NULL, UNIQUE | |
| `borough` | `VARCHAR(100)` | NULL | |
| `total_reports` | `INT` | NOT NULL DEFAULT 0 | |
| `open_reports` | `INT` | NOT NULL DEFAULT 0 | |
| `repaired_this_month` | `INT` | NOT NULL DEFAULT 0 | |
| `avg_resolution_hours` | `FLOAT` | NULL | |
| `last_calculated_at` | `TIMESTAMP` | NOT NULL | |
| `created_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | |
| `updated_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | |

### `daily_stats`

Roll-up of hourly stats for chart queries.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | `BIGSERIAL` | PK | |
| `date` | `DATE` | NOT NULL, UNIQUE | |
| `total_reports` | `INT` | NOT NULL DEFAULT 0 | |
| `pending_reports` | `INT` | NOT NULL DEFAULT 0 | |
| `repaired_reports` | `INT` | NOT NULL DEFAULT 0 | |
| `rejected_reports` | `INT` | NOT NULL DEFAULT 0 | |
| `unique_emails` | `INT` | NOT NULL DEFAULT 0 | |
| `photos_uploaded` | `INT` | NOT NULL DEFAULT 0 | |
| `total_expenses` | `DECIMAL(12,2)` | NOT NULL DEFAULT 0 | |
| `avg_repair_hours` | `FLOAT` | NULL | |
| `created_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | |
| `updated_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | |

### `weekly_stats`

Roll-up of daily stats for trend analysis.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | `BIGSERIAL` | PK | |
| `week_start` | `DATE` | NOT NULL, UNIQUE | Monday of the week |
| `total_reports` | `INT` | NOT NULL DEFAULT 0 | |
| `repaired_reports` | `INT` | NOT NULL DEFAULT 0 | |
| `total_expenses` | `DECIMAL(12,2)` | NOT NULL DEFAULT 0 | |
| `avg_cost_per_repair` | `DECIMAL(10,2)` | NULL | |
| `repair_velocity` | `FLOAT` | NULL | Repairs per day |
| `sla_breach_count` | `INT` | NOT NULL DEFAULT 0 | |
| `material_usage` | `JSONB` | NULL | Top materials used |
| `created_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | |
| `updated_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | |

---

## Notifications

### `notifications`

In-app notifications for admin, manager, and service workers.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | `BIGSERIAL` | PK | |
| `user_id` | `BIGINT` | FK → users.id, INDEX | Recipient |
| `type` | `ENUM('critical_report','low_stock','job_assigned','sla_breach','expense_alert','system')` | NOT NULL, INDEX | |
| `title_fr` | `VARCHAR(255)` | NOT NULL | French title (default) |
| `title_en` | `VARCHAR(255)` | NULL | English title |
| `message_fr` | `TEXT` | NOT NULL | French message (default) |
| `message_en` | `TEXT` | NULL | English message |
| `action_url` | `VARCHAR(500)` | NULL | Link to relevant page |
| `is_read` | `BOOLEAN` | NOT NULL DEFAULT false, INDEX | |
| `read_at` | `TIMESTAMP` | NULL | |
| `created_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | |
| `updated_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | |

---

## Communications Tables

### `email_deliveries`

Track every automated email for reliability.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | `BIGSERIAL` | PK | |
| `report_uuid` | `UUID` | NOT NULL, INDEX | |
| `recipient_email` | `VARCHAR(255)` | NOT NULL | |
| `template` | `VARCHAR(100)` | NOT NULL | `job_completed`, `status_update` |
| `subject_fr` | `VARCHAR(500)` | NOT NULL | French subject |
| `subject_en` | `VARCHAR(500)` | NULL | English subject |
| `body_fr` | `TEXT` | NOT NULL | French body |
| `body_en` | `TEXT` | NULL | English body |
| `locale_sent` | `VARCHAR(5)` | NOT NULL DEFAULT 'fr' | Which language was sent |
| `provider` | `VARCHAR(50)` | NOT NULL DEFAULT 'resend' | |
| `provider_message_id` | `VARCHAR(255)` | NULL | Resend message ID |
| `status` | `ENUM('queued','sent','delivered','bounced','complained','failed')` | NOT NULL DEFAULT 'queued', INDEX | |
| `sent_at` | `TIMESTAMP` | NULL | |
| `delivered_at` | `TIMESTAMP` | NULL | |
| `opened_at` | `TIMESTAMP` | NULL | If tracking enabled |
| `error_message` | `TEXT` | NULL | |
| `retry_count` | `INT` | NOT NULL DEFAULT 0 | |
| `created_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | |
| `updated_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | |

---

## Reference Data Tables

### `montreal_boundary`

Geofencing boundary polygon.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | `SMALLINT` | PK | |
| `name` | `VARCHAR(255)` | NOT NULL | "Montreal Metropolitan Area" |
| `name_fr` | `VARCHAR(255)` | NULL | French name |
| `boundary` | `GEOGRAPHY(MULTIPOLYGON,4326)` | NOT NULL | PostGIS polygon |
| `bounding_box` | `GEOGRAPHY(POLYGON,4326)` | NULL | Simplified bbox for fast checks |
| `area_km2` | `FLOAT` | NULL | Computed area |
| `data_source` | `VARCHAR(255)` | NULL | Open data URL |
| `valid_from` | `DATE` | NOT NULL | Boundary effective date |
| `valid_to` | `DATE` | NULL | NULL = current |
| `created_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | |

### `report_categories`

Lookup table for report types.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | `SMALLINT` | PK | |
| `slug` | `VARCHAR(50)` | UNIQUE, NOT NULL | `pothole`, `graffiti`, etc. |
| `label_en` | `VARCHAR(100)` | NOT NULL | |
| `label_fr` | `VARCHAR(100)` | NULL | |
| `icon` | `VARCHAR(50)` | NULL | Lucide icon name |
| `color` | `VARCHAR(7)` | NULL | Hex color for map pins |
| `is_active` | `BOOLEAN` | NOT NULL DEFAULT true | |
| `sort_order` | `SMALLINT` | NOT NULL DEFAULT 0 | |
| `created_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | |

---

## Job & Expense Management

### `job_materials` (pivot)

Links materials to jobs with planned vs actual usage.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | `BIGSERIAL` | PK | |
| `repair_job_id` | `BIGINT` | FK → repair_jobs.id, INDEX | |
| `material_id` | `BIGINT` | FK → materials.id, INDEX | |
| `quantity_planned` | `FLOAT` | NOT NULL DEFAULT 0 | Estimated needed |
| `quantity_actual` | `FLOAT` | NULL | Actually used |
| `unit_cost_at_time` | `DECIMAL(10,2)` | NULL | Material price when used |
| `created_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | |
| `updated_at` | `TIMESTAMP` | NOT NULL DEFAULT NOW() | |

---

## Data Retention & Privacy

| Table | Retention | Action |
|-------|-----------|--------|
| `reports` | 2 years | Auto-archive to R2, keep stats |
| `reports.ip_address_raw` | 30 days | Hash and nullify |
| `clusters` | Match reports | Cascade delete |
| `media` | Match reports | Delete files + DB row |
| `rate_limit_buckets` | 24 hours | Auto-expire rows |
| `suspicious_activity` | 1 year | Aggregate and archive |
| `email_deliveries` | 1 year | Anonymize email, keep stats |
| `device_fingerprints` | 90 days inactive | Delete or anonymize |
| `hourly_stats` | Indefinite | Aggregated, no PII |

---

## Scaling Checklist

- [ ] Partition management command runs monthly (`artisan reports:partition-create`)
- [ ] Archive job runs nightly (move cold data to R2)
- [ ] Stats refresh job runs hourly (update `hourly_stats`, `neighborhood_stats`)
- [ ] IP purge job runs daily (hash raw IPs older than 30 days)
- [ ] Clustering job runs every 5 minutes (update `clusters`)
- [ ] GIST indexes maintained (REINDEX monthly during low traffic)
- [ ] Connection pooling configured (PgBouncer when > 100 concurrent)
- [ ] Read replica configured for citizen PWA queries

---

*This schema is designed for 100k+ reports. Review quarterly as data volume grows.*
