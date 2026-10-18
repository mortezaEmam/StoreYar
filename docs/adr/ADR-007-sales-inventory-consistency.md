# ADR-007 Sales / Inventory Consistency

- **Status:** Accepted (pattern) / Proposed (implementation cleanup)
- **Context:** ConfirmOrder already reserve+commits stock in one DB transaction (VERIFIED).
- **Decision:** Keep **immediate consistency** for confirm/cancel stock effects via Inventory application API inside the same transaction. Do not switch confirm to eventual messaging.
- **Alternatives:** Eventual reserve via outbox (rejected for oversell risk in monolith).
- **Consequences:** Sales depends on Inventory application availability in-process (acceptable).
- **Data integrity impact:** Prevents oversell at confirm.
- **Migration impact:** TASK-003 only changes call path, not consistency model.
