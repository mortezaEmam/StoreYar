<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Catalog\Presentation\Http\Controllers\ProductMasterController;

Route::get('/{id}/master', [ProductMasterController::class, 'show']);
Route::patch('/{id}/master', [ProductMasterController::class, 'update']);
