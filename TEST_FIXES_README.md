# Test fixes (after first patch)

Copy these three files over the ones in your repo:

1. tests/Security/TenantIsolationTest.php
   - Disables ThrottleRequests middleware (fixes HTTP 429 on login)

2. tests/Unit/Modules/Inventory/Domain/Aggregates/StockItemTest.php
   - Discards StockInitialized before asserting StockAdjusted

3. tests/Architecture/ArchitectureBaselineTest.php
   - Allowlists pre-existing Shared Application DB usage in OutboxProcessor and ListAuditLogHandler

```bash
# from repo root, after placing the three files:
php artisan test tests/Security/TenantIsolationTest.php
php artisan test tests/Unit/Modules/Inventory
php artisan test tests/Architecture
```
