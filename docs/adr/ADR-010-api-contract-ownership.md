# ADR-010 API Contract Ownership

- **Status:** Proposed
- **Context:** openapi/openapi.yaml exists; routes implemented; possible field drift.
- **Decision:** OpenAPI is the candidate public contract. Implementation and Feature tests must not silently diverge; add contract tests (TASK-012). Backend owns business rules; clients consume API.
- **Alternatives:** Code-first only; separate external gateway schema.
- **Migration impact:** Reconcile undocumented endpoints (e.g. business/ping).
