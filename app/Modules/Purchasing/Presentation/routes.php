<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Purchasing\Presentation\Http\Controllers\PurchaseOrderController;

Route::get('/', [PurchaseOrderController::class, 'index']);
Route::post('/', [PurchaseOrderController::class, 'store']);
Route::get('/{id}', [PurchaseOrderController::class, 'show']);
Route::post('/{id}/receive', [PurchaseOrderController::class, 'receive']);
