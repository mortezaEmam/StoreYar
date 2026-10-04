<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Identity\Presentation\Http\Middleware\AuthenticateSession;
use StoreYar\Modules\Organization\Presentation\Http\Controllers\OrganizationController;

Route::middleware(AuthenticateSession::class)->group(function (): void {
    Route::get('/', [OrganizationController::class, 'index']);
    Route::post('/', [OrganizationController::class, 'store'])
        ->middleware('throttle:10,1');

    Route::get('/{id}', [OrganizationController::class, 'show']);
    Route::patch('/{id}', [OrganizationController::class, 'rename']);
    Route::post('/{id}/suspend', [OrganizationController::class, 'suspend']);
    Route::post('/{id}/activate', [OrganizationController::class, 'activate']);
});
