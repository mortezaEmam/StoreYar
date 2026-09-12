<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Bus\Command;

use Illuminate\Contracts\Container\Container;
use LogicException;

final class LaravelCommandHandlerResolver implements CommandHandlerResolver
{
    public function __construct(
        private readonly Container $container,
    ) {
    }

    public function resolve(Command $command): CommandHandler
    {
        $commandClass = $command::class;

        $handlerClass = $this->container->get(
            CommandHandlerMap::class
        )->handlerFor($commandClass);

        $handler = $this->container->make($handlerClass);

        if (! $handler instanceof CommandHandler) {
            throw new LogicException(
                sprintf(
                    'Command handler [%s] must implement [%s].',
                    $handlerClass,
                    CommandHandler::class
                )
            );
        }

        return $handler;
    }
}
