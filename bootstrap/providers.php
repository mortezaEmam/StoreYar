<?php

use App\Providers\AppServiceProvider;
use App\Providers\SharedServiceProvider;
use StoreYar\Modules\Authorization\Infrastructure\AuthorizationServiceProvider;
use StoreYar\Modules\Identity\Infrastructure\IdentityServiceProvider;
use StoreYar\Modules\Organization\Infrastructure\OrganizationServiceProvider;
use StoreYar\Shared\Infrastructure\Providers\SharedServiceProvider as SharedInfrastructureServiceProvider;

return [
    AppServiceProvider::class,
    SharedServiceProvider::class,
    SharedInfrastructureServiceProvider::class,
    IdentityServiceProvider::class,
    OrganizationServiceProvider::class,
    AuthorizationServiceProvider::class,
];
