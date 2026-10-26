<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Shared\Application\Context\BusinessContext;

Route::prefix('auth')->group(base_path(
    'app/Modules/Identity/Presentation/routes.php'
));


Route::prefix('organizations')->group(base_path(
    'app/Modules/Organization/Presentation/routes.php'
));

Route::middleware(['auth.session', 'business.context'])->group(function (): void {
    Route::get('/business/ping', function (BusinessContext $context) {
        return response()->json([
            'business_id' => $context->businessId(),
            'branch_id' => $context->branchId(),
        ]);
    });
});


Route::middleware(['auth.session', 'business.context', 'org.member'])
    ->prefix('products')
    ->group(base_path('app/Modules/Catalog/Presentation/routes.php'));

Route::middleware(['auth.session', 'business.context', 'org.member'])
    ->prefix('categories')
    ->group(base_path('app/Modules/Catalog/Presentation/routes_categories.php'));

Route::middleware(['auth.session', 'business.context', 'org.member'])
    ->prefix('stock')
    ->group(base_path('app/Modules/Inventory/Presentation/routes.php'));

Route::middleware(['auth.session', 'business.context', 'org.member'])
    ->prefix('orders')
    ->group(base_path('app/Modules/Sales/Presentation/routes.php'));



Route::middleware(['auth.session', 'business.context', 'org.member'])
    ->prefix('payments')
    ->group(base_path('app/Modules/Payments/Presentation/routes.php'));


Route::middleware(['auth.session', 'business.context', 'org.member'])
    ->prefix('shipments')
    ->group(base_path('app/Modules/Shipping/Presentation/routes.php'));


Route::middleware(['auth.session', 'business.context', 'org.member:owner,admin'])
    ->get('/audit-log', [\StoreYar\Shared\Presentation\Http\Controllers\AuditLogController::class, 'index']);



Route::middleware(['auth.session', 'business.context', 'org.member'])
    ->prefix('warehouses')
    ->group(base_path('app/Modules/Inventory/Presentation/routes_warehouses.php'));

Route::middleware(['auth.session', 'business.context', 'org.member'])
    ->prefix('customers')
    ->group(base_path('app/Modules/Catalog/Presentation/routes_customers.php'));

Route::middleware(['auth.session', 'business.context', 'org.member'])
    ->prefix('units')
    ->group(base_path('app/Modules/Catalog/Presentation/routes_units.php'));

Route::middleware(['auth.session', 'business.context', 'org.member'])
    ->prefix('brands')
    ->group(base_path('app/Modules/Catalog/Presentation/routes_brands.php'));

Route::middleware(['auth.session', 'business.context', 'org.member'])
    ->prefix('barcodes')
    ->group(base_path('app/Modules/Catalog/Presentation/routes_barcodes.php'));

Route::middleware(['auth.session', 'business.context', 'org.member'])
    ->prefix('products')
    ->group(base_path('app/Modules/Catalog/Presentation/routes_product_master.php'));

Route::middleware(['auth.session', 'business.context', 'org.member'])
    ->prefix('suppliers')
    ->group(base_path('app/Modules/Catalog/Presentation/routes_suppliers.php'));

Route::middleware(['auth.session', 'business.context', 'org.member'])
    ->prefix('stock')
    ->group(base_path('app/Modules/Inventory/Presentation/routes_stock_ops.php'));


Route::middleware(['auth.session', 'business.context', 'org.member', 'permission:pos.sale'])
    ->prefix('pos')
    ->group(base_path('app/Modules/Sales/Presentation/routes_pos.php'));


Route::middleware(['auth.session', 'business.context', 'org.member'])
    ->prefix('purchase-orders')
    ->group(base_path('app/Modules/Purchasing/Presentation/routes.php'));


Route::middleware(['auth.session', 'business.context', 'org.member'])
    ->prefix('accounting')
    ->group(base_path('app/Modules/Accounting/Presentation/routes.php'));


Route::middleware(['auth.session', 'business.context', 'org.member', 'permission:reports.view'])
    ->prefix('reports')
    ->group(base_path('app/Modules/Reporting/Presentation/routes.php'));


Route::middleware(['auth.session', 'business.context', 'org.member'])
    ->prefix('permissions')
    ->group(base_path('app/Modules/Authorization/Presentation/routes_permissions.php'));
