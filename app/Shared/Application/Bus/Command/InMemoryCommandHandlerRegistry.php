<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Bus\Command;

use LogicException;

final class InMemoryCommandHandlerRegistry implements CommandHandlerRegistry
{
    /**
     * @var array<class-string<Command>, class-string<CommandHandler>>
     */
    private array $handlers = [];

    public function register(
        string $commandClass,
        string $handlerClass
    ): void {
        if (isset($this->handlers[$commandClass])) {
            throw new LogicException(
                sprintf(
                    'A handler is already registered for command [%s].',
                    $commandClass
                )
            );
        }

        $this->handlers[$commandClass] = $handlerClass;
    }

    public function map(): CommandHandlerMap
    {
        return new InMemoryCommandHandlerMap($this->handlers);
    }
}
