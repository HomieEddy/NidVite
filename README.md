# NidVite

Frictionless pothole reporting bridge for Montreal citizens and repair entrepreneurs.

## About

NidVite is a bilingual (FR/EN) web application that lets Montreal citizens report infrastructure issues (potholes, graffiti, broken lights, sidewalk damage) without creating an account, and gives repair entrepreneurs a Filament dashboard to manage, track, and repair those reports.

## Current Status

- v2.3 is complete and shipped.
- Citizen reporting and tracking flow is stable in production.
- Security hardening and regression coverage for critical paths are in place.
- The project is ready for the next milestone scope.

## Tech Stack

- **Backend:** Laravel 11 on PHP 8.2+
- **Database:** PostgreSQL 15 with PostGIS 3.4
- **Admin:** Filament v5 with RBAC and 2FA
- **Frontend:** Livewire 3, Alpine.js, Tailwind CSS
- **Real-time:** Laravel Reverb
- **Authentication:** Laravel Fortify (TOTP and passkeys)
- **Email:** Resend
- **PWA:** Service worker and manifest via laravelpwa
- **Testing:** Pest 2, PHPStan Level 5, Laravel Pint
- **Deployment:** Railway

## Key Features

- **Zero-login reporting:** Citizens submit a report with only an email.
- **Montreal geofencing:** Reports are accepted only inside city boundaries.
- **Photo evidence:** Up to 5 photos per report, with EXIF metadata removed.
- **Status workflow:** received -> verified -> scheduled -> in_progress -> repaired/rejected.
- **Live operations:** New reports appear in the admin dashboard in real time.
- **Bilingual experience:** French-first interface with an English toggle.
- **Installable PWA:** Mobile-friendly with offline fallback support.
- **Public transparency:** Map view with report markers and status color coding.
- **Repair operations:** Jobs, materials, vendors, and GST/QST expense tracking.

## The Happy Path

1. A citizen opens `/signaler` on mobile or web.
2. They choose an issue type, add a short description, location, and optional photos.
3. NidVite validates the location (must be inside Montreal) and creates the report.
4. The citizen immediately gets a tracking link: `/suivi/{uuid}`.
5. The repair team sees the new report in the admin dashboard.
6. The report moves through statuses: `received` -> `verified` -> `scheduled` -> `in_progress` -> `repaired` (or `rejected` with reason).
7. The citizen receives localized status emails and can check progress anytime from the tracking page.

In short: report in under 30 seconds, then follow progress by link until repair is complete.

---

## Architecture Decisions

### Security

- Admin access is hardened with Fortify, mandatory 2FA, and optional passkeys.
- Role-based permissions are enforced across the Filament admin (Admin, Manager, Service Worker, Accountant, Viewer).
- Admin and citizen contexts are isolated: no shared authentication surface.

### Bot & Spam Protection (Layered)

- Honeypot and reCAPTCHA reduce automated report spam with minimal user friction.
- Server-side geofencing accepts only Montreal coordinates.
- Uploaded photos are sanitized (EXIF removed) before storage.

### Data Integrity

- Status changes are constrained by a strict state machine.
- Public tracking uses UUID links to prevent report ID guessing.
- Activity logs and soft deletes provide traceability and safer operations.

### Regional Law Compliance (Quebec / Canada)

- Privacy is minimized by design: citizen reports require only essential data.
- French is the default language, with English available as a secondary option.
- Expense tracking supports Quebec GST and QST tax requirements.
- Geofencing data is sourced from Montreal open data under attribution terms.

---

## Documentation

- [Setup Guide](docs/SETUP_GUIDE.md)
- [Tech Stack](docs/engineering/TECH_STACK.md)
- [Project Structure](docs/engineering/PROJECT_STRUCTURE.md)
- [Database Schema](docs/database/SCHEMA_OVERVIEW.md)
- [Coding Manifesto](docs/engineering/CODING_MANIFESTO.md)
- [Integration Specs](docs/integrations/INTEGRATION_SPECS.md)
- [Security & Privacy](docs/security/SECURITY_PRIVACY.md)
- [Monitoring](docs/engineering/MONITORING_PACKAGES.md)
- [Railway Runtime (Phase 5)](docs/process/RAILWAY_PHASE5_RUNTIME.md)
- [Roadmap](.planning/ROADMAP.md)

## License

Proprietary. All rights reserved.
