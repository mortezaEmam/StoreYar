<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Bus\Command;

use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Bus\Command\CommandHandlerMap;
use StoreYar\Shared\Application\Bus\Command\CommandHandlerResolver;
use StoreYar\Shared\Application\Bus\Command\LaravelCommandBus;
use StoreYar\Shared\Application\Bus\Command\LaravelCommandHandlerResolver;
use Tests\TestCase;

class CommandBusContainerTest extends TestCase
{
    public function test_command_bus_is_resolved_from_container(): void
    {
        $bus = $this->app->make(CommandBus::class);

        $this->assertInstanceOf(
            LaravelCommandBus::class,
            $bus
        );
    }

    public function test_command_handler_resolver_is_resolved_from_container(): void
    {
        $resolver = $this->app->make(
            CommandHandlerResolver::class
        );

        $this->assertInstanceOf(
            LaravelCommandHandlerResolver::class,
            $resolver
        );
    }

    public function test_command_handler_map_is_resolved_from_container(): void
    {
        $map = $this->app->make(
            CommandHandlerMap::class
        );

        $this->assertInstanceOf(
            CommandHandlerMap::class,
            $map
        );
    }
}
