# Staging Demo UAT Go/No-Go Checklist

Date: 2026-05-08
Milestone: v2.0 Phase 14

## 1) Preflight Gate

Run from project root:

```bash
php artisan ops:check-staging-readiness
```

Go criteria:
- Command exits with code 0.
- Queue/cache/filesystem/database/postgis checks pass.
- Mailer is safe for staging (`log` or `array`).

No-go criteria:
- Any failed check output from the command.

## 2) Demo Data Reset

```bash
php artisan ops:seed-staging-demo --fresh
```

Go criteria:
- Command exits with code 0.
- Output contains `Staging demo seed completed.`
- Demo reports are present for map and dashboard checks.

No-go criteria:
- Seed command fails or dataset count is not reproducible.

## 3) UAT Functional Pass

Run and verify:
- Public map page loads and markers render.
- `/api/reports/geojson` returns features.
- Admin dashboard loads repair velocity and neighborhood cost widgets without SQL errors.
- Export actions generate Excel and PDF successfully.

## 4) Rollback Plan

If go/no-go fails:
1. Stop demo and record failure output.
2. Re-run `php artisan ops:check-staging-readiness` after env fix.
3. Re-seed with `php artisan ops:seed-staging-demo --fresh`.
4. If still failing, revert to the last known good staging commit and repeat preflight.

## 5) Sign-off

Record:
- Commit SHA tested
- Operator
- Date/time
- Go/No-go decision
- Follow-up actions (if any)
