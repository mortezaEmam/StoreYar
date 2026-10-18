# StoreYar — Architecture Audit (Phase 0)

**Commit:** `952856a6dbf81e96bffdaee2a604d6ea35c7c33e`  
**Rule:** Source code is truth. UNKNOWN marks unverified items.

---

## 1. Executive Summary

StoreYar is a **Laravel modular monolith** implementing multi-tenant commerce with **DDD-style modules**, **CQRS command/query buses**, **session auth**, **organization membership**, **transactional outbox (partial)**, and **idempotency (opt-in)**.

**Active business modules:** Identity, Organization, Authorization, Catalog, Inventory, Sales, Payments, Shipping.  
**Eighteen** additional module folders are empty scaffolds.

**Strengths:** Clear layer folders; architecture tests protecting domain purity; org-scoped tables; version columns; real order→stock→payment→shipment flows in code.

**Critical gaps:** Sales writes Inventory domain directly; stock reserve/commit silent to outbox; dual outbox schemas; tenant checks are manual; no stock ledger; no Nuxt frontend; README is stock Laravel.

---

## 2. Repository Structure

See [REPOSITORY_MAP.md](REPOSITORY_MAP.md).

PHP ^8.3, Laravel ^13.17, PHPUnit 12, Vite, CI on PHP 8.4 + MySQL 8.4. No Boost installed despite AGENTS.md text. Pint present; no PHPStan in composer.json.

---

## 3. Current Architecture

Modular monolith: Presentation → Application (commands/queries) → Domain ← Infrastructure.

Shared kernel: buses, Result, BusinessContext, Outbox, Idempotency, Audit, Money/Quantity, concurrency.

Providers register per-module handlers. Middleware: `auth.session`, `business.context`, `org.member`.

---

## 4. Bounded Contexts

See [BOUNDED_CONTEXTS.md](BOUNDED_CONTEXTS.md).

Real contexts match active modules. Scaffold folders are **not** contexts yet. Shared is kernel, not a BC.

---

## 5. Aggregate Map

| Aggregate | Module | Notes |
|-----------|--------|-------|
| User (+ Session entity) | Identity | |
| Organization, Branch | Organization | |
| Membership | Authorization | Entity/versioned |
| Product | Catalog | |
| StockItem | Inventory | quantity≈available; reserved_quantity |
| Order (+ OrderLine) | Sales | draft→confirmed→paid / cancelled |
| Payment | Payments | pending→succeeded/failed; refunded |
| Shipment | Shipping | unique per org+order |

---

## 6. Domain Model

See [DOMAIN_MODEL.md](../domain/DOMAIN_MODEL.md).

---

## 7. Database Model

See [DATABASE_MODEL.md](../database/DATABASE_MODEL.md).

Hard FKs rare; tenant key `organization_id` / `business_id`; dual outbox tables; no ledger.

---

## 8. API Model

REST under `/api` with module route files. Bearer session token + `X-Business-Id`. Idempotency-Key on selected mutations. OpenAPI candidate contract — see [API_RECONCILIATION.md](../api/API_RECONCILIATION.md).

---

## 9. Business Workflows

See [WORKFLOWS.md](WORKFLOWS.md).

Confirm order: **same transaction** reserve+commit stock then confirm. Payment succeed: **same transaction** mark payment + dispatch MarkOrderPaid. Shipping independent create after paid (gateway check).

---

## 10. Authentication

Custom opaque session tokens (`identity_sessions.token_hash`). Register/login/logout/rotate/me. Not Sanctum. Throttling on auth routes.

---

## 11. Authorization

Membership roles (owner/admin/…). Middleware `org.member` and role lists. Shared AuthorizationService contracts exist; primary enforcement is membership middleware + handler org checks.

---

## 12. Tenant Isolation

Application-level via BusinessContext + middleware + handler comparisons. **No RLS**. Branch in context but **not** on commerce tables. Risk: missed filter = cross-tenant access (P0 test gap).

Details: [TENANCY_AND_AUTHORIZATION.md](../security/TENANCY_AND_AUTHORIZATION.md).

---

## 13. Idempotency

Table `integration_idempotency_keys`; store claim/complete/replay; module services for stock/orders/payments/shipping. Not global middleware. Stuck `processing` risk on failures.

Details: [IDEMPOTENCY.md](../integration/IDEMPOTENCY.md).

---

## 14. Outbox

Module repos → `EloquentOutbox` → **`shared_outbox`**. Scheduler `outbox:publish`. Default publisher logs. Parallel unused-richer `integration_outbox_messages`. Stock reserve path often emits **no** events.

Details: [OUTBOX.md](../integration/OUTBOX.md).

---

## 15. Inbox

