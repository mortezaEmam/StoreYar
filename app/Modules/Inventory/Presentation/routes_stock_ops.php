<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Inventory\Presentation\Http\Controllers\StockIssueController;
use StoreYar\Modules\Inventory\Presentation\Http\Controllers\StockReceiveController;

Route::post('/receive', [StockReceiveController::class, 'receive']);
Route::post('/issue', [StockIssueController::class, 'issue']);
Route::get('/movements', [StockReceiveController::class, 'movements']);
Route::get('/by-warehouse', [StockReceiveController::class, 'byWarehouse']);
