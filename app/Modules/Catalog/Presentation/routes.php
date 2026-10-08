<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Catalog\Presentation\Http\Controllers\ProductController;

Route::post('/', [ProductController::class, 'store'])
    ->middleware('throttle:30,1');
