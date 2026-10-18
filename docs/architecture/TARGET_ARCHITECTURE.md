# StoreYar — Target Architecture Blueprint (Phase 1)

**Based on:** Phase 0 audit at commit `952856a6dbf81e96bffdaee2a604d6ea35c7c33e`  
**Classification legend:** **VERIFIED** (current code) · **PROPOSED** (target, not yet implemented) · **UNKNOWN** (needs decision or deeper investigation)

---

## 1. Confirmed current architecture (VERIFIED)

```
Laravel modular monolith
├── Presentation (HTTP controllers, requests, resources, module routes)
├── Application (Commands/Handlers, Queries/Handlers, idempotent services)
├── Domain (Aggregates, VOs, domain events, repository contracts)
└── Infrastructure (Eloquent repos, gateways, ServiceProviders)
+ Shared kernel (buses, Result, BusinessContext, Outbox, Idempotency, Audit, Clock, ULID)
+ Multi-tenant org via X-Business-Id + membership middleware
+ Session-token authentication (Identity)
```

**Active contexts:** Identity, Organization, Authorization, Catalog, Inventory, Sales, Payments, Shipping.  
**Not contexts yet:** 18 empty scaffold folders.

**Critical verified defects (Phase 0):**
- Sales Infrastructure mutates Inventory `StockItem` + repository directly
- `reserve` / `release` / `commitReserved` emit no domain events
- Dual outbox tables (`shared_outbox` used; `integration_outbox_messages` parallel)
- Tenant isolation is application-only (manual org checks)
- No stock movement ledger
- InventoryServiceProvider registered twice
- Frontend is Blade welcome only

---

## 2. Proposed target architecture (PROPOSED)

```
Modular Monolith (single deployable)
+ DDD modules with explicit ownership
+ Pragmatic CQRS (CommandBus / QueryBus already VERIFIED — keep)
+ Hexagonal ports for cross-context calls
+ One transactional Outbox + idempotent consumers
+ Strong tenant isolation (middleware + handler rules + tests; no microservices)
+ API-first public surface; Nuxt client later
```

**Not proposed:** microservices, distributed sagas as default, event-sourcing full rewrite, branch-level commerce isolation (unless business requires it later).

### Layer responsibilities (PROPOSED, aligned with VERIFIED layout)

| Layer | Responsibility | Must not |
|-------|----------------|----------|
| Domain | Invariants, state machines, domain events | Laravel, HTTP, other modules |
| Application | Use cases, transactions, orchestration of ports | Eloquent models, HTTP details |
| Infrastructure | Repositories, gateways, outbox writers, external I/O | Business invariants |
| Presentation | AuthN headers, validation DTOs, map to commands/queries | Domain logic, cross-tenant bypass |

### Laravel mapping (PROPOSED)

Preserve existing conventions:
- `app/Modules/{Context}/{Domain,Application,Infrastructure,Presentation}`
- `app/Shared/{Domain,Application,Infrastructure,Presentation,Support}`
- Module `*ServiceProvider` registers handlers and port bindings
- `routes/api.php` groups module routes + middleware aliases

Improve:
- One registration per provider
- Cross-context only via Application contracts or Outbox integration events
- Controllers remain thin; transactions only at handler/service boundaries
- No open DB transaction across external HTTP payment providers (when added)

---

## 3. Bounded context map (summary)

| Context | Owns | Does not own |
|---------|------|--------------|
| Identity | Users, credentials, sessions | Membership, orgs |
| Organization | Organizations, branches | Roles, products |
| Authorization | Memberships, roles | User credentials |
| Catalog | Products, SKU, list price, status | Stock quantities |
| Inventory | Stock balances, reservations, movements | Orders, payments |
| Sales | Orders, lines, order status | Stock tables, payment provider |
| Payments | Payment attempts, payment status | Order aggregate persistence |
| Shipping | Shipments, tracking, ship status | Inventory, payment tables |
| Shared | Generic contracts only | Any product/order/stock rules |

Full per-context sheets: [BOUNDED_CONTEXTS.md](BOUNDED_CONTEXTS.md).

---

## 4. Aggregate ownership map

