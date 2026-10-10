<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Inventory\Presentation\Http\Controllers\StockController;


Route::get('/', [StockController::class, 'index']);
Route::post('/', [StockController::class, 'store'])
    ->middleware('throttle:30,1');
Route::get('/{productId}', [StockController::class, 'show']);
Route::post('/{productId}/adjust', [StockController::class, 'adjust'])
    ->middleware('throttle:60,1');
