# Backup and Monitoring Runbook

## Scope

This runbook covers Phase 6 operations:

- Daily DB backup to R2
- Health endpoint with DB/Redis/queue/disk/mail checks
- Schedule monitor wiring for scheduled tasks
- Data retention: purge stale raw IPs and archive old reports to R2 cold storage

## Scheduled Tasks

Defined in routes/console.php:

- health:schedule-check-heartbeat (every minute)
- health:queue-check-heartbeat (every minute)
- health:check --fail-command-on-failing-check (every 5 minutes)
- backup:clean (daily 01:00)
- backup:run --only-db (daily 01:30)
- reports:run-retention (daily 02:30)
- model:prune for schedule-monitor logs (daily 03:30)

All tasks are tagged with monitor names so they are tracked by spatie/laravel-schedule-monitor.

## Health Endpoint

- Route: GET /health
- Controller: Spatie health JSON results controller
- Returns latest health check results as JSON

## Backup Target

- Disk: BACKUP_DISK (defaults to r2)
- Configuration: config/backup.php

## Retention Policy

- Purge IP age: RETENTION_IP_PURGE_DAYS (default 30)
- Archive age: RETENTION_REPORT_ARCHIVE_DAYS (default 730)
- Archive disk: RETENTION_COLD_STORAGE_DISK (default r2-cold)
- Archive prefix: RETENTION_COLD_STORAGE_PREFIX (default cold/reports)

Command:

- php artisan reports:run-retention

## Deployment Note

Scheduler startup should run schedule sync before schedule execution:

- php artisan schedule-monitor:sync --no-interaction
- php artisan schedule:run --no-interaction --verbose
