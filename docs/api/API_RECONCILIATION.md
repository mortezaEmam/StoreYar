# API Reconciliation

Last updated: Phase OpenAPI sync (routes ↔ `openapi/openapi.yaml`).

## Source of truth

1. **Executable routes** under `routes/api.php` + module `Presentation/routes.php`
2. **OpenAPI** `openapi/openapi.yaml` — must document every public HTTP operation
3. **Contract tests** `tests/Contract/OpenApiContractTest.php` — fail CI if critical paths missing

## Gaps closed in this pass

| Code route | OpenAPI before | After |
|------------|----------------|-------|
| `PATCH /products/{id}` (rename) | Missing | Documented |
| `PATCH /products/{id}/price` | Missing | Documented |
| `GET /business/ping` | Missing | Documented |

## Still intentional / not public product surface

- Internal artisan commands, queue workers, outbox processor — not REST paths
- Laravel Vite/Blade assets — not under `/api`

## Verification

```bash
php artisan test tests/Contract
```

When adding a route: update OpenAPI in the same change, then extend the contract data provider.
