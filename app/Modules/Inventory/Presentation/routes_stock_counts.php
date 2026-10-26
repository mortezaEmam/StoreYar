<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Inventory\Presentation\Http\Controllers\StockCountController;

Route::get('/', [StockCountController::class, 'index']);
Route::post('/', [StockCountController::class, 'store']);
Route::get('/{id}', [StockCountController::class, 'show']);
Route::post('/{id}/post', [StockCountController::class, 'post']);
