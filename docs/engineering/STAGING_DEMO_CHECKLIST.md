# Staging Demo Checklist

Use this checklist before any staging demo run.

## Preconditions

- Deploy is complete on Railway staging services.
- Queue worker and scheduler services are running.
- Staging environment is configured to avoid outbound mail delivery.

## Readiness Command

Run:

```bash
php artisan ops:check-staging-readiness
```

Expected outcome:
- Exit code `0`
- Output includes `Staging readiness check passed.`

If the command fails, resolve all reported issues before demo rehearsal.

## Railway Script Shortcut

Run:

```bash
sh deploy/railway/staging-readiness.sh
```

This executes the same guard command in fail-fast mode.

## Post-Check Actions

- Run smoke test on critical admin pages.
- Validate map/dashboard data visibility using staging dataset.
- Confirm no real outbound notification path is enabled.
