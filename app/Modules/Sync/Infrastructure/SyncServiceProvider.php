<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sync\Infrastructure;

use Illuminate\Support\ServiceProvider;
use StoreYar\Modules\Sync\Application\Commands\PushOfflineEvents\PushOfflineEventsCommand;
use StoreYar\Modules\Sync\Application\Commands\PushOfflineEvents\PushOfflineEventsHandler;
use StoreYar\Modules\Sync\Application\Commands\RegisterDevice\RegisterDeviceCommand;
use StoreYar\Modules\Sync\Application\Commands\RegisterDevice\RegisterDeviceHandler;
use StoreYar\Shared\Application\Bus\Command\CommandHandlerRegistry;

final class SyncServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $commands = $this->app->make(CommandHandlerRegistry::class);
        $commands->register(RegisterDeviceCommand::class, RegisterDeviceHandler::class);
        $commands->register(PushOfflineEventsCommand::class, PushOfflineEventsHandler::class);
    }
}
