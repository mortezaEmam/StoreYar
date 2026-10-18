# Domain Model — Current (VERIFIED) + Target (PROPOSED)

Phase 1 blueprint. Source truth for “current”: Phase 0 domain scan at `952856a6`.

---

## Shared base (VERIFIED)

- `AggregateRoot` / `Entity` / `DomainEvent` / `IntegrationEvent`
- VOs: AggregateVersion, CorrelationId, CausationId, OperationId
- Support: Money, Quantity

**PROPOSED:** Keep; do not grow Shared with context logic.

---

## User (Identity) — VERIFIED

- Root: User; Entity: Session
- VOs: UserId, SessionId; Enum: UserStatus
- Commands: CreateUser, SetPassword, AuthenticateUser, RevokeSession, RevokeAllUserSessions, RotateSession
- Events: UserCreated, UserActivated, UserSuspended
- Repo: UserRepository, SessionRepository, UserCredentialRepository
- Concurrency: version on users
- **PROPOSED:** Outbox on user lifecycle if consumers need it

---

## Organization / Branch — VERIFIED

- Roots: Organization, Branch
- Commands: Create, Rename, Suspend, Activate, CreateBranch
- Event: OrganizationCreated
- **PROPOSED:** CreateOrganization application service orchestrates Authorization.GrantMembership in one TX without Organization Domain importing Authorization Domain

---

## Membership — VERIFIED

- Membership with Role enum
- Invariants: unique (org,user); no last-owner removal; no self-revoke
- Commands: Grant, ChangeRole, Revoke

---

## Product (Catalog) — VERIFIED

- Product: id, organizationId, name, sku, unit price, currency, status, version
- Unique (org, sku)
- Commands: Create, Rename, ChangePrice, Activate, Archive
- Event: ProductCreated
- **PROPOSED:** Order lines keep price snapshot; archive does not rewrite historical lines

---

## StockItem (Inventory) — VERIFIED + PROPOSED

### Current fields (VERIFIED)
- quantity (acts as available after reserves)
- reserved_quantity
- organization_id, product_id, version

### Methods (VERIFIED)
- create / increase / decrease → emit StockInitialized / StockAdjusted
- reserve / release / commitReserved → **no domain events**

### Target model (PROPOSED)

**Projections on StockItem:**
```
on_hand   // physical
reserved  // active reservations
available = on_hand - reserved
```

Migration of field names can be gradual: today `quantity` ≈ available; introduce explicit `on_hand` via migration when ledger lands, or document mapping:
```
on_hand   := quantity + reserved_quantity   // current encoding
reserved  := reserved_quantity
available := quantity
```

**Invariants:**
1. on_hand ≥ 0, reserved ≥ 0, available ≥ 0
2. available = on_hand - reserved
3. Cannot reserve more than available
4. Cannot release/commit more than reserved
5. Unique (organization_id, product_id)

**Commands (HTTP):** InitializeStock, AdjustStock (idempotent wrappers exist)  
**Commands (internal application API for Sales):**  
- ReserveAndCommitForOrder(organizationId, lines[]) — current confirm semantics  
- ReleaseForOrder / RestockForOrder — cancel paths  

**Concurrency:** optimistic `version` on save; optional `SELECT … FOR UPDATE` inside Inventory handler TX for multi-line reserve (**PROPOSED** if race tests fail).

**Idempotency:** external HTTP adjusts by Idempotency-Key; internal order ops keyed by order id + operation type (**PROPOSED**).

### StockMovement (PROPOSED ledger)

Append-only rows: id, organization_id, product_id, type (INITIAL|ADJUST|RESERVE|RELEASE|COMMIT|RESTOCK), quantity_delta, order_id nullable, operation_id, occurred_at.  
Balance projection must be reconcilable from movements. **Do not replace balances in v1 without dual-write period.**

---

## Order (Sales) — VERIFIED + PROPOSED

### State machine (VERIFIED)
```
DRAFT --confirm--> CONFIRMED --mark paid--> PAID
  |                   |                      |
  +------ cancel -----+----------------------+
```

### Guards (VERIFIED)
- canAddLine: DRAFT
- canConfirm: DRAFT + non-empty lines
- canMarkPaid: CONFIRMED
- canRevertPaid: PAID
- canCancel: DRAFT | CONFIRMED | PAID

### OrderLine (VERIFIED)
- product_id, quantity, unit_price_amount, currency (snapshot)

### Confirm (VERIFIED behavior / PROPOSED structure)
1. Begin TX
2. Inventory.ReserveAndCommitForOrder(lines)  // port, not domain import
3. order.confirm()
4. save order + outbox
5. Commit TX

### Cancel
- If confirmed/paid: Inventory release/restock via port
- Then cancel order

### Payment relationship
- Payments context owns Payment; Sales accepts MarkOrderPaid only from valid state

---

## Payment (Payments) — VERIFIED + PROPOSED

- Status: pending → succeeded | failed; refunded
- CompletePayment: if already SUCCEEDED return; else succeed + MarkOrderPaid
- **PROPOSED:** MarkOrderPaid rejects if order not CONFIRMED
- External provider reference: **UNKNOWN** (no provider in code)
- Multiple payments per order allowed by schema — **UNKNOWN** if intentional

---

## Shipment (Shipping) — VERIFIED + PROPOSED

- Unique (organization_id, order_id)
- States: created → shipped → delivered; cancel from allowed states
- Create requires Sales IsShippableOrder (typically paid)
- Partial shipments: **UNKNOWN** → treat unsupported
- No Inventory/Payment writes

---

## Repository interfaces (pattern)

Each root: `findById`, `save` (pullDomainEvents → outbox inside save TX).  
**PROPOSED:** Inventory save always records events for reserve/release/commit paths once domain emits them.

---

## What is not an aggregate

- Eloquent models
- Idempotency keys, outbox rows, audit log rows
- Laravel `users` / framework sessions tables
