<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Inventory\Presentation\Http\Controllers\StockTransferController;

Route::post('/', [StockTransferController::class, 'store']);
