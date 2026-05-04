# ADR-003: Anti-Spam Strategy (Honeypot + reCAPTCHA v2 Invisible)

## Status
Accepted

## Context

The PRD mandates an **Anti-Spam Shield** with two layers: invisible bot protection and device-based rate limiting. Since the reporting form is public-facing with no authentication, it is a high-value target for automated abuse.

We must select specific tools for bot protection.

### Options Considered

1. **Honeypot Only**: A hidden form field that humans ignore but bots fill in.
   - *Pros*: Zero user friction. No external dependencies.
   - *Cons*: Only blocks basic bots. Easily bypassed by sophisticated scrapers.

2. **reCAPTCHA v3**: Invisible scoring (0.0–1.0) with no user interaction.
   - *Pros*: Completely frictionless. Google's risk analysis engine.
   - *Cons*: Requires interpreting an opaque score threshold. Can block legitimate users on mobile or with aggressive privacy settings. Privacy concerns (Google data collection).

3. **reCAPTCHA v2 Invisible**: "I'm not a robot" badge that auto-verifies in the background, but falls back to a challenge if suspicious.
   - *Pros*: Well-understood behavior. Good balance of security and UX. Easier to test than v3.
   - *Cons*: Slightly more user-visible than v3 (badge appears). Still a Google dependency.

4. **Cloudflare Turnstile**: Invisible, privacy-respecting alternative to reCAPTCHA.
   - *Pros*: Privacy-first. No visual challenge.
   - *Cons*: Requires Cloudflare account. Less documentation in the Laravel ecosystem.

## Decision

We will adopt a **two-layer approach**:

- **Layer 1 (First Line)**: `spatie/laravel-honeypot` — blocks basic bots with zero friction.
- **Layer 2 (Second Line)**: `anhskohbo/no-captcha` (reCAPTCHA v2 Invisible) — blocks sophisticated bots.

**Rationale**: For the MVP phase, reCAPTCHA v2 Invisible is easier to reason about and test than v3's score-based system. The honeypot catches low-hanging fruit without any external API call.

## Consequences

- **Positive**: Defense in depth. A bot must bypass both the honeypot and Google's risk analysis.
- **Positive**: The honeypot field is completely invisible to users; reCAPTCHA v2 Invisible only shows a small badge.
- **Negative**: Hard dependency on Google's reCAPTCHA service. If Google changes pricing or terms, we must migrate.
- **Negative**: reCAPTCHA v2 requires a site key and secret key managed via the Google reCAPTCHA admin console.

## Implementation Notes

- The honeypot field must be rendered in the Livewire `ReportForm` component but hidden via CSS (`display: none`).
- reCAPTCHA v2 Invisible will be bound to the form submit button. On challenge failure, the user sees a visual test.
- Rate limiting (IP-based + device fingerprint) operates as a third layer behind these two; see SECURITY_PRIVACY.md.

## Related Decisions

- SECURITY_PRIVACY.md: Defines rate limiting strategy and device fingerprinting.
- INTEGRATION_SPECS.md: Contains reCAPTCHA site/secret key configuration.
