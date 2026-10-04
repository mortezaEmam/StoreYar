<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(base_path(
    'app/Modules/Identity/Presentation/routes.php'
));
