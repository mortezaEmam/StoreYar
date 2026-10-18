# ADR-003 Tenant Isolation

- **Status:** Accepted
- **Context:** Multi-tenant via organization_id and X-Business-Id; membership middleware VERIFIED; no RLS.
- **Decision:** Application-level isolation: authenticate → validate membership for requested business → bind BusinessContext → all queries/commands filter by organization_id. Client header is a request parameter, not authorization proof. CI tests for cross-tenant denial (TASK-001).
- **Alternatives:** DB RLS; one DB per tenant; trust header only.
- **Consequences:** Discipline in handlers required; tests are mandatory safety net.
- **Security impact:** Primary control against tenant escape.
- **Data integrity impact:** Indirect — prevents cross-tenant mutation.
- **Migration impact:** Add feature tests; fix any failing handlers.
