<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Authorization\Presentation\Http\Controllers\PermissionController;

Route::get('/catalog', [PermissionController::class, 'catalog']);
Route::get('/me', [PermissionController::class, 'myPermissions']);
Route::get('/matrix', [PermissionController::class, 'roleMatrix']);
