# Event Catalog

## Domain vs integration (PROPOSED policy)

| Kind | Purpose | Persistence |
|------|---------|-------------|
| Domain event | Fact inside one context | Collected on aggregate → outbox in same TX as save |
| Integration event | Contract for other contexts / projections | Published from outbox; consumers use inbox + event_id idempotency |

Not every domain event needs an integration consumer.

---

## Verified domain events (from code)

| Event | Context | When |
|-------|---------|------|
| UserCreated, UserActivated, UserSuspended | Identity | User lifecycle |
| OrganizationCreated | Organization | Org create |
| ProductCreated | Catalog | Product create |
| StockInitialized, StockAdjusted | Inventory | Init; increase/decrease only |
| OrderCreated, OrderLineAdded, OrderConfirmed, OrderPaid, OrderPaymentReverted | Sales | Order lifecycle |
| PaymentInitiated, PaymentSucceeded, PaymentFailed, PaymentRefunded | Payments | Payment lifecycle |
| ShipmentCreated, ShipmentShipped, ShipmentDelivered, ShipmentCancelled | Shipping | Shipment lifecycle |

---

## Proposed domain events (missing today)

| Event | Context | When | Why |
|-------|---------|------|-----|
| StockReserved | Inventory | reserve() | Audit + outbox visibility for confirm path |
| StockReleased | Inventory | release() | Cancel path |
| StockCommitted | Inventory | commitReserved() | Confirm path |
| ProductPriceChanged | Catalog | ChangeProductPrice | Optional consumers |
| ProductArchived / ProductActivated | Catalog | status change | Optional |

---

## Outbox recording responsibility (VERIFIED + PROPOSED)

| Component | Role |
|-----------|------|
| Aggregate | `recordEvent` |
| Repository `save` | `pullDomainEvents` + `Outbox::recordMany` in same TX (**VERIFIED** for Catalog, Inventory, Sales, Payments, Shipping) |
| Publisher | `outbox:publish` / OutboxProcessor (**VERIFIED** schedule) |
| Consumer | **PROPOSED** future; none business-critical today |

**Gap (VERIFIED):** Identity/Organization/Authorization repositories not observed writing outbox.

**Gap (VERIFIED):** dual tables `shared_outbox` vs `integration_outbox_messages` — **PROPOSED** unify on one schema with business_id + event_id unique.

---

## Event identity (PROPOSED minimum payload)

```
event_id (ULID)
event_type
aggregate_type / aggregate_id
organization_id | business_id
occurred_at
payload (versioned schema)
correlation_id / causation_id (optional)
```

`shared_outbox` currently lacks business_id (**VERIFIED**) — add in unification task.

---

## Consumer idempotency (PROPOSED)

- Inbox unique on `event_id`
- Handler no-ops if already processed
- Poison message: attempts + last_error; manual replay

---

## What not to event

- Pure queries
- Intra-handler steps already in one TX (ConfirmOrder should not wait on async StockReserved to continue)
