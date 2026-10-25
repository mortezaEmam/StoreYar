<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Inventory\Presentation\Http\Controllers\StockReceiveController;

Route::post('/receive', [StockReceiveController::class, 'receive']);
Route::get('/movements', [StockReceiveController::class, 'movements']);
Route::get('/by-warehouse', [StockReceiveController::class, 'byWarehouse']);
