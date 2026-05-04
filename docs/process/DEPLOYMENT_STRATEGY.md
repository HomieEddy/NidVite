# Deployment Strategy: Railway vs Vercel vs Laravel Forge

This document compares deployment platforms for NidVite and provides a definitive recommendation.

---

## Platform Comparison

| Criteria | Railway | Vercel | Laravel Forge |
|----------|---------|--------|---------------|
| **Runtime** | Docker containers (PHP, Node) | Serverless (Node.js only) | VPS (DigitalOcean, AWS, etc.) |
| **Laravel Support** | Excellent (native PHP runtime) | Poor (requires custom PHP runtime) | Excellent (purpose-built for Laravel) |
| **PostGIS** | Native (PostgreSQL + PostGIS add-on) | Not applicable | Manual install on VPS |
| **Queue Workers** | Native (worker processes) | Not applicable | Supervisor config |
| **NextJS Apps** | Supported but not optimized | Excellent (primary use case) | Possible but manual |
| **SSL/Custom Domain** | Automatic | Automatic | Automatic (via Let's Encrypt) |
| **Auto-deploy from Git** | Yes | Yes | Yes |
| **Scaling** | Horizontal (containers) | Serverless (instant) | Vertical (bigger VPS) |
| **Pricing** | $5-20/month (startup) | Free tier generous | $12-40/month (server + Forge fee) |
| **DevOps Overhead** | Minimal | Minimal | Medium (server management) |

---

## Analysis by Platform

### Railway

**Best for:** Laravel + PostgreSQL/PostGIS applications with minimal DevOps overhead.

**Pros:**
- Native Laravel support with zero configuration
- PostgreSQL with PostGIS extension is a one-click add-on
- Queue workers run as separate processes (no Supervisor config needed)
- Automatic deploy previews for PRs
- Environment variables managed via UI
- Generous free tier ($5/month credit)

**Cons:**
- Less control over server configuration compared to VPS
- Cold starts can occur if the container sleeps (mitigated by health checks)
- No built-in NextJS optimization (but can deploy separately)

**NidVite Fit:** **Excellent.** Railway handles everything the PRD requires: PHP runtime, PostGIS, queue workers, email (Resend integration), and automatic deploys.

---

### Vercel

**Best for:** Frontend frameworks (Next.js, React, Vue, Svelte).

**Pros:**
- Optimized for serverless Node.js applications
- Edge network (CDN) for static assets
- Preview deployments per PR
- Free tier is very generous

**Cons:**
- **Not suitable for Laravel.** PHP is not a first-class runtime on Vercel.
- No native PostgreSQL/PostGIS support
- Queue workers require external services (AWS Lambda, etc.)
- File system is ephemeral (local storage lost between invocations)

**NidVite Fit:** **Poor.** While your NextJS apps belong on Vercel, NidVite is a Laravel backend with PostGIS. Porting Laravel to Vercel's serverless model is a major architectural mismatch.

---

### Laravel Forge

**Best for:** Teams needing full server control with Laravel-optimized tooling.

**Pros:**
- Purpose-built for Laravel (automatic Nginx config, PHP-FPM, SSL)
- Supports multiple server providers (DigitalOcean, AWS, Linode)
- Queue workers, scheduler, and cache configured automatically
- Database backups and monitoring built-in
- One-click site cloning and deployment

**Cons:**
- Requires a separate server ($5-20/month) + Forge subscription ($19/month)
- You must manually install and configure PostGIS on the VPS
- More DevOps overhead (server updates, security patches)
- No automatic deploy previews

**NidVite Fit:** **Good for production, overkill for MVP.** Forge shines when you need dedicated resources and long-term stability. For an MVP, Railway's managed approach is faster.

---

## Recommendation

### For NidVite MVP: Railway

**Rationale:**
1. **PostGIS is managed.** No server configuration required.
2. **Queue workers are native.** The clustering job runs without Supervisor setup.
3. **Faster iteration.** Push to `main` → deploy in 60 seconds. No server maintenance.
4. **Cost-effective.** Starts at ~$5/month (within free tier credit for light usage).
5. **Grows with you.** Scale horizontally when traffic increases.

### Deployment Architecture (Railway)

```
┌─────────────────────────────────────────┐
│              Railway Project            │
│                                         │
│  ┌──────────────┐  ┌─────────────────┐  │
│  │   Web App    │  │  Queue Worker   │  │
│  │  (Laravel)   │  │   (Clustering)  │  │
│  └──────────────┘  └─────────────────┘  │
│         │                   │           │
│         └─────────┬─────────┘           │
│                   │                     │
│         ┌─────────▼─────────┐           │
│         │   PostgreSQL +    │           │
│         │     PostGIS       │           │
│         └───────────────────┘           │
│                                         │
└─────────────────────────────────────────┘
```

---

## Hybrid Strategy (If You Have NextJS Apps)

If you want NidVite and your NextJS apps under one deployment strategy:

| Project | Platform | Reason |
|---------|----------|--------|
| NidVite (Laravel) | **Railway** | PHP + PostGIS + queues |
| NextJS App 1 | **Vercel** | Optimized for NextJS |
| NextJS App 2 | **Vercel** | Optimized for NextJS |

**API Communication:** If your NextJS apps need to consume NidVite data, expose a JSON API from Laravel and call it from NextJS via server-side props or API routes.

---

## Migration Path (Railway → Forge)

If NidVite outgrows Railway:

1. **Phase 1 (MVP):** Railway (ease of use, fast deploys)
2. **Phase 2 (Growth):** Stay on Railway, scale containers
3. **Phase 3 (Scale):** Migrate to Laravel Forge + DigitalOcean/AWS when:
   - You need dedicated CPU/RAM
   - PostGIS queries become I/O bound
   - You want multi-region deployment
   - Compliance requires data residency controls

**Migration effort:** Medium. Export PostgreSQL dump, import to Forge-managed server, update DNS. Laravel code is platform-agnostic.

---

## Final Decision Matrix

| If you need... | Choose |
|----------------|--------|
| Fastest path to MVP with PostGIS | **Railway** |
| Zero server maintenance | **Railway** |
| Automatic queue worker management | **Railway** |
| Full server control and optimization | **Laravel Forge** |
| Deploy NextJS alongside Laravel | **Railway + Vercel** (separate) |
| Cheapest possible hosting | **Railway** (free tier) or **Forge + DO** ($24/month) |
| Enterprise compliance (SOC2, HIPAA) | **Laravel Forge + AWS** |

---

## Action Items

- [ ] Create Railway account and project
- [ ] Add PostgreSQL database with PostGIS extension
- [ ] Connect GitHub repo for auto-deploy on `main` push
- [ ] Configure environment variables (Resend, MapTiler, reCAPTCHA)
- [ ] Set predeploy command: `php artisan migrate --force && php artisan opcache:clear`
- [ ] Add health check endpoint (`/health` via Spatie Health)
- [ ] Configure custom domain and SSL

---

*This document is a living record. Re-evaluate when monthly hosting costs exceed $50 or traffic exceeds 10k requests/day.*
