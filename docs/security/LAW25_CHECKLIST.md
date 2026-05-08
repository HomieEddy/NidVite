# Law 25 Compliance Checklist (Phase 8)

Date: 2026-05-07
Scope: Launch readiness baseline for citizen reporting and tracking.

## Checklist

- [x] Public privacy policy page published in FR/EN
- [x] Public terms of service page published in FR/EN
- [x] Raw IP retention window documented and automated
- [x] Long-term report archival policy documented and automated
- [x] User-facing report tracking identifiers avoid raw UUID exposure
- [ ] DPO/legal final review completed

## Evidence

- Routes: /confidentialite and /conditions
- Retention automation: routes/console.php (retention and archival jobs)
- Tracking ID policy: reports.public_tracking_id and /suivi/{trackingId}

## Open Gaps

- Legal owner sign-off pending on final wording
- Public contact mechanism for privacy requests to be published

## Owner

- Engineering owner: NidVite maintainer
- Compliance partner: pending assignment
