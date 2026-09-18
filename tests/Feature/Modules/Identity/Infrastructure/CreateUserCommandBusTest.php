<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Identity\Infrastructure;

use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserHandler;
use StoreYar\Shared\Application\Bus\Command\CommandHandlerMap;
use StoreYar\Shared\Application\Bus\Command\CommandHandlerRegistry;
use Tests\TestCase;

final class CreateUserCommandBusTest extends TestCase
{
    public function test_create_user_command_is_registered_with_its_handler(): void
    {
        $map = $this->app->make(CommandHandlerMap::class);

        self::assertSame(
            CreateUserHandler::class,
            $map->handlerFor(CreateUserCommand::class),
        );
    }

    public function test_create_user_handler_can_be_resolved_from_the_container(): void
    {
        $registry = $this->app->make(CommandHandlerRegistry::class);

        $map = $registry->map();

        $handlerClass = $map->handlerFor(CreateUserCommand::class);
        $handler = $this->app->make($handlerClass);

        self::assertInstanceOf(
            CreateUserHandler::class,
            $handler,
        );
    }
}
