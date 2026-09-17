<?php

use App\Providers\AppServiceProvider;
use App\Providers\SharedServiceProvider;
use StoreYar\Modules\Identity\Infrastructure\IdentityServiceProvider;
use StoreYar\Shared\Infrastructure\Providers\SharedServiceProvider as SharedInfrastructureServiceProvider;

return [
    AppServiceProvider::class,
    SharedServiceProvider::class,
    SharedInfrastructureServiceProvider::class,
    IdentityServiceProvider::class,
];
