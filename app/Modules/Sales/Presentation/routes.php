<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Sales\Presentation\Http\Controllers\OrderController;

Route::get('/', [OrderController::class, 'index']);
Route::post('/', [OrderController::class, 'store'])
    ->middleware('throttle:30,1');
Route::get('/{id}', [OrderController::class, 'show']);
Route::post('/{id}/lines', [OrderController::class, 'addLine'])
    ->middleware('throttle:60,1');
Route::post('/{id}/confirm', [OrderController::class, 'confirm'])
    ->middleware('throttle:30,1');
Route::post('/{id}/cancel', [OrderController::class, 'cancel'])
    ->middleware('throttle:30,1');
