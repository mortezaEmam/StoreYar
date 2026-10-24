# AI_PROGRESS

## Phase status

| Phase | Status |
|-------|--------|
| Phase 0 — Architecture audit | Done |
| Phase 0b — Baseline hygiene | Apply locally if not pushed |
| Phase 1 — Frontend CI build | Apply locally if not pushed |
| OpenAPI ↔ routes reconciliation | **Done (this change)** |
| TASK-001 … TASK-014 | Done (git history) |

## Completed recently

- Documented `PATCH /products/{id}`, `PATCH /products/{id}/price`, `GET /business/ping` in OpenAPI
- Expanded `OpenApiContractTest` critical path matrix + PHPUnit 12 `#[DataProvider]`

## Next (priority)

1. **P1** — Ensure Phase 0b hygiene + CI frontend build are on the branch you deploy
2. **P2** — Password reset / email verification (product decision)
3. **P2** — i18n fa/en + RTL foundation
4. **P2** — Request correlation id on API responses/logs

## Rules

- New HTTP route ⇒ same PR updates `openapi/openapi.yaml` + contract test
- Do not reintroduce root artifact pollution
