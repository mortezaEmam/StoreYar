<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Payments\Presentation\Http\Controllers\PaymentController;

Route::get('/', [PaymentController::class, 'index']);
Route::post('/', [PaymentController::class, 'store'])->middleware('throttle:30,1');
Route::get('/{id}', [PaymentController::class, 'show']);
Route::post('/{id}/succeed', [PaymentController::class, 'succeed'])->middleware('throttle:30,1');
Route::post('/{id}/fail', [PaymentController::class, 'fail'])->middleware('throttle:30,1');
