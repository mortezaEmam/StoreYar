<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Payments\Presentation\Http\Controllers\PartyPaymentController;

Route::get('/', [PartyPaymentController::class, 'index']);
Route::post('/customers', [PartyPaymentController::class, 'customer']);
Route::post('/suppliers', [PartyPaymentController::class, 'supplier']);