Table `integration_inbox_messages` + application inbox store abstractions. **No verified business event consumers** driving other modules.

---

## 16. Inventory Consistency

Balance-only model. available≈`quantity`, reserved=`reserved_quantity`. Optimistic `version`. Confirm path reserve then commit immediately (no long-lived reservation row). **No movement ledger**. Sales mutates Inventory domain directly (P1).

---

## 17. Sales Consistency

Order state machine enforced in aggregate. Confirm requires stock success in same transaction. Cancel restocks via service. Paid via Payments command. Lines unique per product.

---

## 18. Payment Consistency

Independent Payment aggregate. Complete is idempotent if already SUCCEEDED. Success drives MarkOrderPaid via CommandBus (sync). Multiple payments per order allowed by schema (no unique order_id).

---

## 19. Shipping Consistency

Shipment unique (org, order). Create gated by ShippableOrderGateway (Sales). Independent lifecycle ship/deliver/cancel.

---

## 20. Frontend

Blade welcome + Vite + axios only. **No Vue/Nuxt**. Target Nuxt 4 stack is greenfield — [FRONTEND_AUDIT.md](../frontend/FRONTEND_AUDIT.md).

---

## 21. Testing

~117 tests; architecture + security gates in CI; Identity heaviest; gaps in cross-tenant matrix, stock races, outbox consumers — [TEST_STRATEGY.md](../testing/TEST_STRATEGY.md).

---

## 22. CI/CD

GitHub Actions: composer validate, install, migrate MySQL 8.4, php -l, Architecture tests, Security tests, full suite. Push/PR to master.

---

## 23. Security

Session auth, throttling, membership roles, org checks. Threats: tenant escape via missed filters; stuck idempotency; silent stock mutations. No CSRF surface for pure API token clients (API JSON). UNKNOWN: rate limits completeness, token entropy implementation details beyond generator class existence.

---

## 24. Architecture Violations

See [ARCHITECTURE_DEBT.md](ARCHITECTURE_DEBT.md) and [CROSS_CONTEXT_DEPENDENCIES.md](CROSS_CONTEXT_DEPENDENCIES.md).

Headline P1: **Sales → Inventory domain aggregate write**.

---

## 25. Missing Features

- Stock ledger / movements  
- Async integration consumers  
- Payment provider webhooks  
- Customer BC, Pricing, Purchasing, Accounting (scaffolds only)  
- Branch-level inventory/orders  
- Real frontend  
- Full OpenAPI response contract tests  
- PHPStan/static analysis in CI  

---

## 26. Documentation Gaps

- Product README missing (stock Laravel)  
- ADRs not yet written (deferred until decisions locked in implementation)  
- Boost docs without Boost  

Phase 0 docs under `docs/` close the discovery gap.

---

## 27. Target Architecture

```
Modular Monolith
+ DDD modules (explicit ownership)
+ Pragmatic CQRS (already present)
+ Hexagonal ports for cross-context (Application APIs / integration events)
+ Single transactional outbox + idempotent consumers
+ Strong tenant isolation (tests + consistent handler rules; optional future RLS)
+ API-first; Nuxt client later
```

**No microservices** unless a concrete requirement appears.

Shared kernel stays small (IDs, events base, Result, Clock, Outbox contract, BusinessContext, messaging contracts).

---

## 28. Migration Strategy

1. Lock discovery docs (this phase).  
2. P0 tests for tenant isolation.  
3. Inventory event completeness + Sales port refactor.  
4. Unify outbox; harden idempotency.  
5. Remove scaffolds/legacy noise.  
6. Ledger ADR + implement if required by business.  
7. Frontend only after API contracts stable.

Incremental; no big-bang rewrite.

---

## 29. P0/P1/P2/P3/P4 Priorities

| Sev | Top items |
|-----|-----------|
| P0 | Tenant isolation test matrix; stock mutation observability |
| P1 | Sales/Inventory boundary; outbox unification; idempotency failure; duplicate provider |
| P2 | Scaffolds cleanup; ledger; Money VO; OpenAPI contracts |
| P3 | README; tooling consistency |
| P4 | Cosmetic |

Full list: [ARCHITECTURE_DEBT.md](ARCHITECTURE_DEBT.md), [ROADMAP.md](../ROADMAP.md).

---

## 30. Recommended First Implementation Task

**TASK-001 — Tenant isolation HTTP test matrix**

Prove that authenticated user in Org A cannot read/mutate Org B products, stock, orders, payments, shipments. Fail CI on regression.

Then **TASK-002** (domain events on reserve/release/commit) and **TASK-003** (Inventory application API for Sales).

Do **not** start frontend or empty-module features until P0/P1 integrity items are addressed.
