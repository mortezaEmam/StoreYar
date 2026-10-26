<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Reporting\Presentation\Http\Controllers\ReportController;

Route::get('/dashboard', [ReportController::class, 'dashboard']);
Route::get('/stock-on-hand', [ReportController::class, 'stockOnHand']);
Route::get('/low-stock', [ReportController::class, 'lowStock']);
Route::get('/sales-summary', [ReportController::class, 'salesSummary']);
Route::get('/purchase-summary', [ReportController::class, 'purchaseSummary']);
Route::get('/stock-movements', [ReportController::class, 'stockMovements']);
Route::get('/trial-balance', [ReportController::class, 'trialBalance']);
Route::get('/pos-sessions/{id}', [ReportController::class, 'posSessionReport']);
