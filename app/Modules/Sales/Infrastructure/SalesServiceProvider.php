<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Infrastructure;

use Illuminate\Support\ServiceProvider;
use StoreYar\Modules\Sales\Application\Commands\AddOrderLine\AddOrderLineCommand;
use StoreYar\Modules\Sales\Application\Commands\AddOrderLine\AddOrderLineHandler;
use StoreYar\Modules\Sales\Application\Commands\CancelOrder\CancelOrderCommand;
use StoreYar\Modules\Sales\Application\Commands\CancelOrder\CancelOrderHandler;
use StoreYar\Modules\Sales\Application\Commands\ConfirmOrder\ConfirmOrderCommand;
use StoreYar\Modules\Sales\Application\Commands\ConfirmOrder\ConfirmOrderHandler;
use StoreYar\Modules\Sales\Application\Commands\CreateOrder\CreateOrderCommand;
use StoreYar\Modules\Sales\Application\Commands\CreateOrder\CreateOrderHandler;
use StoreYar\Modules\Sales\Application\Queries\GetOrderById\GetOrderByIdHandler;
use StoreYar\Modules\Sales\Application\Queries\GetOrderById\GetOrderByIdQuery;
use StoreYar\Modules\Sales\Application\Queries\ListOrdersByOrganization\ListOrdersByOrganizationHandler;
use StoreYar\Modules\Sales\Application\Queries\ListOrdersByOrganization\ListOrdersByOrganizationQuery;
use StoreYar\Modules\Sales\Domain\Contracts\OrderRepository;
use StoreYar\Modules\Sales\Domain\Contracts\ProductCatalogGateway;
use StoreYar\Modules\Sales\Domain\Contracts\StockReservationService;
use StoreYar\Modules\Sales\Infrastructure\Catalog\CatalogProductCatalogGateway;
use StoreYar\Modules\Sales\Infrastructure\Inventory\InventoryStockReservationService;
use StoreYar\Modules\Sales\Infrastructure\Persistence\Eloquent\EloquentOrderRepository;
use StoreYar\Shared\Application\Bus\Command\CommandHandlerRegistry;
use StoreYar\Shared\Application\Bus\Query\QueryHandlerRegistry;

final class SalesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OrderRepository::class, EloquentOrderRepository::class);
        $this->app->singleton(StockReservationService::class, InventoryStockReservationService::class);
        $this->app->singleton(ProductCatalogGateway::class, CatalogProductCatalogGateway::class);

        $commands = $this->app->make(CommandHandlerRegistry::class);

        $commands->register(CreateOrderCommand::class, CreateOrderHandler::class);
        $commands->register(AddOrderLineCommand::class, AddOrderLineHandler::class);
        $commands->register(ConfirmOrderCommand::class, ConfirmOrderHandler::class);
        $commands->register(CancelOrderCommand::class, CancelOrderHandler::class);

        $queries = $this->app->make(QueryHandlerRegistry::class);

        $queries->register(GetOrderByIdQuery::class, GetOrderByIdHandler::class);
        $queries->register(ListOrdersByOrganizationQuery::class, ListOrdersByOrganizationHandler::class);
    }
}
