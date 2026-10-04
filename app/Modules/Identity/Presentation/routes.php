<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Identity\Presentation\Http\Controllers\AuthController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth.session')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/rotate', [AuthController::class, 'rotate']);
    Route::get('/me', [AuthController::class, 'me']);
});
