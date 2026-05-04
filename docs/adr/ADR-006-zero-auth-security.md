# ADR-006: Zero-Auth Security & Abuse Prevention

## Status
Accepted

## Context

NidVite allows anyone to submit reports without authentication. This creates unique security challenges:
- **Spam floods**: Botnets can submit thousands of fake reports
- **Storage abuse**: Malicious users can upload huge photos to exhaust storage
- **Privacy invasion**: Bad actors could track specific locations via repeated submissions
- **Geofence bypass**: GPS spoofing to submit reports outside Montreal
- **Email harassment**: Using other people's emails to spam them with "repaired" notifications

## Decision

Implement **defense in depth** with 5 layers:

### Layer 1: Network (IP-based)

- `blocked_ips` table with automatic and manual entries
- CIDR range blocking for known datacenter IPs (AWS, DigitalOcean) that submit reports
- IP reputation check via free tier of AbuseIPDB or similar

### Layer 2: Device Fingerprinting

- Hash of: User-Agent + Canvas fingerprint + WebGL fingerprint + fonts
- Stored in `device_fingerprints` table with trust score
- New fingerprints start with "untrusted" score and gain trust over time
- Rate limits are stricter for untrusted fingerprints

### Layer 3: Behavioral Analysis

- `suspicious_activity` table tracks patterns:
  - > 3 submissions in 1 hour from same IP
  - GPS coordinates that jump impossible distances (> 100km in minutes)
  - Photos with identical file hashes (spam uploads)
  - Reports outside Montreal geofence
- Automated temporary blocks trigger at threshold
- Manual review queue for borderline cases

### Layer 4: Resource Limits

- Max 3 photos per report, 5MB each
- Max 3 reports per email per hour
- Photo deduplication via perceptual hash (phash)
- Storage quota per IP (soft limit: 50MB/day)

### Layer 5: Honeypot + reCAPTCHA

- Honeypot field (ADR-003)
- reCAPTCHA v2 Invisible (ADR-003)
- Time-based token: form must take > 3 seconds to complete (bots are instant)

## Data Retention for Security Data

| Data Type | Retention | Reason |
|-----------|-----------|--------|
| Raw IP address | 30 days | Abuse investigation, then hashed |
| Device fingerprint | 90 days | Trust score calculation |
| Suspicious activity logs | 1 year | Pattern analysis and legal compliance |
| Rate limit buckets | 1 hour | Rolling window |
| Blocked IPs | Permanent (if manual) / 24h (if auto) | Security |

## Consequences

- **Positive**: Can withstand basic DDoS and spam attacks
- **Positive**: Privacy-preserving (IPs are hashed after 30 days)
- **Negative**: False positives may block legitimate users
- **Negative**: Device fingerprinting requires client-side JavaScript (PWA dependency)

## Related Decisions

- SECURITY_PRIVACY.md: Full privacy controls and Quebec Law 25 compliance
- ADR-005: Rate limiting tables in database design
