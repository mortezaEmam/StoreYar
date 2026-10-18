# Architecture Debt

## P0 — Data corruption / security

| ID | Issue | Evidence |
|----|-------|----------|
| P0-1 | Tenant isolation is application-only; any missed `organization_id` check is cross-tenant read/write | Pattern of manual checks; no global scope |
| P0-2 | Stock reserve/commit without domain events → audit/outbox blind spot on the path that changes stock for orders | StockItem::reserve/release/commitReserved no recordEvent |
| P0-3 | Confirm does reserve+commit immediately; concurrent confirms rely on version/locks — must verify repository uses optimistic lock on every save | version column present; concurrent test depth UNKNOWN |

## P1 — Critical architecture / transactions

| ID | Issue | Evidence |
|----|-------|----------|
| P1-1 | Sales Infrastructure mutates Inventory aggregate + repository directly | InventoryStockReservationService |
| P1-2 | Dual outbox tables (`shared_outbox` vs `integration_outbox_messages`) — operational confusion | migrations + EloquentOutbox vs integration stores |
| P1-3 | Idempotency claim can leave `processing` on failure | IdempotencyExecutor / module services |
| P1-4 | Identity/Organization domain events not clearly outbox-backed | no pullDomainEvents in those modules (grep) |
| P1-5 | InventoryServiceProvider registered twice | bootstrap/providers.php |
| P1-6 | shared_outbox lacks business_id / correlation metadata | migration |

## P2 — Design

| ID | Issue |
|----|-------|
| P2-1 | Empty scaffold modules (18) + duplicate Payment vs Payments |
| P2-2 | Legacy Laravel users/sessions tables and App\Models\User |
| P2-3 | Money VO duplicated (Shared Support vs Sales Domain) |
| P2-4 | No stock ledger / movement history |
| P2-5 | Synchronous cross-context commands instead of integration events for payment→order (works but couples) |
| P2-6 | Branch context unused by commerce tables |

## P3 — Maintainability

| ID | Issue |
|----|-------|
| P3-1 | README is stock Laravel; product docs only in Phase 0 docs |
| P3-2 | AGENTS/CLAUDE Boost instructions without Boost installed |
| P3-3 | Persian comments mixed in routes/middleware |
| P3-4 | OpenAPI vs response Resources field-level drift risk |

## P4 — Cosmetic

Formatting, typos in commit messages (“complate”), unused default Controller.
