# ADR-005: Production Database Design for Scale

## Status
Accepted

## Context

The initial schema was designed for MVP functionality. However, NidVite must handle:
- **Zero-auth abuse**: No user accounts means no natural rate limiting barrier
- **High write volume**: Every pothole is a report + photos + geospatial indexing
- **Geospatial queries**: 5-meter clustering and proximity searches are expensive at scale
- **Data retention**: Quebec Law 25 + GDPR require controlled data lifecycle
- **Read scaling**: Entrepreneur dashboard queries must remain fast with 100k+ reports

## Decision

We will implement a **multi-layered database strategy**:

### 1. Table Partitioning

`reports` table is partitioned **by month** on `created_at`:
- Enables fast purging of old data (DROP PARTITION vs DELETE)
- Query planner only scans relevant partitions for time-range queries
- Monthly rotation aligns with data retention policy

```sql
CREATE TABLE reports (
    id BIGSERIAL,
    uuid UUID NOT NULL,
    reporter_email VARCHAR(255) NOT NULL,
    location GEOGRAPHY(POINT,4326) NOT NULL,
    status report_status NOT NULL DEFAULT 'pending',
    priority report_priority NOT NULL DEFAULT 'normal',
    -- ... other columns
    created_at TIMESTAMP NOT NULL,
    expires_at TIMESTAMP NOT NULL DEFAULT (NOW() + INTERVAL '2 years')
) PARTITION BY RANGE (created_at);
```

### 2. Sharding Strategy (Future)

For Phase 2 (100k+ reports), implement **geo-hash sharding**:
- Reports sharded by geohash prefix (e.g., first 4 chars = ~20km box)
- Enables regional query isolation
- Montreal fits in ~4-6 shards

### 3. Read Replicas

- Primary DB: Writes + real-time entrepreneur queries
- Replica 1: Citizen PWA reads (tracking, public map data)
- Replica 2: Analytics and reporting queries

### 4. Hot/Cold Data Separation

- **Hot** (last 90 days): Primary partitions, fully indexed
- **Warm** (90 days - 2 years): Compressed partitions, GIST index only
- **Cold** (> 2 years): Archived to object storage (R2) + aggregated stats retained

## Tables Added for Scale

| Table | Purpose |
|-------|---------|
| `rate_limit_buckets` | Token bucket rate limiting per IP/device/email |
| `blocked_ips` | IP addresses temporarily or permanently blocked |
| `suspicious_activity` | Audit log of potential abuse (failed geofencing, rapid submissions) |
| `email_deliveries` | Delivery status tracking for automated emails |
| `hourly_stats` | Pre-aggregated metrics for dashboard performance |
| `neighborhood_stats` | Pre-computed neighborhood report counts |
| `report_archives` | Metadata for cold storage in R2 |
| `cache_warmers` | Tracks precomputed query caches |

## Consequences

- **Positive**: Can handle 10k+ reports/day with sub-100ms dashboard queries
- **Positive**: GDPR deletion is fast (DROP PARTITION or update expires_at)
- **Negative**: More complex migration strategy (partition management)
- **Negative**: Requires monitoring partition sizes

## Related Decisions

- ADR-006 (Security Hardening): Rate limiting tables support zero-auth abuse prevention
- SCHEMA_OVERVIEW.md: Full entity diagram with all production tables
