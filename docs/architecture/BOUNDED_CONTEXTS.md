# Bounded Contexts — Current + Target (Phase 1)

Evidence base: Phase 0 audit commit `952856a6`.  
**VERIFIED** = code today · **PROPOSED** = target · **UNKNOWN** = open decision

---

## Identity

| Field | Content |
|-------|---------|
| **Business purpose** | Authenticate people and issue sessions |
| **Responsibilities** | Register user, credentials, login/logout, session rotate/revoke, current user |
| **Non-responsibilities** | Organization membership, roles, business catalog |
| **Owned aggregates** | User (root), Session (entity) |
| **Owned tables** | `identity_users`, `identity_user_credentials`, `identity_sessions` |
| **Public application contracts** | Commands: CreateUser, SetPassword, AuthenticateUser, RevokeSession, RevokeAllUserSessions, RotateSession; Queries: GetUserById, ValidateSession |
| **Inbound commands** | HTTP `/api/auth/*` |
| **Outbound events** | UserCreated, UserActivated, UserSuspended (**VERIFIED** types; outbox wiring **UNKNOWN**/absent) |
| **Dependencies** | Shared only |
| **Consistency** | Strong within user/session |
| **Authorization rules** | Public register/login; session required for logout/rotate/me |
| **Current implementation** | **VERIFIED** full module |
| **Target design** | Wire domain events to outbox if other contexts need them; keep session model |
| **Migration steps** | Optional outbox on UserRepository; remove legacy `users` table later |

---

## Organization

| Field | Content |
|-------|---------|
| **Business purpose** | Define the tenant (business) and optional branches |
| **Responsibilities** | Create/rename/suspend/activate organization; create branches |
| **Non-responsibilities** | Product catalog, stock, billing plans |
| **Owned aggregates** | Organization, Branch |
| **Owned tables** | `organization_organizations`, `organization_branches` |
| **Public contracts** | CreateOrganization, Rename, Suspend, Activate, CreateBranch; queries list/show |
| **Inbound** | `/api/organizations/*` |
| **Outbound events** | OrganizationCreated (**VERIFIED** at least) |
| **Dependencies** | On create: Authorization GrantMembership (**VERIFIED**); Identity for auth |
| **Consistency** | Strong; membership grant in same use-case transaction **PROPOSED** explicit |
| **Authorization** | Authenticated create; member for read; owner for rename/suspend/activate |
| **Current** | **VERIFIED** |
| **Target** | Keep; ensure create org + owner membership is one TX |
| **Migration** | Document TX; avoid leaking Authorization domain into Organization domain (call Application command) |

---

## Authorization

| Field | Content |
|-------|---------|
| **Business purpose** | Who may act inside an organization |
| **Responsibilities** | Grant/change/revoke membership; role checks |
| **Non-responsibilities** | Login, product permissions matrix beyond role |
| **Owned aggregates** | Membership |
| **Owned tables** | `authorization_memberships` |
| **Public contracts** | GrantMembership, ChangeMemberRole, RevokeMembership; GetMembership, ListMembers |
| **Inbound** | Nested under organizations routes |
| **Outbound events** | **UNKNOWN** / none observed |
| **Dependencies** | Identity (user id), Organization (exists) |
| **Consistency** | Unique (org,user); cannot remove last owner; cannot revoke self (**VERIFIED**) |
| **Authorization** | owner/admin for grant; owner for role change/revoke |
| **Current** | **VERIFIED** middleware `org.member` |
| **Target** | Keep as context; optional fine-grained Permission later without merging into Identity |
| **Migration** | None urgent |

---

## Catalog

| Field | Content |
|-------|---------|
| **Business purpose** | Sellable products for an organization |
| **Responsibilities** | Product identity, SKU, name, list price, activate/archive |
| **Non-responsibilities** | Stock quantity, orders |
| **Owned aggregates** | Product |
| **Owned tables** | `catalog_products` |
| **Public contracts** | Create/Rename/ChangePrice/Activate/Archive; Get/List |
| **Inbound** | `/api/products/*` |
| **Outbound events** | ProductCreated (+ **PROPOSED** ProductPriceChanged, ProductArchived as integration if needed) |
| **Dependencies** | None on other BCs |
| **Consistency** | Unique (org, sku) |
| **Authorization** | org.member |
| **Current** | **VERIFIED** + outbox on save |
| **Target** | Snapshot pricing for Sales remains Sales’ responsibility at line add |
| **Migration** | None for core; emit richer events if consumers appear |

---

## Inventory

