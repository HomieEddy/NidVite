# ADR-001: Required Email in Zero-Auth Reporting

## Status
Accepted

## Context

The Product Requirement Document (PRD) for NidVite defines a **Zero-Auth Reporting** flow for citizens. Users should not need to create an account to submit a pothole report. However, the PRD also mandates an **Automated Email Loop**: the entrepreneur's dashboard must trigger an email to the original reporter automatically when a job's status changes to "Repaired."

This creates an architectural tension: how do we email a user who has not authenticated and may not have provided any contact information?

### Options Considered

1. **Optional Email**: Collect email as an optional field. If provided, send notifications. If omitted, the user relies solely on the unique tracking URL or browser cookie.
   - *Pros*: Maximizes submission friction reduction.
   - *Cons*: Breaks the "Automated Email Loop" requirement for a significant portion of users. Creates a two-tier experience.

2. **Required Email**: Make email a mandatory field on the report submission form.
   - *Pros*: Guarantees the Automated Email Loop works for 100% of reports. Provides an audit trail.
   - *Cons*: Slightly increases friction compared to purely anonymous submission.

3. **Skip Email for MVP**: Defer the email loop entirely and rely on the unique tracking URL + optional PWA push notifications.
   - *Pros*: Fastest path to MVP.
   - *Cons*: Explicitly fails a core PRD requirement.

## Decision

We will adopt **Option 2: Required Email**.

While Zero-Auth eliminates password/account creation friction, the email field will be a **single, required input** on the report form. This is a conscious tradeoff: we accept a marginal increase in form friction to guarantee the Automated Email Loop and provide a reliable communication channel.

## Consequences

- **Positive**: The `reports` table will always have a valid `reporter_email`, ensuring the entrepreneur dashboard can trigger completion emails without conditional logic.
- **Positive**: The unique tracking URL remains as a fallback, but email becomes the primary notification channel.
- **Negative**: A user without an email address (or unwilling to provide one) cannot submit a report. We accept this edge case for the MVP.
- **Risk**: We must validate email format and protect against throwaway addresses. This is mitigated by the rate limiting and anti-spam strategy defined in ADR-003.

## Related Decisions

- ADR-003 (Anti-Spam Strategy): Rate limiting must apply per email address and per device fingerprint to prevent abuse of the required email field.
