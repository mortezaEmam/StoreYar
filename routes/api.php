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
    ->prefix('stock')
    ->group(base_path('app/Modules/Inventory/Presentation/routes.php'));

Route::middleware(['auth.session', 'business.context', 'org.member'])
    ->prefix('orders')
    ->group(base_path('app/Modules/Sales/Presentation/routes.php'));
