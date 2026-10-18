# Cross-Context Dependency Audit

Scan: PHP `use StoreYar\Modules\{Other}` where Other ≠ owning module.

## Inventory of dependencies

| From | To | Location | Mechanism | Classification |
|------|----|----------|-----------|----------------|
| Organization | Authorization | CreateOrganizationHandler, OrganizationServiceProvider, routes | Dispatches/registers membership grant; routes import MembershipController | **CONDITIONAL** (composition at application boundary; acceptable if via command/API) |
| Organization | Identity | OrganizationController, routes | Auth middleware / user context | **ALLOWED** (Presentation) |
| Authorization | Identity | MembershipController, EnsureOrganizationMembership | Session attributes, user id | **ALLOWED** |
| Authorization | Organization | EnsureOrganizationMembership | Loads organization by id | **CONDITIONAL** — reads org aggregate via repository from another module |
| Inventory | Catalog | CatalogProductExistenceChecker | Infrastructure implements Domain ProductExistenceChecker using Catalog repo/query | **ALLOWED** (anti-corruption adapter) if only uses Catalog application/query API; **VIOLATION** if it reaches Catalog Eloquent models directly — **verify:** uses Catalog domain/application |
| Sales | Catalog | CatalogProductCatalogGateway | Infrastructure gateway | **ALLOWED** (port/adapter) |
| Sales | Inventory | InventoryStockReservationService | **Directly uses Inventory `StockItem` aggregate + `StockItemRepository`** | **ARCHITECTURAL VIOLATION (P1)** — Sales Infrastructure mutates Inventory domain aggregate and persistence |
| Sales | Identity | OrderController | customer user from session | **ALLOWED** |
| Payments | Sales | CompletePaymentHandler, RefundPaymentHandler, SalesPayableOrderGateway | CommandBus MarkOrderPaid; gateway | **CONDITIONAL** — CommandBus is acceptable application integration; gateway OK |
| Shipping | Sales | SalesShippableOrderGateway | Read order shippability | **ALLOWED** (read port) |

## Shared usage

All modules may depend on `StoreYar\Shared\*`. Shared must not depend on Modules (architecture tests enforce Domain; Application/Infrastructure of Shared should not import Modules — ListAuditLog is in Shared).

## Forbidden pattern observed

```
Sales.Infrastructure
  → Inventory.Domain.Aggregates.StockItem
  → Inventory.Domain.Contracts.StockItemRepository
  → save() Inventory persistence
```

Target:

```
Sales.Application → Inventory.Application (command/API)
  or Integration Event → Inventory consumer
```

## Cross-context DB FKs

Minimal hard FKs (see DATABASE_MODEL). Logical IDs only — **ALLOWED** for modular monolith if ownership clear; risk of orphans.

## Eloquent cross-reads

UNKNOWN without full grep of Model::query across modules — InventoryStockReservationService is the critical confirmed case of cross-context **write**.

## Domain event coupling

No verified event consumers in other modules reacting to outbox events (publisher is Log/Queue). Cross-context reactions today are **synchronous command/gateway calls**, not eventual consistency via outbox.
