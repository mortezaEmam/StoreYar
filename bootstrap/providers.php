<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    App\Providers\SharedServiceProvider::class,
    StoreYar\Shared\Infrastructure\Providers\SharedServiceProvider::class,

];
