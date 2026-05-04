# ADR-009: Job-Based Expense Tracking with Inventory

## Status
Accepted

## Context

The entrepreneur needs to track costs associated with repairing potholes. A single repair trip ("job") may fix multiple potholes and incur multiple expenses (materials, labor, fuel). Additionally, material inventory must be tracked with stock levels, purchase prices, and vendor information for proper expense auditing.

## Decision

Implement a **job-based expense tracking system** with the following principles:

### Core Principles

1. **Job-Based**: Expenses attach to a `repair_job`, which groups multiple `reports`
2. **Optional Fields**: All expense fields are nullable for quick job entry
3. **Equal Cost Split**: Default cost allocation is equal across all reports in a job
4. **Manual Override**: Manager can set custom cost percentages per report
5. **Inventory Tracking**: Stock levels decrement on job completion, reserved during job execution
6. **Tax Compliance**: Quebec GST (5%) + QST (9.975%) = 14.975% tracked on all expenses

### Report State Machine

```
pending → scheduled → in_progress → repaired
   ↓         ↓            ↓            ↓
rejected  (any state can transition to rejected by admin/manager)
```

**Transition Rules:**
- `pending → scheduled`: Only Manager or Admin
- `scheduled → in_progress`: Only assigned Service Worker (or Manager)
- `in_progress → repaired`: Only Manager, or auto-completed when job closes
- `any → rejected`: Only Admin or Manager (with reason required)

### Job Lifecycle

```
planned → in_progress → completed
   ↓          ↓            ↓
cancelled  (any state can transition to cancelled)
```

**Inventory Rules:**
- Job created: No stock change
- Job started: `reserved_quantity` incremented
- Job completed: `reserved_quantity` moved to `current_stock` decrement
- Job cancelled: `reserved_quantity` released back to stock

### Cost Allocation

**Default:** Equal split across all reports in job
```
Job total: $500
Reports: 5
Each report cost: $100
```

**Manual Override:**
```
Job total: $500
Report A: 60% = $300
Report B: 30% = $150
Report C: 10% = $50
```

**Storage:** `job_reports.cost_allocation_percentage` (FLOAT, default 100/n)

### Tax Handling

All expenses track:
- `subtotal` (pre-tax)
- `tax_rate` (14.975% for Quebec)
- `tax_amount` (calculated)
- `total` (subtotal + tax)

## Tables

### `repair_jobs`

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGSERIAL | PK |
| `uuid` | UUID | Public identifier |
| `title` | VARCHAR(255) | e.g., "Downtown sweep — May 5" |
| `description` | TEXT | Nullable |
| `scheduled_at` | TIMESTAMP | When planned |
| `started_at` | TIMESTAMP | When work began |
| `completed_at` | TIMESTAMP | When work finished |
| `status` | ENUM | `planned`, `in_progress`, `completed`, `cancelled` |
| `created_by` | FK | Manager who created it |
| `estimated_cost` | DECIMAL(10,2) | Pre-work estimate |
| `actual_cost` | DECIMAL(10,2) | Computed from expenses |
| `location_radius` | GEOGRAPHY | Area covered (optional) |
| `weather_conditions` | VARCHAR(255) | Optional context |
| `all_fields_optional` | BOOLEAN | Always true — quick entry mode |
| `created_at`, `updated_at` | TIMESTAMP | |

### `job_reports` (pivot)

| Column | Type | Notes |
|--------|------|-------|
| `repair_job_id` | FK | |
| `report_id` | FK | |
| `cost_allocation_percentage` | FLOAT | Default: 100/n |
| `cost_override_reason` | VARCHAR(255) | Nullable |
| `repair_notes` | TEXT | Per-report notes |
| `created_at`, `updated_at` | TIMESTAMP | |

### `job_workers` (pivot — multiple workers per job)

| Column | Type | Notes |
|--------|------|-------|
| `repair_job_id` | FK | |
| `user_id` | FK | Service Worker |
| `role_in_job` | ENUM | `lead`, `assistant` |
| `hours_worked` | FLOAT | Nullable |
| `created_at`, `updated_at` | TIMESTAMP | |

