<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Reporting\Presentation\Http\Controllers\ExportController;

Route::get('/manifest', [ExportController::class, 'manifest']);
Route::get('/products', [ExportController::class, 'products']);
Route::get('/barcodes', [ExportController::class, 'barcodes']);
Route::get('/stock', [ExportController::class, 'stock']);
Route::get('/customers', [ExportController::class, 'customers']);
Route::get('/suppliers', [ExportController::class, 'suppliers']);
Route::get('/sales', [ExportController::class, 'sales']);
Route::get('/movements', [ExportController::class, 'movements']);
Route::get('/journals', [ExportController::class, 'journals']);
