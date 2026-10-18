# Business Workflows (actual code paths)

## Identity

### Register
`POST /api/auth/register` → AuthController → CreateUser (+ SetPassword) via CommandBus → UserRepository save → JSON user/session as implemented.

### Login
`POST /api/auth/login` → AuthenticateUser → SessionRepository + token → Bearer token response.

### Logout / Rotate / Me
auth.session middleware → RevokeSession / RotateSession / GetUserById.

## Organization

### Create
auth.session → OrganizationController::store → CreateOrganizationHandler → saves Organization **and** grants owner membership (Authorization) → response.

### Rename / Suspend / Activate
org.member:owner → corresponding commands.

### Create Branch
org.member → CreateBranch.

### Membership grant/change/revoke
org.member:owner|admin → MembershipController → Authorization commands.

## Catalog

Middleware: auth.session, business.context, org.member.

CRUD-ish: CreateProduct, list, show, Rename, ChangePrice, Activate, Archive → handlers → ProductRepository (outbox on write).

## Inventory

### Initialize / Adjust
HTTP + Idempotency-Key (services) → InitializeStock / AdjustStock → StockItemRepository.

### Reserve / Release / Commit
**Not HTTP.** Invoked only from Sales `InventoryStockReservationService` inside Confirm/Cancel flows.

## Sales

### Create Order
POST /api/orders + Idempotency → CreateOrder (customer from session user).

### Add Line
POST …/lines → AddOrderLine (draft only); may resolve price via ProductCatalogGateway.

### Confirm
POST …/confirm + Idempotency → **DB transaction**:
1. Load order (org check)
2. stock.reserveForOrder(lines)
3. stock.commitForOrder(lines)  // immediate — no held reservation window
4. order.confirm()
5. orders.save (outbox order events)
6. audit log

Stock saves happen inside reservation service (separate save calls); still same DB transaction if connection shared.

### Cancel
CancelOrderHandler → may restock via restockForOrder / release depending on state → order cancel → save.

### Mark paid
Not public HTTP primarily; dispatched from Payments CompletePaymentHandler.

## Payments

### Initiate
POST /api/payments + Idempotency → validates order via gateway → Payment aggregate pending → save.

### Succeed
POST …/succeed → CompletePaymentHandler transaction: markSucceeded → save payment → CommandBus MarkOrderPaidCommand → audit.

### Fail / Refund
Similar; Refund may interact with Sales.

## Shipping

### Create
POST /api/shipments + Idempotency → ShippableOrderGateway must allow → unique org+order → save.

### Ship / Deliver / Cancel
State transitions on Shipment aggregate + outbox events.

## Audit

GET /api/audit-log → ListAuditLog query → shared_audit_log filtered by org (owner/admin).

## Notes

- No async saga: confirm stock is sync.
- Payment success does not auto-create shipment.
- OpenAPI may document additional fields; see API_RECONCILIATION.
