<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Shipping\Presentation\Http\Controllers\ShipmentController;

Route::get('/', [ShipmentController::class, 'index']);
Route::post('/', [ShipmentController::class, 'store'])->middleware('throttle:30,1');
Route::get('/{id}', [ShipmentController::class, 'show']);
Route::post('/{id}/ship', [ShipmentController::class, 'ship'])->middleware('throttle:30,1');
Route::post('/{id}/deliver', [ShipmentController::class, 'deliver'])->middleware('throttle:30,1');
Route::post('/{id}/cancel', [ShipmentController::class, 'cancel'])->middleware('throttle:30,1');
