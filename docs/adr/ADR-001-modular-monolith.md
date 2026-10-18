# ADR-001 Modular Monolith

- **Status:** Accepted
- **Context:** StoreYar is a multi-tenant commerce system with multiple modules already in one Laravel app (Phase 0 VERIFIED). Team size and ops model do not require independent deployables.
- **Decision:** Remain a single deployable modular monolith with DDD module boundaries.
- **Alternatives:** Microservices per context; modular monolith (chosen); classic layered Laravel without modules.
- **Consequences:** Sync application calls and shared DB transactions allowed across contexts; must enforce module boundaries by convention and tests.
- **Security impact:** Single trust boundary for server code; tenant isolation still mandatory in app layer.
- **Data integrity impact:** Enables atomic ConfirmOrder across Sales+Inventory.
- **Migration impact:** None — confirms current shape.
