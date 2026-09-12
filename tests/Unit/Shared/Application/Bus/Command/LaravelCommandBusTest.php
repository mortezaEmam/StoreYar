<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Bus\Command;

use LogicException;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Application\Bus\Command\CommandHandlerMap;
use StoreYar\Shared\Application\Bus\Command\InMemoryCommandHandlerMap;
use StoreYar\Shared\Application\Bus\Command\LaravelCommandBus;
use StoreYar\Shared\Application\Bus\Command\LaravelCommandHandlerResolver;
use Tests\TestCase;

class LaravelCommandBusTest extends TestCase
{
    public function test_it_dispatches_command_to_registered_handler(): void
    {
        $map = new InMemoryCommandHandlerMap([
            FakeCommand::class => FakeCommandHandler::class,
        ]);

        $this->app->instance(
            CommandHandlerMap::class,
            $map
        );

        $resolver = new LaravelCommandHandlerResolver(
            $this->app
        );

        $bus = new LaravelCommandBus($resolver);

        $result = $bus->dispatch(
            new FakeCommand('hello')
        );

        $this->assertSame(
            'handled:hello',
            $result
        );
    }

    public function test_it_rejects_unregistered_command(): void
    {
        $map = new InMemoryCommandHandlerMap([]);

        $this->app->instance(
            CommandHandlerMap::class,
            $map
        );

        $resolver = new LaravelCommandHandlerResolver(
            $this->app
        );

        $bus = new LaravelCommandBus($resolver);

        $this->expectException(LogicException::class);

        $bus->dispatch(
            new UnregisteredCommand()
        );
    }
}

final class FakeCommand implements Command
{
    public function __construct(
        public readonly string $value,
    ) {
    }
}

final class UnregisteredCommand implements Command
{
}

final class FakeCommandHandler implements CommandHandler
{
    public function handle(Command $command): mixed
    {
        if (! $command instanceof FakeCommand) {
            throw new LogicException(
                'Unexpected command.'
            );
        }

        return 'handled:' . $command->value;
    }
}
