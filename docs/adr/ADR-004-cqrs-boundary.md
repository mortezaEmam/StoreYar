# ADR-004 CQRS Boundary

- **Status:** Accepted
- **Context:** CommandBus/QueryBus already implemented and used (VERIFIED).
- **Decision:** Keep pragmatic CQRS: write via commands, read via queries; shared DB; no separate read models required initially.
- **Alternatives:** CRUD controllers only; full CQRS with projections.
- **Consequences:** Handler registration in providers continues.
- **Security / data / migration:** Low impact; preserve existing buses.
