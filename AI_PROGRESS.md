# AI_PROGRESS

## Done (through final-steps package)

- DDD modules, tenant isolation, outbox, idempotency
- OpenAPI reconciliation + contract tests
- Password reset + Mail notifier + Array in testing
- X-Request-Id
- Nuxt UI + i18n en/fa
- **CI:** backend suite + frontend `nuxi prepare` + `npm run build`
- **SMTP docs:** `docs/MAIL_SMTP.md`
- **Playwright smoke:** `frontend/e2e/smoke.spec.ts`

## Local verify

```bash
php artisan test tests/Feature/Modules/Identity/PasswordResetTest.php
php artisan test tests/Contract
cd frontend && npm run build
# optional: npm run test:e2e with dev server up
```

## Remaining product decisions (not blocking)

- Categories, discounts, multi-warehouse
- Full Playwright against live API (login → order) when stable seed data exists
