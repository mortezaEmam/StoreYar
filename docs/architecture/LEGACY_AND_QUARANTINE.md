# Legacy User & Quarantined Modules (TASK-007 / TASK-008)

## Legacy `App\Models\User`

- **Not** used by StoreYar API authentication (Identity sessions + `identity_users`).
- Marked `@deprecated` on the class.
- `DatabaseSeeder` no longer seeds this model.
- Table `users` remains (Laravel default migration) for framework compatibility and generic DB transaction tests.
- Do not add new domain logic against `App\Models\User`.

## Removed empty module scaffolds (TASK-008)

Directories that contained only `.gitkeep` layer placeholders were removed:

Accounting, Audit, Billing, Configuration, Customer, FileManagement,
ImportExport, Integration, Marketing, Messaging, Notification,
Observability, Offline, Payment (singular — real code is **Payments**),
Pricing, Purchasing, Reporting, Search.

## Active modules

Authorization, Catalog, Identity, Inventory, Organization, Payments, Sales, Shipping

Reintroduce a module only when real Domain/Application code lands.
