<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Organization\Presentation\Http\Controllers\OrganizationController;

Route::middleware('auth.session')->group(function (): void {
    Route::post('/', [OrganizationController::class, 'store'])
        ->middleware('throttle:10,1');
});