### `expenses`

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGSERIAL | PK |
| `repair_job_id` | FK | |
| `category_id` | FK | expense_categories |
| `material_id` | FK | materials (nullable) |
| `description` | VARCHAR(500) | |
| `quantity` | FLOAT | |
| `unit` | VARCHAR(50) | bag, liter, hour, km |
| `unit_cost` | DECIMAL(10,2) | |
| `subtotal` | DECIMAL(10,2) | quantity × unit_cost |
| `tax_rate` | DECIMAL(5,4) | Default 0.14975 |
| `tax_amount` | DECIMAL(10,2) | subtotal × tax_rate |
| `total` | DECIMAL(10,2) | subtotal + tax_amount |
| `vendor` | VARCHAR(255) | Where purchased |
| `vendor_contact` | VARCHAR(255) | Nullable |
| `receipt_media_id` | FK | Spatie receipt photo |
| `incurred_at` | TIMESTAMP | When expense occurred |
| `created_by` | FK | users.id |
| `created_at`, `updated_at` | TIMESTAMP | |

### `expense_categories`

| Column | Type | Notes |
|--------|------|-------|
| `id` | SMALLINT | PK |
| `slug` | VARCHAR(50) | Unique |
| `label_fr` | VARCHAR(100) | French label (default) |
| `label_en` | VARCHAR(100) | English label |
| `color` | VARCHAR(7) | Hex for charts |
| `is_inventory_related` | BOOLEAN | True for Materials |
| `is_required` | BOOLEAN | False — all optional |
| `sort_order` | SMALLINT | |
| `created_at`, `updated_at` | TIMESTAMP | |

### `materials`

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGSERIAL | PK |
| `sku` | VARCHAR(100) | Unique stock code |
| `name` | VARCHAR(255) | "Cold Patch Asphalt" |
| `description` | TEXT | Nullable |
| `unit` | VARCHAR(50) | bag, liter, kg |
| `current_stock` | FLOAT | Real-time quantity |
| `reserved_stock` | FLOAT | For in-progress jobs |
| `min_stock_alert` | FLOAT | Threshold for low stock |
| `avg_purchase_price` | DECIMAL(10,2) | Weighted average |
| `last_purchase_price` | DECIMAL(10,2) | Most recent |
| `location` | VARCHAR(255) | Warehouse, Truck A, etc. |
| `is_active` | BOOLEAN | |
| `created_at`, `updated_at` | TIMESTAMP | |

### `material_purchases`

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGSERIAL | PK |
| `material_id` | FK | |
| `quantity` | FLOAT | |
| `unit_cost` | DECIMAL(10,2) | |
| `subtotal` | DECIMAL(10,2) | |
| `tax_rate` | DECIMAL(5,4) | 0.14975 |
| `tax_amount` | DECIMAL(10,2) | |
| `total` | DECIMAL(10,2) | |
| `vendor` | VARCHAR(255) | |
| `vendor_contact` | VARCHAR(255) | Nullable |
| `receipt_media_id` | FK | Spatie receipt |
| `stock_updated` | BOOLEAN | Did we add to inventory? |
| `purchased_at` | TIMESTAMP | |
| `created_by` | FK | users.id |
| `created_at`, `updated_at` | TIMESTAMP | |

### `job_materials` (pivot)

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGSERIAL | PK |
| `repair_job_id` | FK | |
| `material_id` | FK | |
| `quantity_planned` | FLOAT | Estimated needed |
| `quantity_actual` | FLOAT | Actually used |
| `created_at`, `updated_at` | TIMESTAMP | |

## Inventory Rules

1. **Job Creation**: No stock change
2. **Job Start**: `reserved_stock += quantity_planned`
3. **Job Complete**: `current_stock -= quantity_actual`, `reserved_stock -= quantity_planned`
4. **Job Cancel**: `reserved_stock -= quantity_planned`
5. **Purchase**: `current_stock += quantity`, update avg price
6. **Low Stock Alert**: If `current_stock - reserved_stock < min_stock_alert`, alert manager

## Notifications

| Trigger | Recipients | Method |
|---------|-----------|--------|
| Low stock | Manager, Admin | Dashboard badge + email |
| Critical report submitted | Manager, Admin | Dashboard badge + email |
| Job assigned | Service Worker | Dashboard badge |
| Job overdue (SLA breach) | Manager | Dashboard badge + email |
| Expense exceeds estimate | Manager | Dashboard badge |

## Consequences

- **Positive**: Accurate cost tracking per job and per report
- **Positive**: Inventory prevents running out of materials mid-job
- **Positive**: Tax compliance built-in
- **Negative**: More complex data entry (mitigated by optional fields)
- **Negative**: Stock calculation must be transaction-safe (use database transactions)

## Related Decisions

- ADR-008: RBAC determines who can create/manage jobs and expenses
- SCHEMA_OVERVIEW.md: Complete expense tracking tables
- TECH_STACK.md: `maatwebsite/laravel-excel` for export
