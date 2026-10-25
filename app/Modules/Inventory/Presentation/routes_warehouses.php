<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Inventory\Presentation\Http\Controllers\WarehouseController;

Route::get('/', [WarehouseController::class, 'index']);
Route::post('/', [WarehouseController::class, 'store']);
