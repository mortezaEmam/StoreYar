# ADR-002 Bounded Context Ownership

- **Status:** Accepted
- **Context:** Eight active modules with clear tables; Sales currently writes Inventory domain (violation).
- **Decision:** Each context owns its aggregates and tables. Cross-context writes only via published Application contracts or integration events. Consumers define ports; providers implement application APIs.
- **Alternatives:** Shared database models across modules; unrestricted Eloquent access.
- **Consequences:** Adapters in Infrastructure; slightly more code; safer evolution.
- **Security impact:** Clearer audit of who mutates stock/money.
- **Data integrity impact:** Prevents accidental cross-table writes.
- **Migration impact:** Refactor InventoryStockReservationService to call Inventory application (TASK-003).
