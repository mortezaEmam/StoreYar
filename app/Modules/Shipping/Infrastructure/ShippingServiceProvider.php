<?php

declare(strict_types=1);

namespace StoreYar\Modules\Shipping\Infrastructure;

use Illuminate\Support\ServiceProvider;
use StoreYar\Modules\Shipping\Application\Commands\CreateShipment\CreateShipmentCommand;
use StoreYar\Modules\Shipping\Application\Commands\CreateShipment\CreateShipmentHandler;
use StoreYar\Modules\Shipping\Application\Commands\DeliverShipment\DeliverShipmentCommand;
use StoreYar\Modules\Shipping\Application\Commands\DeliverShipment\DeliverShipmentHandler;
use StoreYar\Modules\Shipping\Application\Commands\ShipShipment\ShipShipmentCommand;
use StoreYar\Modules\Shipping\Application\Commands\ShipShipment\ShipShipmentHandler;
use StoreYar\Modules\Shipping\Application\Queries\GetShipmentById\GetShipmentByIdHandler;
use StoreYar\Modules\Shipping\Application\Queries\GetShipmentById\GetShipmentByIdQuery;
use StoreYar\Modules\Shipping\Application\Queries\ListShipmentsByOrganization\ListShipmentsByOrganizationHandler;
use StoreYar\Modules\Shipping\Application\Queries\ListShipmentsByOrganization\ListShipmentsByOrganizationQuery;
use StoreYar\Modules\Shipping\Domain\Contracts\ShipmentRepository;
use StoreYar\Modules\Shipping\Domain\Contracts\ShippableOrderGateway;
use StoreYar\Modules\Shipping\Infrastructure\Persistence\Eloquent\EloquentShipmentRepository;
use StoreYar\Modules\Shipping\Infrastructure\Sales\SalesShippableOrderGateway;
use StoreYar\Shared\Application\Bus\Command\CommandHandlerRegistry;
use StoreYar\Shared\Application\Bus\Query\QueryHandlerRegistry;

final class ShippingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            ShipmentRepository::class,
            EloquentShipmentRepository::class,
        );

        $this->app->singleton(
            ShippableOrderGateway::class,
            SalesShippableOrderGateway::class,
        );

        $commands = $this->app->make(CommandHandlerRegistry::class);

        $commands->register(
            CreateShipmentCommand::class,
            CreateShipmentHandler::class,
        );

        $commands->register(
            ShipShipmentCommand::class,
            ShipShipmentHandler::class,
        );

        $commands->register(
            DeliverShipmentCommand::class,
            DeliverShipmentHandler::class,
        );

        $queries = $this->app->make(QueryHandlerRegistry::class);

        $queries->register(
            GetShipmentByIdQuery::class,
            GetShipmentByIdHandler::class,
        );

        $queries->register(
            ListShipmentsByOrganizationQuery::class,
            ListShipmentsByOrganizationHandler::class,
        );
    }
}