| Aggregate root | Context | Identity | Child entities | Concurrency |
|----------------|---------|----------|----------------|-------------|
| User | Identity | UserId (ULID) | Session | version |
| Organization | Organization | OrganizationId | — | version |
| Branch | Organization | BranchId | — | version |
| Membership | Authorization | MembershipId | — | version |
| Product | Catalog | ProductId | — | version |
| StockItem (balance projection) | Inventory | StockItemId | — | version + optional lock |
| StockReservation (PROPOSED optional) | Inventory | ReservationId | lines | version |
| StockMovement (PROPOSED ledger entry) | Inventory | MovementId | — | append-only |
| Order | Sales | OrderId | OrderLine | version |
| Payment | Payments | PaymentId | — | version |
| Shipment | Shipping | ShipmentId | — | version |

**PROPOSED:** Keep `StockItem` as the balance projection aggregate; introduce append-only **StockMovement** records for audit/reconciliation. Do **not** make every table an aggregate. Reservation may remain methods on StockItem **or** become a first-class aggregate if multi-line order reservations need independent lifecycle — prefer methods + movement rows first (smaller change).

---

## 5. Critical invariants

### Inventory (PROPOSED formulas; current semantics VERIFIED)

**Current (VERIFIED):** On reserve: `quantity -= amount`, `reserved_quantity += amount`. Available for further reserve ≈ `quantity`. On-hand conceptually ≈ `quantity + reserved_quantity`.

**Target formulas (PROPOSED):**
```
on_hand     = projection field (or sum of movements of type RECEIPT/ADJUST+/COMMIT effects)
reserved    = sum of active reservations (or projection field)
available   = on_hand - reserved
```
Must never allow `available < 0` or `reserved < 0` or `reserved > on_hand`.

| Rule | Current | Target |
|------|---------|--------|
| Initialize | quantity ≥ 0, unique (org, product) | Same + movement INITIAL |
| Adjust up/down | increase/decrease with events | Same + movement ADJUST |
| Reserve | decreases quantity, increases reserved; **no event** | Same math + movement RESERVE + event |
| Release | reverse reserve; **no event** | + movement RELEASE + event |
| Commit | decreases reserved only; **no event** | + movement COMMIT + event |
| Concurrent reserve | version on StockItem | Keep optimistic version; optional row lock in transaction |
| Insufficient | domain exceptions | Same |
| Idempotent external adjust | Idempotency-Key services | Keep; movements must also be idempotent by operation_id |

**Sales must not touch Inventory tables (PROPOSED).** Call Inventory application API only.

### Sales (VERIFIED state machine + PROPOSED rules)

```
DRAFT → CONFIRMED → PAID
  ↓         ↓         ↓
CANCELLED ← ← ← ← ← ←
```

- Lines only in DRAFT; unique product per order; non-empty to confirm.
- Confirm: **immediate consistency** with Inventory reserve+commit in **one application transaction** (modular monolith) via Inventory port — VERIFIED behavior, PROPOSED cleaner port.
- Price/name: **snapshot on OrderLine at add-line time** (VERIFIED unit_price on line). Product archive after order: order lines remain valid (PROPOSED explicit rule).
- Cancel: release/restock per state (VERIFIED paths via StockReservationService).

### Payments (VERIFIED + PROPOSED)

- Lifecycle independent of Order: pending → succeeded | failed; refunded.
- Succeed must not mark order paid if order not CONFIRMED (PROPOSED guard on MarkOrderPaid).
- Duplicate succeed is no-op (VERIFIED for SUCCEEDED).
- External provider: **UNKNOWN / not present** — design webhook port later; do not invent provider.

### Shipping (VERIFIED + PROPOSED)

- One shipment per (org, order) unique (VERIFIED).
- Partial shipments: **UNKNOWN** — treat as **not supported** until product decision.
- Must not write Payment or Inventory persistence (PROPOSED; currently uses Sales gateway read only — VERIFIED).

---

## 6. Transaction boundaries (summary)

