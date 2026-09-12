<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Bus\Command;

use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Application\Bus\Command\CommandHandlerRegistry;
use Tests\TestCase;

final class CommandBusRegistryIntegrationTest extends TestCase
{
    public function test_registered_handler_can_be_resolved_and_executed_through_command_bus(): void
    {
        $registry = $this->app->make(
            CommandHandlerRegistry::class
        );

        $registry->register(
            RegisteredCommand::class,
            RegisteredCommandHandler::class
        );

        $result = $this->app
            ->make(CommandBus::class)
            ->dispatch(
                new RegisteredCommand('store-001')
            );

        $this->assertSame(
            'handled:store-001',
            $result
        );
    }
}

final class RegisteredCommand implements Command
{
    public function __construct(
        public readonly string $businessId,
    ) {
    }
}

final class RegisteredCommandHandler implements CommandHandler
{
    public function handle(Command $command): mixed
    {
        assert($command instanceof RegisteredCommand);

        return 'handled:' . $command->businessId;
    }
}
