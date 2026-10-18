# ADR-006 Inventory Ledger and Concurrency

- **Status:** Proposed
- **Context:** StockItem balance only; reserve/commit without events; optimistic version exists.
- **Decision:** Phase A — emit domain events on reserve/release/commit; keep balance fields. Phase B — optional append-only `inventory_stock_movements` dual-written, reconcilable with balances. Concurrency: optimistic version; ordered multi-line locks if tests show races. available = on_hand - reserved (explicit).
- **Alternatives:** Event-sourced inventory only; ignore ledger.
- **Consequences:** Incremental migration; no big-bang rewrite of stock.
- **Security impact:** Low.
- **Data integrity impact:** High — audit trail for stock.
- **Migration impact:** TASK-002 then TASK-009.