| Field | Content |
|-------|---------|
| **Business purpose** | Quantity available to sell and audit of stock changes |
| **Responsibilities** | Initialize stock, adjust, reserve, release, commit; expose available/on-hand/reserved; **PROPOSED** movement history |
| **Non-responsibilities** | Order lifecycle, payments, shipping labels |
| **Owned aggregates** | StockItem (balance); **PROPOSED** StockMovement (append-only); optional StockReservation if lifecycle demands |
| **Owned tables** | `inventory_stock_items`; **PROPOSED** `inventory_stock_movements` |
| **Public application contracts (PROPOSED)** | InitializeStock, AdjustStock, ReserveAndCommitForOrder, ReleaseForOrder, RestockForOrder; Queries: GetStockByProduct, ListStock |
| **Inbound commands** | HTTP stock init/adjust; **order-driven stock only via application API from Sales** |
| **Outbound events** | StockInitialized, StockAdjusted (**VERIFIED**); **PROPOSED** StockReserved, StockReleased, StockCommitted |
| **Dependencies** | Catalog existence check (query port) |
| **Consistency** | available ≥ 0; reserved ≥ 0; reserved ≤ on_hand; optimistic version |
| **Authorization** | org.member for HTTP; internal API trusted only inside monolith application layer with org id |
| **Current** | Balance fields; Sales mutates domain directly (**violation**) |
| **Target** | Sales → Inventory Application only; events on all mutations; incremental ledger |
| **Migration** | (1) events on reserve/release/commit (2) application commands (3) Sales adapter (4) optional movements table |

### Available quantity formula

**Current (VERIFIED):** after reserve, `quantity` holds remaining available; `reserved_quantity` holds reserved.  
**Target (PROPOSED explicit):**
```
available = on_hand - reserved
```
with `on_hand` and `reserved` as projection fields updated only inside Inventory aggregates, reconcilable from movements when ledger exists.

---

## Sales

| Field | Content |
|-------|---------|
| **Business purpose** | Customer orders and their commercial state |
| **Responsibilities** | Draft order, lines with price snapshot, confirm, cancel, mark paid/revert paid |
| **Non-responsibilities** | Mutating stock tables, charging cards, printing labels |
| **Owned aggregates** | Order + OrderLine |
| **Owned tables** | `sales_orders`, `sales_order_lines` |
| **Public contracts** | CreateOrder, AddOrderLine, ConfirmOrder, CancelOrder, MarkOrderPaid, RevertOrderPayment; queries list/show |
| **Inbound** | `/api/orders/*`; MarkOrderPaid from Payments |
| **Outbound events** | OrderCreated, OrderLineAdded, OrderConfirmed, OrderPaid, OrderPaymentReverted |
| **Dependencies** | Catalog (pricing/active query); Inventory (reserve API); Identity (customer user id) |
| **Consistency** | Confirm requires successful Inventory reserve+commit in same TX (**VERIFIED** intent) |
| **Authorization** | org.member; customer_user_id from session on create |
| **Current** | Full module; stock via InventoryStockReservationService domain leak |
| **Target** | Port-only Inventory access; keep sync confirm |
| **Migration** | Replace service implementation to call Inventory handlers |

---

## Payments

| Field | Content |
|-------|---------|
| **Business purpose** | Record payment attempts against an order |
| **Responsibilities** | Initiate, succeed, fail, refund payment records |
| **Non-responsibilities** | Order aggregate rules beyond “mark paid” command; inventory |
| **Owned aggregates** | Payment |
| **Owned tables** | `payments_payments` |
| **Public contracts** | InitiatePayment, CompletePayment, FailPayment, RefundPayment |
| **Inbound** | `/api/payments/*` |
| **Outbound events** | PaymentInitiated, PaymentSucceeded, PaymentFailed, PaymentRefunded |
| **Dependencies** | Sales payable query + MarkOrderPaid command |
| **Consistency** | Payment success does not invent order state; MarkOrderPaid enforces order status (**PROPOSED** strengthen) |
| **Authorization** | org.member |
| **Current** | No external PSP (**VERIFIED** absence) |
| **Target** | Keep manual succeed/fail; add webhook port when provider chosen |
| **Migration** | Guard MarkOrderPaid; optional unique constraint policy **UNKNOWN** |

---

## Shipping

| Field | Content |
|-------|---------|
| **Business purpose** | Fulfillment shipment for an order |
| **Responsibilities** | Create shipment, ship, deliver, cancel |
| **Non-responsibilities** | Stock deduction, payment capture |
| **Owned aggregates** | Shipment |
| **Owned tables** | `shipping_shipments` |
| **Public contracts** | CreateShipment, ShipShipment, DeliverShipment, CancelShipment |
| **Inbound** | `/api/shipments/*` |
| **Outbound events** | ShipmentCreated, Shipped, Delivered, Cancelled |
| **Dependencies** | Sales IsShippableOrder query |
| **Consistency** | Unique (org, order); partial ship **UNKNOWN** → default unsupported |
| **Authorization** | org.member |
| **Current** | **VERIFIED** gateway read-only to Sales |
| **Target** | Keep; never write Inventory/Payments |
| **Migration** | Document partial-ship decision before schema change |

---

## Shared (kernel, not a BC)

| Allowed | Not allowed |
|---------|-------------|
| Clock, IdGenerator, AggregateRoot/Entity bases, DomainEvent/IntegrationEvent contracts, Result/Error, Outbox/Inbox/Idempotency stores, BusinessContext, LockManager, generic AuditLogger, Money/Quantity primitives | Order/Stock/Payment business rules, cross-module Eloquent models |

---

## Scaffold folders

Not bounded contexts. **PROPOSED:** quarantine or delete empty modules (Payment singular, Accounting, …) after architecture tests still pass.
