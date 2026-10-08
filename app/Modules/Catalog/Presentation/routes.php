<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Catalog\Presentation\Http\Controllers\ProductController;


Route::get('/', [ProductController::class, 'index']);
Route::post('/', [ProductController::class, 'store'])
    ->middleware('throttle:30,1');
Route::get('/{id}', [ProductController::class, 'show']);
