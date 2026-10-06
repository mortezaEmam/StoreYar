<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Authorization\Presentation\Http\Controllers\MembershipController;
use StoreYar\Modules\Identity\Presentation\Http\Middleware\AuthenticateSession;
use StoreYar\Modules\Organization\Presentation\Http\Controllers\OrganizationController;
use StoreYar\Shared\Application\Context\BusinessContext;

Route::middleware('auth.session')->group(function (): void {
    Route::get('/', [OrganizationController::class, 'index']);
    Route::post('/', [OrganizationController::class, 'store'])
        ->middleware('throttle:10,1');

    // عملیات روی یک سازمان — نیاز به عضویت
    Route::middleware('org.member')->group(function (): void {
        Route::get('/{id}/members', [MembershipController::class, 'index']);
        Route::get('/{id}', [OrganizationController::class, 'show']);
        Route::get('/{id}/branches', [OrganizationController::class, 'listBranches']);
        Route::post('/{id}/branches', [OrganizationController::class, 'storeBranch'])
            ->middleware('throttle:10,1');
    });

    // فقط owner
    Route::middleware('org.member:owner')->group(function (): void {
        Route::patch('/{id}', [OrganizationController::class, 'rename']);
        Route::post('/{id}/suspend', [OrganizationController::class, 'suspend']);
        Route::post('/{id}/activate', [OrganizationController::class, 'activate']);
    });

    Route::middleware('org.member:owner,admin')->group(function (): void {
        Route::post('/{id}/members', [MembershipController::class, 'store'])
            ->middleware('throttle:20,1');
    });

    Route::middleware('org.member:owner')->group(function (): void {
        Route::patch('/{id}/members/{userId}', [MembershipController::class, 'update']);
        Route::delete('/{id}/members/{userId}', [MembershipController::class, 'destroy']);
    });
});

