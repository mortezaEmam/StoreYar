<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Catalog\Presentation\Http\Controllers\CustomerController;

Route::get('/', [CustomerController::class, 'index']);
Route::post('/', [CustomerController::class, 'store']);
