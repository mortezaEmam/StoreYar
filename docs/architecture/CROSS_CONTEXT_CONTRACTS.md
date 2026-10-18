# Cross-Context Contracts

## Policy (PROPOSED)

Prefer, in order:
1. **Synchronous application command/query** inside the modular monolith when an invariant requires immediate consistency (e.g. confirm order + stock).
2. **Read-only query port** for validation (product exists, order shippable).
3. **Integration event via Outbox** when another context reacts asynchronously and eventual consistency is acceptable.
4. **Process manager** only if multi-step long-running orchestration is required (not needed for current verified flows).

Forbidden:
- Context A imports Context B Domain aggregates or Infrastructure Eloquent models to write B’s data.

---

## Contract catalog

### Sales → Inventory

| Item | Detail |
|------|--------|
| **Current (VERIFIED)** | `InventoryStockReservationService` uses `StockItem` + `StockItemRepository` — **ARCHITECTURAL VIOLATION** |
| **Target (PROPOSED)** | Interface in Sales Domain: `StockReservationService` (already exists). Implementation calls **Inventory Application** commands/handlers only |
| **Operations** | `reserveForOrder`, `releaseForOrder`, `commitForOrder`, `restockForOrder` (VERIFIED interface); or collapse reserve+commit into `ReserveAndCommitForOrder` matching confirm behavior |
| **Consistency** | Immediate; same DB transaction as ConfirmOrder/CancelOrder |
| **Events** | Inventory emits domain events; not required for Sales to subscribe for confirm path |

### Sales → Catalog

| Item | Detail |
|------|--------|
| **Current** | `ProductCatalogGateway` / `CatalogProductCatalogGateway` |
| **Target** | Keep query port: get active product + unit price for line snapshot |
| **Consistency** | Sync read at add-line / create |

### Inventory → Catalog

| Item | Detail |
|------|--------|
| **Current** | `ProductExistenceChecker` / `CatalogProductExistenceChecker` |
| **Target** | Query-only port; no Catalog writes |

### Payments → Sales

| Item | Detail |
|------|--------|
| **Current** | `SalesPayableOrderGateway` + `CommandBus` → `MarkOrderPaidCommand` |
| **Target** | Keep; MarkOrderPaid enforces CONFIRMED |
| **Consistency** | Same TX as payment succeed (**VERIFIED** pattern) |
| **Alternative** | Integration event PaymentSucceeded → Sales consumer (optional later; not required) |

### Shipping → Sales

| Item | Detail |
|------|--------|
| **Current** | `ShippableOrderGateway` / `SalesShippableOrderGateway` |
| **Target** | Sync query only |

### Organization → Authorization

| Item | Detail |
|------|--------|
| **Current** | CreateOrganizationHandler grants membership |
| **Target** | Application-level orchestration; Authorization command API |

### Authorization → Organization / Identity

| Item | Detail |
|------|--------|
| **Current** | Middleware loads Organization; session user |
| **Target** | Allowed presentation/application reads |

---

## Integration events (when to use)

Publish integration events for: audit projections, search indexing, notifications, future analytics.  
**Do not** replace ConfirmOrder’s sync stock call with an eventual event (oversell risk).

---

## Ownership of ports

- Interface defined in **consuming** context Domain or Application Contracts.
- Implementation in **consuming** Infrastructure calling **providing** context Application (preferred) or a stable public facade.

Providing context never depends on consumer.
