<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Sales\Presentation\Http\Controllers\PosReturnController;

Route::post('/', [PosReturnController::class, 'store']);
