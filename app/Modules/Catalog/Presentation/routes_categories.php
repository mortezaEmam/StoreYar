<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Catalog\Presentation\Http\Controllers\CategoryController;

Route::get('/', [CategoryController::class, 'index']);
Route::post('/', [CategoryController::class, 'store'])->middleware('throttle:30,1');
