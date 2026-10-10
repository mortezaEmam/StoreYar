<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Infrastructure;

use Illuminate\Support\ServiceProvider;
use StoreYar\Modules\Inventory\Application\Commands\AdjustStock\AdjustStockCommand;
use StoreYar\Modules\Inventory\Application\Commands\AdjustStock\AdjustStockHandler;
use StoreYar\Modules\Inventory\Application\Commands\InitializeStock\InitializeStockCommand;
use StoreYar\Modules\Inventory\Application\Commands\InitializeStock\InitializeStockHandler;
use StoreYar\Modules\Inventory\Application\Queries\GetStockByProduct\GetStockByProductHandler;
use StoreYar\Modules\Inventory\Application\Queries\GetStockByProduct\GetStockByProductQuery;
use StoreYar\Modules\Inventory\Application\Queries\ListStockByOrganization\ListStockByOrganizationHandler;
use StoreYar\Modules\Inventory\Application\Queries\ListStockByOrganization\ListStockByOrganizationQuery;
use StoreYar\Modules\Inventory\Domain\Contracts\ProductExistenceChecker;
use StoreYar\Modules\Inventory\Domain\Contracts\StockItemRepository;
use StoreYar\Modules\Inventory\Infrastructure\Catalog\CatalogProductExistenceChecker;
use StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent\EloquentStockItemRepository;
use StoreYar\Shared\Application\Bus\Command\CommandHandlerRegistry;
use StoreYar\Shared\Application\Bus\Query\QueryHandlerRegistry;

final class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            StockItemRepository::class,
            EloquentStockItemRepository::class,
        );

        $this->app->singleton(
            ProductExistenceChecker::class,
            CatalogProductExistenceChecker::class,
        );

        $commands = $this->app->make(CommandHandlerRegistry::class);

        $commands->register(InitializeStockCommand::class, InitializeStockHandler::class);
        $commands->register(AdjustStockCommand::class, AdjustStockHandler::class);

        $queries = $this->app->make(QueryHandlerRegistry::class);

        $queries->register(
            ListStockByOrganizationQuery::class,
            ListStockByOrganizationHandler::class,
        );

        $queries->register(
            GetStockByProductQuery::class,
            GetStockByProductHandler::class,
        );
    }
}
