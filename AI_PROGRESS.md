# AI_PROGRESS

## Status (2026-10-24)

| Area | Status |
|------|--------|
| Modular DDD backend | Done |
| Tenant isolation tests | Done |
| Outbox / idempotency | Done |
| OpenAPI ↔ routes | Done (incl. forgot/reset, product price/rename, business ping) |
| Password reset API | Done |
| X-Request-Id middleware | Done |
| Mail password-reset notifier | Done (testing → Array; else Mail) |
| Nuxt admin UI | Done |
| i18n en/fa + RTL | Done (auth + commerce + members/audit) |
| Root hygiene | Apply if still polluted |

## Verified tests (local)

- `tests/Feature/Modules/Identity/PasswordResetTest`
- `tests/Feature/Shared/RequestIdMiddlewareTest`
- `tests/Contract/OpenApiContractTest`

## Runbook

```bash
# API
composer install && cp .env.example .env && php artisan key:generate
# set DB_* MAIL_* FRONTEND_URL in .env
php artisan migrate
php artisan serve

# UI
cd frontend && npm install --legacy-peer-deps
export NUXT_PUBLIC_API_BASE=http://localhost:8000/api
npm run dev
```

## Next backlog (priority)

1. **P2** — Real SMTP in staging; never log reset tokens outside local/testing
2. **P2** — Expand `t()` only where new UI strings appear
3. **P2** — Frontend CI: `npm run build` on every PR (if not already on branch)
4. **P3** — Browser E2E (Playwright) for login → order happy path
5. **P3** — Product categories / richer RBAC when product requires

## Rules

- New HTTP route ⇒ update `openapi/openapi.yaml` + contract test same change
- Do not commit `*.patch` / agent artifacts at repo root
- Identity aggregate uses `userId()`, not `id()`
