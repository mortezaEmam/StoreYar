# Transaction Boundaries

## Principles (PROPOSED)

1. One business use case → one explicit transaction at the Application handler/service.
2. Aggregate save + outbox record in the **same** transaction.
3. Never hold a DB transaction open across external network I/O (future payment providers).
4. Cross-context sync calls allowed **inside** the same monolith DB transaction when they share the connection (Laravel `DB::transaction`).
5. Outbox **publish** is a separate process (scheduler/worker).

---

## Use-case matrix

| Use case | TX scope | Includes | Must not include |
|----------|----------|----------|------------------|
| Register / Login | Short | User/session write | — |
| CreateOrganization | Single | Organization + GrantMembership | External email |
| CreateProduct | Single | Product + outbox | — |
| InitializeStock / AdjustStock | Single | Idempotency claim + StockItem + outbox | — |
| CreateOrder / AddLine | Single | Order + outbox | Stock |
| **ConfirmOrder** | **Single** | Inventory reserve+commit + Order.confirm + outbox + audit | Payment provider |
| CancelOrder | Single | Inventory release/restock + Order.cancel + outbox | — |
| InitiatePayment | Single | Payment + outbox | Provider capture |
| **CompletePayment** | **Single** | Payment.succeed + MarkOrderPaid + outbox | Provider HTTP |
| FailPayment / Refund | Single | Payment state + optional Sales command | — |
| CreateShipment | Single | Shipment + outbox after shippable query | Stock |
| Ship / Deliver / Cancel shipment | Single | Shipment + outbox | — |
| Outbox publish job | Per batch/message | Mark processed_at | Business aggregates |

---

## ConfirmOrder (detail)

**Current (VERIFIED):** `ConfirmOrderHandler` uses `DatabaseManager::transaction`, calls `stock->reserveForOrder` then `commitForOrder`, then `order->confirm`, `orders->save`, audit.

**Target (PROPOSED):** Same TX boundary; stock operations go through Inventory application service that loads/saves StockItem and records events inside that TX.

Failure: full rollback → no partial reserve without order confirm.

---

## CompletePayment (detail)

**Current (VERIFIED):** TX wraps payment save + CommandBus MarkOrderPaid.

**Target:** Same; MarkOrderPaid must no-op or reject illegal order states without corrupting payment success record — define: if order cannot be marked paid, **PROPOSED** fail the TX (payment remain pending) or compensate (**UNKNOWN** product choice). Prefer fail TX for consistency in monolith.

---

## Idempotency vs TX

Claim idempotency key **before** or **inside** TX carefully:
- If claim committed outside and business TX rolls back → stuck `processing` (**VERIFIED risk**).
- **PROPOSED:** claim row in same TX as business work, or use “processing” with TTL sweeper (TASK-005).

---

## Concurrency

- Optimistic version on aggregate save (VERIFIED columns).
- Multi-line reserve: process lines in stable product_id order to reduce deadlock (**PROPOSED**).
