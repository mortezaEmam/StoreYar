<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Catalog\Presentation\Http\Controllers\BrandController;

Route::get('/', [BrandController::class, 'index']);
Route::post('/', [BrandController::class, 'store']);
