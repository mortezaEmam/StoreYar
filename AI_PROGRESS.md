# AI_PROGRESS

## Product baseline — complete (2026-10-24)

### Backend
- Modular DDD (Identity, Organization, Catalog, Inventory, Sales, Payments, Shipping)
- Tenant isolation, outbox, idempotency
- OpenAPI + contract tests
- Password reset + mail notifier
- X-Request-Id
- Product categories API

### Frontend (Nuxt 3)
- Auth (login/register/forgot/reset), business context
- Products (CRUD actions, category assign + filter)
- Stock, orders, payments, shipments, members, audit
- Categories page
- i18n en/fa + RTL
- Dashboard stats
- Audit filters + CSV export

### Ops
- CI: PHPUnit + frontend build
- `scripts/smoke-api.sh`
- docs: MAIL_SMTP, DEPLOY, OPS_CHECKLIST

## Verify locally

```bash
php artisan serve
cd frontend && NUXT_PUBLIC_API_BASE=http://127.0.0.1:8000/api npm run dev
bash scripts/smoke-api.sh
```

## Future (optional product backlog)

- Server-side audit pagination/filter
- Full Playwright login→order against seed data
- Multi-warehouse / discounts
- Observability metrics dashboard
