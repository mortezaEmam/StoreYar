# StoreYar

Multi-tenant commerce modular monolith (Laravel + DDD / CQRS).

## Stack

- PHP 8.4+, Laravel 13
- Modular contexts: Identity, Organization, Authorization, Catalog, Inventory, Sales, Payments, Shipping
- Transactional outbox (`shared_outbox`), idempotency keys, optimistic concurrency
- Tenant isolation via `X-Business-Id` + organization membership

## Quick start

```bash
composer install
cp .env.example .env
php artisan key:generate
# configure DB in .env
php artisan migrate
php artisan test
```

API base path: `/api`  
OpenAPI contract: [`openapi/openapi.yaml`](openapi/openapi.yaml)

## Documentation

| Doc | Purpose |
|-----|---------|
| [`docs/architecture/`](docs/architecture/) | Architecture audit, bounded contexts, target design |
| [`docs/domain/DOMAIN_MODEL.md`](docs/domain/DOMAIN_MODEL.md) | Domain model |
| [`docs/ROADMAP.md`](docs/ROADMAP.md) | Implementation roadmap |
| [`docs/adr/`](docs/adr/) | Architecture decision records |
| [`AI_PROGRESS.md`](AI_PROGRESS.md) | Agent progress log (if present) |

## Auth & tenancy

1. `POST /api/auth/register` or `/api/auth/login` → bearer token  
2. Create/select organization  
3. Send `Authorization: Bearer …` and `X-Business-Id: <organization ULID>` on business routes  
4. Mutating POSTs: `Idempotency-Key` header where required by OpenAPI  

## Frontend

- Default Laravel Vite/Blade is **not** the product UI  
- SPA target: Nuxt scaffold under [`frontend/`](frontend/) (TASK-014)

## License

Proprietary / project-defined. Laravel framework components remain under MIT.
