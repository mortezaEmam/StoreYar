<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Accounting\Presentation\Http\Controllers\OpeningBalanceController;

Route::post('/', [OpeningBalanceController::class, 'store']);
