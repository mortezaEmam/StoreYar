<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Purchasing\Presentation\Http\Controllers\PurchaseReturnController;

Route::post('/', [PurchaseReturnController::class, 'store']);
