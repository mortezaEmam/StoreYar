<?php

use App\Providers\AppServiceProvider;
use App\Providers\SharedServiceProvider;
use StoreYar\Modules\Authorization\Infrastructure\AuthorizationServiceProvider;
use StoreYar\Modules\Catalog\Infrastructure\CatalogServiceProvider;
use StoreYar\Modules\Identity\Infrastructure\IdentityServiceProvider;
use StoreYar\Modules\Inventory\Infrastructure\InventoryServiceProvider;
use StoreYar\Modules\Organization\Infrastructure\OrganizationServiceProvider;
use StoreYar\Modules\Sales\Infrastructure\SalesServiceProvider;
use StoreYar\Shared\Infrastructure\Providers\SharedServiceProvider as SharedInfrastructureServiceProvider;

return [
    AppServiceProvider::class,
    SharedServiceProvider::class,
    SharedInfrastructureServiceProvider::class,
    IdentityServiceProvider::class,
    OrganizationServiceProvider::class,
    AuthorizationServiceProvider::class,
    CatalogServiceProvider::class,
    InventoryServiceProvider::class,
    InventoryServiceProvider::class,
    SalesServiceProvider::class,
];
