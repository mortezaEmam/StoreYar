<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Sync\Presentation\Http\Controllers\SyncController;

Route::post('/devices', [SyncController::class, 'registerDevice']);
Route::post('/push', [SyncController::class, 'push']);
Route::get('/pull/catalog', [SyncController::class, 'pullCatalog']);
Route::get('/pull/stock', [SyncController::class, 'pullStock']);
Route::get('/events', [SyncController::class, 'events']);
