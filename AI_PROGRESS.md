# AI_PROGRESS

## Baseline complete

Architecture, security tests, OpenAPI, password reset, correlation id, Nuxt + i18n,
CI frontend build, SMTP docs, Playwright smoke, API smoke script, deploy outline.

## Scripts

```bash
# API happy path
export API_BASE=http://localhost:8000/api
bash scripts/smoke-api.sh

# Frontend smoke (dev server up)
cd frontend && E2E_BASE_URL=http://localhost:3001 npm run test:e2e
```

## Suggested future work (product)

1. Full Playwright login→order against seeded API
2. Observability (structured logs + metrics)
3. Multi-warehouse / categories when required
4. Harden CORS and secrets per environment
