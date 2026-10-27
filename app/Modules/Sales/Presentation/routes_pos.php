<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Sales\Presentation\Http\Controllers\PosController;
use StoreYar\Modules\Sales\Presentation\Http\Controllers\PosSalesHistoryController;

Route::post('/sessions/open', [PosController::class, 'openSession']);
Route::post('/sessions/{id}/close', [PosController::class, 'closeSession']);
Route::get('/sessions', [PosController::class, 'sessions']);
Route::get('/sessions/current', [PosController::class, 'currentSession']);
Route::post('/sale', [PosController::class, 'sale']);

Route::get('/sales', [PosSalesHistoryController::class, 'index']);
Route::get('/sales/{id}', [PosSalesHistoryController::class, 'show']);
