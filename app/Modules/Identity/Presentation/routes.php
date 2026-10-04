<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Identity\Presentation\Http\Controllers\AuthController;

Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:5,1');
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');

Route::middleware('auth.session')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/rotate', [AuthController::class, 'rotate'])
        ->middleware('throttle:5,1');
    Route::get('/me', [AuthController::class, 'me']);
});
