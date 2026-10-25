<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Catalog\Presentation\Http\Controllers\UnitController;

Route::get('/', [UnitController::class, 'index']);
Route::post('/', [UnitController::class, 'store']);
