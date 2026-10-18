# StoreYar — Repository Map

**Source of truth:** repository at commit `952856a6dbf81e96bffdaee2a604d6ea35c7c33e` (master)  
**Generated:** Phase 0 discovery (code-only; README/AGENTS not trusted where they conflict)

## Control plane

| File | Role |
|------|------|
| `composer.json` / `composer.lock` | PHP ^8.3, Laravel ^13.17, PHPUnit ^12.5, Pint, no Laravel Boost package installed |
| `package.json` | Vite + axios + laravel-vite-plugin; no Vue/Nuxt |
| `vite.config.js` | Default Laravel Vite entry `resources/js/app.js`, `resources/css/app.css` |
| `phpunit.xml` | Suites: Unit, Feature; env testing; DB not forced in xml (CI sets MySQL) |
| `.env.example` / `.env.testing` | Standard Laravel + SQLite optional; CI overrides to MySQL |
| `.github/workflows/ci.yml` | PHP 8.4, MySQL 8.4, migrate, php -l, Architecture, Security, full test |
| `AGENTS.md` / `CLAUDE.md` | Laravel Boost bootstrap instructions only; Boost is **not** installed |
| `README.md` | Default Laravel skeleton README — **not** product documentation |

**Installed architectural tooling (from composer):** none beyond PHPUnit, Pint, Collision. No PHPStan/Psalm, no Pest, no Laravel Boost.

**Scripts:** `composer setup`, `composer test`, `composer dev` (artisan dev).

## Top-level tree (source)

```
app/
  Http/Controllers/Controller.php          # empty Laravel base
  Models/User.php                          # LEGACY default Laravel model (unused by Identity)
  Modules/                                 # bounded contexts
  Providers/AppServiceProvider.php
  Providers/SharedServiceProvider.php      # thin / possibly legacy
  Shared/                                  # shared kernel
bootstrap/app.php, providers.php
config/                                    # default Laravel config only
database/migrations/                       # 21 migrations
database/factories/UserFactory.php         # legacy
database/seeders/DatabaseSeeder.php
openapi/openapi.yaml
public/
resources/{css,js,views/welcome.blade.php}
routes/{api,web,console}.php
tests/{Architecture,Feature,Integration,Security,Unit}
storage/
```

## Module folders under `app/Modules`

| Folder | PHP files | Real code? |
|--------|-----------|------------|
| Identity | 54 | Yes |
| Organization | 39 | Yes |
| Authorization | 27 | Yes |
| Catalog | 30 | Yes |
| Inventory | 31 | Yes |
| Sales | 52 | Yes |
| Payments | 34 | Yes |
| Shipping | 38 | Yes |
| Accounting, Audit, Billing, Configuration, Customer, FileManagement, ImportExport, Integration, Marketing, Messaging, Notification, Observability, Offline, Payment, Pricing, Purchasing, Reporting, Search | 0 each | Scaffold only (layer dirs + `.gitkeep`) |

## Layer pattern (active modules)

```
Module/
  Domain/          Aggregates|Entities, VOs, Enums, Events, Exceptions, Contracts
  Application/     Commands/<Name>/, Queries/<Name>/, Services/ (idempotent wrappers)
  Infrastructure/  *ServiceProvider, Persistence/Eloquent, adapters to other modules
  Presentation/    Http/{Controllers,Requests,Resources,Middleware}, routes.php
```

## Shared kernel (`app/Shared`)

| Area | Path | Notes |
|------|------|-------|
| Domain | Aggregates/AggregateRoot, Entities/Entity, Events, ValueObjects (IDs/version), Contracts (Outbox, Clock, IdGenerator, IdempotencyStore, LockManager, AuditLogger), Errors, Exceptions | Framework-free (architecture tests) |
| Application | Bus/Command, Bus/Query, Context, Idempotency, Outbox, Authorization, Audit, Concurrency, Results/Result, Contracts | CQRS + cross-cutting |
| Infrastructure | Providers, Outbox, Messaging, Idempotency, Concurrency, Clock, Id, Audit, Persistence/Eloquent | Laravel-backed |
| Presentation | Console/PublishOutboxCommand, Http/Controllers/AuditLogController | |
| Support | Money, Quantity | |

## Autoload namespaces

- `App\` → `app/`
- `StoreYar\Shared\` → `app/Shared/`
- `StoreYar\Modules\` → `app/Modules/`
- `Tests\` → `tests/`

## Providers (`bootstrap/providers.php`)

Registered: App, Shared (App\Providers), SharedInfrastructure, Identity, Organization, Authorization, Catalog, **Inventory (twice)**, Sales, Payments, Shipping.

## Routes entry

`routes/api.php` groups module `Presentation/routes.php` under prefixes with middleware.  
`routes/web.php` → welcome only.  
`routes/console.php` → inspire + schedule `outbox:publish` every minute.

## Counts (approx.)

- ~409 PHP files under `app/`
- ~117 `*Test.php` files
- 21 migrations
