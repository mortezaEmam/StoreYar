<?php

declare(strict_types=1);

namespace StoreYar\Modules\Purchasing\Infrastructure;

use Illuminate\Support\ServiceProvider;
use StoreYar\Modules\Purchasing\Application\Commands\CreatePurchaseOrder\CreatePurchaseOrderCommand;
use StoreYar\Modules\Purchasing\Application\Commands\CreatePurchaseOrder\CreatePurchaseOrderHandler;
use StoreYar\Modules\Purchasing\Application\Commands\ReceiveAgainstPO\ReceiveAgainstPOCommand;
use StoreYar\Modules\Purchasing\Application\Commands\ReceiveAgainstPO\ReceiveAgainstPOHandler;
use StoreYar\Shared\Application\Bus\Command\CommandHandlerRegistry;

final class PurchasingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $commands = $this->app->make(CommandHandlerRegistry::class);
        $commands->register(CreatePurchaseOrderCommand::class, CreatePurchaseOrderHandler::class);
        $commands->register(ReceiveAgainstPOCommand::class, ReceiveAgainstPOHandler::class);
    }
}
