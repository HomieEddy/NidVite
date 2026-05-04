# ADR-002: Pre-Computed PostGIS Clustering

## Status
Accepted

## Context

The PRD requires **Proximity Clustering**: automatic merging of duplicate reports within a 5-meter radius. This prevents the entrepreneur's dashboard from being overwhelmed by multiple pins for the same pothole.

We must decide how to compute these clusters: on-the-fly at query time, or pre-computed and stored.

### Options Considered

1. **On-The-Fly Clustering**: Calculate clusters dynamically using PostGIS `ST_DWithin` every time the dashboard loads.
   - *Pros*: No additional storage. Always reflects the latest state. Simple to implement initially.
   - *Cons*: Performance degrades linearly with report volume. Expensive for the map widget which may query hundreds of reports simultaneously.

2. **Pre-Computed Clustering**: Run a queue job that calculates clusters and stores them in a dedicated `clusters` table. The dashboard reads from this table.
   - *Pros*: O(1) read performance for the dashboard. Scales to thousands of reports. Allows for complex cluster metadata (centroid, report count, bounding box).
   - *Cons*: Requires a queue worker. Clusters are slightly stale between job runs (mitigated by frequent scheduling).

## Decision

We will adopt **Option 2: Pre-Computed Clustering**.

Clusters will be calculated by a Laravel Queue job triggered on a schedule (e.g., every 5 minutes) and whenever a new report is submitted. The results will be stored in a `clusters` table with a PostGIS `geography` centroid column.

## Consequences

- **Positive**: The Filament Map Widget will query the `clusters` table directly, ensuring fast load times regardless of report volume.
- **Positive**: The cluster logic is centralized in an Action class (`ClusterNearbyReports`), making it testable and reusable.
- **Negative**: Requires a queue worker running in production (Railway handles this via predeploy/worker processes).
- **Negative**: Clusters may be up to 5 minutes stale. This is acceptable for the MVP; real-time clustering can be added later if needed.

## Implementation Notes

- The `clusters` table will store: `id`, `centroid_location` (PostGIS `geography(POINT,4326)`), `report_ids` (JSON array of UUIDs), `status` (derived from constituent reports), `created_at`, `updated_at`.
- The clustering job will use PostGIS `ST_ClusterDBSCAN` with `eps := 5` (meters) and `minpoints := 2`.
- Single reports (noise points) will not appear in the `clusters` table; they remain as individual pins.

## Related Decisions

- TECH_STACK.md: Requires PostgreSQL 15+ with PostGIS extension.
- PROJECT_STRUCTURE.md: Clustering logic lives in `app/Actions/Reports/ClusterNearbyReports.php`.
