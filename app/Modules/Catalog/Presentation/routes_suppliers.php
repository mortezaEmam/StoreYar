<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Catalog\Presentation\Http\Controllers\SupplierController;

Route::get('/', [SupplierController::class, 'index']);
Route::post('/', [SupplierController::class, 'store']);
