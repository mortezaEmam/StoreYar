<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Bus\Command;

use LogicException;

final class InMemoryCommandHandlerMap implements CommandHandlerMap
{
    /**
     * @param array<class-string<Command>, class-string<CommandHandler>> $handlers
     */
    public function __construct(
        private readonly array $handlers,
    ) {
    }

    public function handlerFor(string $commandClass): string
    {
        $handler = $this->handlers[$commandClass] ?? null;

        if ($handler === null) {
            throw new LogicException(
                sprintf(
                    'No handler registered for command [%s].',
                    $commandClass
                )
            );
        }

        return $handler;
    }
}
