# StoreYar Implementation Roadmap (Phase 1)

Each task is independently reviewable. Priority: integrity → architecture → product surface → frontend.

---

## TASK-001 — Tenant isolation HTTP test matrix

**Status:** Implemented (tests written). Run on PHP 8.4 CI / local. File: `tests/Security/TenantIsolationTest.php`.

| Field | Content |
|-------|---------|
| **Business outcome** | Prove users cannot access another organization’s data |
| **Current** | Middleware + ad hoc org checks; limited automated cross-tenant coverage |
| **Desired** | Feature tests: user in Org A gets 403/404 for Org B product/stock/order/payment/shipment read & write |
| **Context** | Security / all commerce modules |
| **Dependencies** | None |
| **Expected files** | `tests/Feature/Security/TenantIsolation*` or per-module feature tests |
| **DB impact** | None (test data only) |
| **API impact** | None |
| **Security impact** | High — regression net |
| **Required tests** | The task *is* the tests |
| **Migration / rollback** | N/A |
| **Acceptance criteria** | CI fails if cross-tenant access succeeds |
| **Definition of Done** | Tests green on CI MySQL; documented in TEST_STRATEGY |

---

## TASK-002 — Domain events on stock reserve / release / commit

| Field | Content |
|-------|---------|
| **Business outcome** | Stock changes from orders are auditable via outbox |
| **Current** | reserve/release/commitReserved mutate state without recordEvent |
| **Desired** | StockReserved, StockReleased, StockCommitted domain events; repository already pulls events |
| **Context** | Inventory |
| **Dependencies** | None (can precede TASK-003) |
| **Expected files** | `StockItem.php`, new event classes, unit tests, optionally ConfirmOrder feature assertion on outbox |
| **DB impact** | None (outbox rows only) |
| **API impact** | None |
| **Security impact** | Low |
| **Required tests** | Unit: event emitted; Feature/Integration: confirm writes outbox stock events |
| **Migration / rollback** | Additive events; consumers optional |
| **Acceptance criteria** | Confirm order produces stock-related outbox entries |
| **DoD** | Tests green; EVENT_CATALOG updated |

---

## TASK-003 — Sales uses Inventory application API only

| Field | Content |
|-------|---------|
| **Business outcome** | Enforce inventory ownership boundary |
| **Current** | `InventoryStockReservationService` uses Inventory Domain + Repository |
| **Desired** | Service calls Inventory Application commands/handlers (or Inventory facade); no `use …Inventory\Domain\Aggregates` from Sales |
| **Context** | Sales Infrastructure + Inventory Application |
| **Dependencies** | TASK-002 recommended |
| **Expected files** | Inventory command handlers for reserve/commit/release/restock; Sales adapter rewrite; architecture test optional ban |
| **DB impact** | None |
| **API impact** | None |
| **Security impact** | org id still required on internal calls |
| **Required tests** | Existing confirm/cancel tests pass; no domain import from Sales |
| **Migration / rollback** | Swap adapter implementation |
| **Acceptance criteria** | Cross-context dependency audit clean for Sales→Inventory Domain |
| **DoD** | Grep clean; tests green |

---

## TASK-004 — Unify outbox storage

| Field | Content |
|-------|---------|
| **Business outcome** | One operational outbox |
| **Current** | `shared_outbox` used by repos; `integration_outbox_messages` parallel |
| **Desired** | Single schema (prefer adding business_id/event_id uniqueness to the active path) + one publisher |
| **Context** | Shared |
| **Dependencies** | TASK-002 helpful |
| **Expected files** | migrations, EloquentOutbox, publisher bindings, docs |
| **DB impact** | Migration |
| **API impact** | None |
| **Required tests** | Outbox feature tests |
| **Rollback** | Expand-contract migration |
| **DoD** | Only one table written by module repos |

---

## TASK-005 — Idempotency failure recovery

| Field | Content |
|-------|---------|
| **Business outcome** | No permanent stuck `processing` keys |
| **Current** | Risk if exception after claim before complete |
| **Desired** | TTL, failed status, or same-TX claim; documented semantics + tests |
| **Context** | Shared + module services |
| **Dependencies** | None |
| **Expected files** | IdempotencyExecutor / store / sweeper command |
| **DB impact** | Possible status values / expires_at column |
| **Required tests** | Failure mid-flight scenarios |
| **DoD** | Documented + tested recovery |

---

## TASK-006 — Remove duplicate InventoryServiceProvider

| Field | Content |
|-------|---------|
| **Current** | Registered twice in `bootstrap/providers.php` |
| **Desired** | Single registration |
| **Context** | bootstrap |
| **Dependencies** | None |
| **Expected files** | `bootstrap/providers.php` |
| **Tests** | Boot / smoke |
| **DoD** | Unique provider list |

---

## TASK-007 — Legacy Laravel User / users table strategy

| Field | Content |
|-------|---------|
| **Current** | Parallel unused `users` + `App\Models\User` |
| **Desired** | Document ignore or remove migrations/model if safe |
| **Context** | Identity hygiene |
| **Risk** | Low if unused |
| **DoD** | No confusion in docs; CI green |

---

## TASK-008 — Quarantine empty module scaffolds

| Field | Content |
|-------|---------|
| **Desired** | Delete or move empty modules including `Payment` singular |
| **Context** | repo hygiene |
| **Tests** | Architecture layer-folder test may need update if folders removed |
| **DoD** | Architecture tests updated and green |

---

## TASK-009 — Stock movement ledger (incremental)

| Field | Content |
|-------|---------|
| **Desired** | Dual-write movements; reconcile balances |
| **Dependencies** | TASK-002, ADR-006 |
| **DB** | New table |
| **DoD** | Movements for init/adjust/reserve/release/commit; report reconciling |

---

## TASK-010 — MarkOrderPaid guards + multi-payment policy

| Field | Content |
|-------|---------|
| **Desired** | Reject illegal transitions; decide unique payment per order or not |
| **UNKNOWN** | Product decision on multiple payments |
| **DoD** | Tests for illegal mark-paid |

---

## TASK-011 — Money VO consolidation

| Field | Content |
|-------|---------|
| **Desired** | One Money type (Shared Support) used by Sales |
| **DoD** | No duplicate Money in Sales Domain unless justified |

---

## TASK-012 — OpenAPI contract tests

| Field | Content |
|-------|---------|
| **Desired** | Response shapes match OpenAPI for critical endpoints |
| **DoD** | CI contract suite or documented manual checklist automation |

---

## TASK-013 — Product README

| Field | Content |
|-------|---------|
| **Desired** | Replace stock Laravel README with StoreYar overview + links to docs |

---

## TASK-014 — Frontend Nuxt bootstrap

| Field | Content |
|-------|---------|
| **Desired** | Scaffold per FRONTEND_BLUEPRINT after API stability |
| **Dependencies** | TASK-001–005 at minimum |

---

## Order of execution

1. TASK-001  
2. TASK-002  
3. TASK-003  
4. TASK-006 (quick win anytime)  
5. TASK-004, TASK-005  
6. TASK-007, TASK-008  
7. TASK-009+ as product needs  

**First implementation task after Phase 1:** **TASK-001**.
