<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Bus\Command;

use LogicException;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Application\Bus\Command\InMemoryCommandHandlerRegistry;
use Tests\TestCase;

final class CommandHandlerRegistryTest extends TestCase
{
    public function test_it_registers_command_handler(): void
    {
        $registry = new InMemoryCommandHandlerRegistry();

        $registry->register(
            FakeRegisteredCommand::class,
            FakeRegisteredCommandHandler::class
        );

        $map = $registry->map();

        $this->assertSame(
            FakeRegisteredCommandHandler::class,
            $map->handlerFor(FakeRegisteredCommand::class)
        );
    }

    public function test_it_rejects_duplicate_command_handler_registration(): void
    {
        $registry = new InMemoryCommandHandlerRegistry();

        $registry->register(
            FakeRegisteredCommand::class,
            FakeRegisteredCommandHandler::class
        );

        $this->expectException(LogicException::class);

        $registry->register(
            FakeRegisteredCommand::class,
            AnotherFakeCommandHandler::class
        );
    }
}

final class FakeRegisteredCommand implements Command
{
}

final class FakeRegisteredCommandHandler implements CommandHandler
{
    public function handle(Command $command): mixed
    {
        return null;
    }
}

final class AnotherFakeCommandHandler implements CommandHandler
{
    public function handle(Command $command): mixed
    {
        return null;
    }
}
