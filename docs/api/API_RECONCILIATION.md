# API Reconciliation (OpenAPI vs code)

OpenAPI: `openapi/openapi.yaml` (title StoreYar API 1.0.0).

## Method

Compared tags/paths to `routes/api.php` + module `routes.php` + controllers. Full field-level payload diff is partial without running the app; status reflects structural presence.

| Area | OpenAPI | Routes | Implementation | Tests | Status |
|------|---------|--------|----------------|-------|--------|
| Auth register/login/logout/rotate/me | Yes | Yes | AuthController | Feature/Unit Identity | IMPLEMENTED |
| Organizations CRUD-ish + branches | Yes | Yes | OrganizationController | Partial | IMPLEMENTED / PARTIAL tests |
| Members | Yes | Yes | MembershipController | Partial | IMPLEMENTED |
| Products | Yes | Yes | ProductController | Feature Catalog | IMPLEMENTED |
| Stock | Yes | Yes | StockController | Feature Inventory | IMPLEMENTED |
| Orders | Yes | Yes | OrderController | Feature Sales | IMPLEMENTED |
| Payments | Yes | Yes | PaymentController | Feature (from history) | IMPLEMENTED |
| Shipments | Yes | Yes | ShipmentController | Feature (from history) | IMPLEMENTED |
| Audit log | Yes | Yes | AuditLogController | Partial | IMPLEMENTED |
| business/ping | No | Yes | Closure in api.php | UNKNOWN | IMPLEMENTED_BUT_UNDOCUMENTED |
| Idempotency-Key | Documented | Enforced in selected services | Yes | Feature idempotency tests | PARTIAL (not all POST endpoints) |
| X-Business-Id | Documented | Middleware | Yes | Context tests | IMPLEMENTED |

## Contract risks

- OpenAPI may lag response shapes vs API Resources.
- Undocumented ping endpoint.
- Error envelope consistency: JSON on api/* via exception renderer; domain exceptions mapping **UNKNOWN** completeness.

## Classification summary

Most documented commerce endpoints are **IMPLEMENTED**. Treat OpenAPI as candidate contract, not guarantee of field-level accuracy until response schema tests exist.
