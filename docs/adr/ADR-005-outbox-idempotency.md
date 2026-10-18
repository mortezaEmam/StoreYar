# ADR-005 Outbox and Consumer Idempotency

- **Status:** Proposed
- **Context:** Module repos write `shared_outbox`; parallel `integration_*` tables exist; publisher logs; no business consumers; reserve path silent.
- **Decision:** Single outbox schema (unify); domain events for all stock mutations; at-least-once publish; consumers idempotent by event_id via inbox when introduced. Do not create a third implementation.
- **Alternatives:** Drop outbox; dual-write forever; message broker only.
- **Consequences:** Migration of table shape; publisher config clarity.
- **Security impact:** business_id on outbox rows aids tenant-scoped processing.
- **Data integrity impact:** Reliable publication after commit.
- **Migration impact:** TASK-002, TASK-004.
