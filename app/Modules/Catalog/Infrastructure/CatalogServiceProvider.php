<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Infrastructure;

use Illuminate\Support\ServiceProvider;
use StoreYar\Modules\Catalog\Application\Commands\ActivateProduct\ActivateProductCommand;
use StoreYar\Modules\Catalog\Application\Commands\ActivateProduct\ActivateProductHandler;
use StoreYar\Modules\Catalog\Application\Commands\ArchiveProduct\ArchiveProductCommand;
use StoreYar\Modules\Catalog\Application\Commands\ArchiveProduct\ArchiveProductHandler;
use StoreYar\Modules\Catalog\Application\Commands\CreateProduct\CreateProductCommand;
use StoreYar\Modules\Catalog\Application\Commands\CreateProduct\CreateProductHandler;
use StoreYar\Modules\Catalog\Application\Queries\GetProductById\GetProductByIdHandler;
use StoreYar\Modules\Catalog\Application\Queries\GetProductById\GetProductByIdQuery;
use StoreYar\Modules\Catalog\Application\Queries\ListProductsByOrganization\ListProductsByOrganizationHandler;
use StoreYar\Modules\Catalog\Application\Queries\ListProductsByOrganization\ListProductsByOrganizationQuery;
use StoreYar\Modules\Catalog\Domain\Contracts\ProductRepository;
use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\EloquentProductRepository;
use StoreYar\Shared\Application\Bus\Command\CommandHandlerRegistry;
use StoreYar\Shared\Application\Bus\Query\QueryHandlerRegistry;

final class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            ProductRepository::class,
            EloquentProductRepository::class,
        );

        $registry = $this->app->make(CommandHandlerRegistry::class);

        $registry->register(
            CreateProductCommand::class,
            CreateProductHandler::class,
        );

        $registry->register(
            ActivateProductCommand::class,
            ActivateProductHandler::class,
        );

        $registry->register(
            ArchiveProductCommand::class,
            ArchiveProductHandler::class,
        );

        $queryRegistry = $this->app->make(QueryHandlerRegistry::class);

        $queryRegistry->register(
            ListProductsByOrganizationQuery::class,
            ListProductsByOrganizationHandler::class,
        );

        $queryRegistry->register(
            GetProductByIdQuery::class,
            GetProductByIdHandler::class,
        );
    }
}
