# Database Model

Documented from migrations only. No redesign.

## Table inventory

| Table | Owner Context | Purpose | PK | Tenant key | Important FKs | Unique | Indexes / notes |
|-------|---------------|---------|----|------------|---------------|--------|-----------------|
| users | LEGACY Laravel | Default skeleton | id bigint | — | — | email | Not used by Identity module |
| password_reset_tokens | LEGACY | — | email | — | — | — | Unused by Identity |
| sessions | LEGACY Laravel session driver | — | id | — | user_id | — | Not Identity sessions |
| cache / cache_locks | Framework | Cache | key | — | — | — | |
| jobs / job_batches / failed_jobs | Framework | Queue | — | — | — | — | |
| integration_outbox_messages | Shared/Integration | Rich outbox | id ULID | business_id | — | event_id | Not written by EloquentOutbox |
| integration_inbox_messages | Shared/Integration | Inbox | id ULID | business_id | — | event_id | Store exists; consumer usage partial |
| integration_idempotency_keys | Shared/Integration | Idempotency | id ULID | business_id | — | (business_id, operation_id) | Used by DatabaseIdempotencyStore path |
| identity_users | Identity | Users | id ULID | global | — | email | version |
| identity_user_credentials | Identity | Password hashes | user_id | — | FK → identity_users CASCADE | — | |
| identity_sessions | Identity | Auth sessions | id ULID | — | user_id (no FK) | token_hash | |
| organization_organizations | Organization | Businesses | id ULID | self | owner_user_id (no FK) | name | version |
| organization_branches | Organization | Branches | id ULID | organization_id | no FK | (org, name) | version |
| authorization_memberships | Authorization | Roles | id ULID | organization_id | user_id no FK | (org, user) | version |
| catalog_products | Catalog | Products | id ULID | organization_id | — | (org, sku) | unit_price_amount, currency; version |
| inventory_stock_items | Inventory | Stock balances | id ULID | organization_id | product_id no FK | (org, product) | quantity, reserved_quantity; version |
| sales_orders | Sales | Orders | id ULID | organization_id | customer_user_id no FK | — | status, version |
| sales_order_lines | Sales | Lines | id ULID | via order | FK order_id CASCADE; product_id no FK | (order_id, product_id) | |
| shared_outbox | Shared | Domain event outbox | id ULID | — (no business_id column) | — | — | **Used by module repositories** |
| payments_payments | Payments | Payments | id ULID | organization_id | order_id no FK | — | amount, currency, status, version |
| shipping_shipments | Shipping | Shipments | id ULID | organization_id | order_id no FK | (org, order_id) | status, tracking, version |
| shared_audit_log | Shared | Audit | id ULID | organization_id nullable | — | — | action, resource_* |

## Cross-module foreign keys

- Only **hard FKs**: `identity_user_credentials.user_id` → `identity_users`; `sales_order_lines.order_id` → `sales_orders`.
- All other cross-context references (`product_id`, `order_id`, `organization_id`, `user_id`) are **logical string ULIDs without FK constraints**.

## Tenant isolation at DB level

- Business-owned tables carry `organization_id` (or `business_id` on integration tables).
- **No DB-level RLS**. Isolation is application-enforced.
- Branch: `organization_branches` only; most aggregates do **not** store `branch_id` (BusinessContext has optional branch but stock/orders are org-scoped only in schema).

## Concurrency columns

`version` (unsigned int/bigint) on: identity_users, organizations, branches, memberships, products, stock_items, orders, payments, shipments.

## Soft deletes

None observed in migrations.

## Dual outbox

| Table | Written by code path |
|-------|----------------------|
| `shared_outbox` | `EloquentOutbox` / module repositories |
| `integration_outbox_messages` | Integration messaging infrastructure (InMemory/Database stores); **not** the primary repository outbox path |

## Gaps

- No stock movement / ledger table.
- No reservation table keyed by order (reservation is denormalized into stock_items.reserved_quantity only).
- payments: no unique(order_id) — multiple payments per order possible at DB level.
- Identity sessions: no FK to users.
