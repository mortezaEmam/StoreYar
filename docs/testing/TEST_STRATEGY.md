# Test Strategy

## Counts

~117 pre-existing `*Test.php` files under Unit, Feature, Integration, Architecture, Security, plus TASK-001 tenant isolation suite.

## Classification

| Type | Location | Coverage notes |
|------|----------|----------------|
| Architecture | tests/Architecture | Layer folders; Shared Domain no Laravel/Modules; module domains no Laravel |
| Security | tests/Security | Baseline + **TenantIsolationTest (TASK-001)** |
| Unit Shared | tests/Unit/Shared | Buses, idempotency, concurrency, context, money, outbox stores |
| Unit Modules | Identity heavy; some Sales/Inventory/Organization aggregates | Many command folders |
| Integration | Identity, Organization, Authorization command paths | DB-backed |
| Feature | Identity, Catalog, Inventory, Sales, Payments, Shipping, Shared messaging/idempotency | HTTP + DB |

## TASK-001 — Tenant isolation matrix

**File:** `tests/Security/TenantIsolationTest.php`

| Scenario | Expected |
|----------|----------|
| User B + `X-Business-Id` = Org A (not a member) | **403** on products, stock, orders, payments, shipments (list/get/mutate) |
| User B + `X-Business-Id` = Org B + Org A resource IDs | **404** on get/mutate; lists must not include Org A IDs |
| User A + `X-Business-Id` = Org B | **403** (symmetric) |
| Legitimate owner + own `X-Business-Id` | **200** on own product/stock/order/payment/shipment |

Resources covered: products, stock, orders, payments, shipments.

**CI:** `php artisan test tests/Security` (workflow already runs Security suite before full suite).

**Note:** Local sandbox PHP is 8.3; project requires PHP ≥8.4 (Laravel 13 / Symfony 8). Run tenant tests on CI or PHP 8.4+.

## Critical paths

| Path | Tests present? | Gap |
|------|----------------|-----|
| Cross-tenant HTTP isolation | **Yes (TASK-001)** | — |
| Stock reserve+commit on confirm | ConfirmOrder unit/feature partial | Race/concurrency multi-worker |
| Cancel restock | Partial | |
| Payment succeed → order paid | Partial | Provider webhook simulation absent |
| Shipping from non-paid order | Domain exception tests partial | |
| Outbox atomic with aggregate | Messaging feature tests | Not all aggregates |
| Idempotency stuck processing | Some failure tests | Cleanup policy |
| Branch isolation | N/A | |

## Missing (remaining)

- Load/concurrency tests for stock version conflicts under parallel confirms.
- Contract tests generating from OpenAPI.
- Full payment refund → order revert matrix.
- Outbox consumer integration (no consumers yet).
