<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Catalog\Presentation\Http\Controllers\BarcodeController;

Route::get('/lookup', [BarcodeController::class, 'lookup']);
Route::post('/', [BarcodeController::class, 'store']);
Route::get('/product/{productId}', [BarcodeController::class, 'byProduct']);
