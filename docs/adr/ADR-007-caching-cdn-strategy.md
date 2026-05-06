# ADR-007: Caching & CDN Strategy

## Status
Accepted

## Context

With zero-auth access and public map data, NidVite faces unique caching challenges:
- Map data is read-heavy but changes frequently (new reports, status updates)
- Citizen "Track Report" pages are personal but static after submission
- Entrepreneur dashboard needs near-real-time data
- Photos must be served quickly to both mobile PWA and dashboard

## Decision

Implement **3-tier caching**:

### Tier 1: Application Cache (Laravel Cache + Redis)

**When Phase 2 introduces Redis:**
- Cluster query results cached for 5 minutes
- Neighborhood stats cached for 1 hour
- Rate limit buckets (must be fast, in-memory)
- Session data for Filament auth

**MVP (Database cache driver):**
- Response cache for read-heavy endpoints (`spatie/laravel-response-cache`)
- Config cache and route cache on deploy

### Tier 2: CDN (Cloudflare)

- Static assets (CSS, JS, PWA manifest)
- Photo thumbnails (long cache: 1 year with cache-busting hash)
- Full-size photos (short cache: 1 hour, stale-while-revalidate)
- Map tile proxy (if we serve tiles through our domain)

### Tier 3: Edge Compute (Cloudflare Workers / Vercel Edge)

- Geofencing check at edge (validate lat/lng before hitting origin)
- Rate limiting at edge (faster than origin, reduces load)
- DDoS protection (Cloudflare built-in)

## Cache Invalidation Strategy

| Cache Type | Invalidation Trigger |
|------------|---------------------|
| Report status page | Status change event |
| Cluster data | Clustering job completion |
| Photo thumbnails | New upload (versioned URL) |
| Neighborhood stats | Hourly cron job |
| Static assets | Build hash in filename |

## Consequences

- **Positive**: Reduces origin load by ~70% for read-heavy traffic
- **Positive**: PWA loads faster for returning users
- **Negative**: Cache invalidation complexity (must clear multiple tiers)
- **Negative**: CDN cost at scale (mitigated by R2 zero egress)

## Related Decisions

- TECH_STACK.md: Includes `spatie/laravel-response-cache`
- DEPLOYMENT_STRATEGY.md: Cloudflare as CDN layer
