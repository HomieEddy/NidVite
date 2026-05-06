# ADR-008: RBAC & Admin Security Model

## Status
Accepted

## Context

The entrepreneur dashboard (Filament) requires tight security. Unlike the zero-auth PWA, the admin area handles sensitive business data: expenses, inventory, citizen reports, and audit trails. We need a role-based access control (RBAC) system with 5 roles, 2FA enforcement, session management, and comprehensive audit logging.

## Decision

Implement a full RBAC system with the following architecture:

### Roles

| Role | Description |
|------|-------------|
| **Admin** | Full system access. Can manage users, view audit logs, configure settings. |
| **Manager** | Manages jobs, assigns workers, approves expenses, views analytics. Cannot manage users or view audit logs. |
| **Service Worker** | Field worker. Views assigned reports/jobs, updates status, uploads after-photos. Can self-assign from available jobs. |
| **Accountant** | Views expenses, manages inventory, exports financial data. Cannot edit reports or manage jobs. |
| **Viewer** | Read-only access to reports and analytics. Every page view is logged for audit. Cannot modify any data. |

### Permission Matrix

| Permission | Admin | Manager | Service Worker | Accountant | Viewer |
|------------|-------|---------|----------------|------------|--------|
| View all reports | ✓ | ✓ | ✗ (assigned only) | ✓ | ✓ |
| Edit reports | ✓ | ✓ | ✓ (status only) | ✗ | ✗ |
| Manage jobs | ✓ | ✓ | ✓ (assigned) | ✗ | ✗ |
| Create expenses | ✓ | ✓ | ✓ | ✓ | ✗ |
| View expenses | ✓ | ✓ | ✗ | ✓ | ✗ |
| Manage inventory | ✓ | ✓ | ✗ | ✓ | ✗ |
| View analytics | ✓ | ✓ | ✓ | ✓ | ✓ |
| Export data | ✓ | ✓ | ✗ | ✓ | ✗ |
| Manage users | ✓ | ✗ | ✗ | ✗ | ✗ |
| View audit log | ✓ | ✗ | ✗ | ✗ | ✗ |
| Self-assign jobs | ✗ | ✗ | ✓ | ✗ | ✗ |
| Bulk operations | ✓ | ✓ | ✗ | ✗ | ✗ |

### 2FA (Production Only)

- **Required** for all roles in production (`APP_ENV=production`)
- **Optional** in development/staging
- TOTP via authenticator app (Google Authenticator, Authy, 1Password)
- Backup codes generated on 2FA setup (10 codes, single-use)
- Enforced at login: if 2FA enabled but not provided, login fails

### Session Security

- **Timeout**: 15 minutes idle timeout
- **Single session**: One active session per user (new login invalidates old)
- **Device tracking**: Store device fingerprint, IP, user agent
- **Session invalidation**: Admin can force-logout any user
- **Secure cookies**: `secure`, `httpOnly`, `sameSite=strict`

### Audit Logging

**For all roles:**
- Data changes (create, update, delete) on all resources
- Login/logout events
- Failed login attempts
- 2FA setup/reset events
- Password changes

**For Viewer role only (additional):**
- Every page view logged
- Report detail views logged
- Export/download events logged

**Admin-only access:**
- Audit log is viewable only by Admin role
- Cannot be deleted or modified
- Retained for 2 years

## Implementation

### Database Schema

**`roles` table:**
```sql
id (SMALLINT, PK)
slug (VARCHAR(50), UNIQUE) — 'admin', 'manager', 'service_worker', 'accountant', 'viewer'
label (VARCHAR(100), NOT NULL)
description (TEXT, NULL)
is_active (BOOLEAN, DEFAULT true)
created_at, updated_at
```

**`permissions` table:**
```sql
id (SMALLINT, PK)
slug (VARCHAR(100), UNIQUE) — 'reports.view', 'reports.edit', 'jobs.manage', etc.
label (VARCHAR(100), NOT NULL)
resource (VARCHAR(50), NOT NULL) — 'reports', 'jobs', 'expenses', etc.
action (VARCHAR(50), NOT NULL) — 'view', 'edit', 'create', 'delete'
created_at, updated_at
```

**`role_permissions` pivot:**
```sql
role_id (FK)
permission_id (FK)
PRIMARY KEY (role_id, permission_id)
```

**`users` table updates:**
- Drop `role` ENUM column
- Add `role_id` (FK → roles.id)
- Add `two_factor_secret` (encrypted text)
- Add `two_factor_recovery_codes` (encrypted text)
- Add `two_factor_confirmed_at` (timestamp)

**`admin_sessions` table:**
```sql
id (BIGSERIAL, PK)
user_id (FK → users.id)
session_id (VARCHAR(128), UNIQUE)
ip_address (INET, NOT NULL)
user_agent (VARCHAR(500), NULL)
device_fingerprint (VARCHAR(64), NULL)
last_activity_at (TIMESTAMP, NOT NULL)
is_active (BOOLEAN, DEFAULT true)
created_at, updated_at
```

**`admin_audit_log` table:**
```sql
id (BIGSERIAL, PK)
user_id (FK → users.id, NULL for system actions)
action (ENUM: 'view', 'create', 'update', 'delete', 'login', 'logout', 'export', 'login_failed')
table_name (VARCHAR(100), NULL)
record_id (BIGINT, NULL)
old_values (JSONB, NULL)
new_values (JSONB, NULL)
ip_address (INET, NOT NULL)
user_agent (VARCHAR(500), NULL)
created_at (TIMESTAMP, NOT NULL)
```

### Packages

- `laravel/fortify` — Auth scaffolding with 2FA support
- `pragmarx/google2fa-laravel` — TOTP generation/verification (if needed beyond Fortify)

## Consequences

- **Positive**: Granular access control prevents unauthorized data exposure
- **Positive**: Audit trail satisfies compliance requirements
- **Positive**: 2FA protects against credential theft
- **Negative**: More complex user management
- **Negative**: Viewer role page-view logging creates write overhead (mitigated by async queue)

## Related Decisions

- SECURITY_PRIVACY.md: Admin security controls
- DEFINITION_OF_DONE.md: RBAC testing requirements
- SCHEMA_OVERVIEW.md: Complete RBAC tables