| Use case | Boundary | Notes |
|----------|----------|-------|
| ConfirmOrder | Single DB transaction: Inventory reserve+commit API + Order.confirm + outbox | Immediate consistency required |
| CompletePayment | Single DB transaction: Payment.succeed + MarkOrderPaid command + outbox | Sync command OK in monolith |
| CreateShipment | Single DB transaction after sync query “is shippable?” | Query outside or inside short TX |
| AdjustStock | Single TX + idempotency claim | |
| Outbox publish | Separate from business TX | At-least-once |

Full detail: [TRANSACTION_BOUNDARIES.md](TRANSACTION_BOUNDARIES.md).

---

## 7. Cross-context contracts (summary)

| From → To | Mechanism (PROPOSED) | Current (VERIFIED) |
|-----------|----------------------|--------------------|
| Sales → Inventory | Application commands: ReserveAndCommitForOrder, ReleaseForOrder, RestockForOrder | Direct StockItem mutation **violation** |
| Sales → Catalog | Query port: GetProductPricing / EnsureActiveProduct | CatalogProductCatalogGateway |
| Inventory → Catalog | Query port: ProductExists | CatalogProductExistenceChecker |
| Payments → Sales | Command: MarkOrderPaid; Query: IsPayableOrder | CommandBus + gateway |
| Shipping → Sales | Query: IsShippableOrder | SalesShippableOrderGateway |
| Organization → Authorization | Command: GrantMembership on create | Handler cross-call |
| Domain events → world | Outbox → publisher → (future) inbox consumers | Log publisher; no business consumers |

Full: [CROSS_CONTEXT_CONTRACTS.md](CROSS_CONTEXT_CONTRACTS.md).

---

## 8. Security and tenant model (PROPOSED)

1. Authenticate session → user id.
2. Client sends `X-Business-Id` as **claim**, not proof.
3. `org.member` verifies membership for that business + optional roles.
4. Every command/query carries `organizationId` from **validated context**, not from arbitrary body field alone.
5. List/get handlers always filter by organization_id.
6. Jobs/consumers: BusinessContext must be set from event payload `business_id` / `organization_id`, never from ambient global state without validation.

Branch: optional context field; **commerce aggregates remain org-scoped** until product requires branch stock (PROPOSED no branch isolation on stock/orders now).

---

## 9. Event and messaging policy (PROPOSED)

- **Domain events:** inside context, recorded on aggregate, persisted via outbox in same TX as aggregate save.
- **Integration events:** only when another context must react asynchronously; published from outbox.
- Confirm stock: **not** eventual — sync application API (invariant).
- Payment succeeded → order paid: sync command today is acceptable; optional future integration event if decoupling needed.
- **One outbox implementation** (unify `shared_outbox` vs integration table — prefer evolving one schema).
- Consumers: inbox + idempotent by `event_id`.

Catalog: [EVENT_CATALOG.md](../integration/EVENT_CATALOG.md).

---

## 10. Frontend (PROPOSED, deferred)

Nuxt 4 + Vue 3 + TS + Nuxt UI + Tailwind + typed API client. Backend remains authority. See [FRONTEND_BLUEPRINT.md](../frontend/FRONTEND_BLUEPRINT.md). **Do not start frontend until P0/P1 integrity tasks land.**

---

## 11. Implementation order (PROPOSED)

1. Tenant isolation tests (P0)  
2. Stock domain events on reserve/release/commit (P0/P1)  
3. Inventory application API; Sales adapter uses it only (P1)  
4. Outbox unification + metadata (P1)  
5. Idempotency failure recovery (P1)  
6. Provider duplicate fix, scaffolds, legacy users (P1/P2)  
7. Stock movement ledger incremental (P2)  
8. OpenAPI contract tests (P2)  
9. Frontend bootstrap (P3)

Detailed tasks: [ROADMAP.md](../ROADMAP.md).

---

## 12. Top unresolved business decisions (UNKNOWN)

1. Are **partial shipments** required?  
2. Multiple concurrent payments per order — intentional or should be unique?  
3. Is **branch-level stock** required in v1?  
4. External payment provider and webhook model?  
5. Must stock history be a full ledger for accounting, or is audit_log + outbox enough for v1?  
6. Soft-delete vs archive-only for products/orders?

Until decided, implement the narrower safe default (no partial ship, org-level stock, snapshot prices, no invented PSP).
